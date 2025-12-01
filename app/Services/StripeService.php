<?php

namespace App\Services;

use Exception;
use Stripe\Stripe;
use Stripe\Account;
use Stripe\Transfer;
use App\Models\Vendor;
use Stripe\AccountLink;
use App\Models\Booking;
use Stripe\PaymentIntent;
use App\Models\Transaction;
use Stripe\Exception\ApiErrorException;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
    }

    public function createConnectedAccount(array $accountData)
    {
        try {
            $account = Account::create([
                'type' => 'express',
                'email' => $accountData['email'] ?? null,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
            ]);
            return $account;
        } catch (ApiErrorException $e) {
            throw new Exception('Stripe API Error: ' . $e->getMessage());
        }
    }

    public function createAccountLink(string $accountId, string $returnUrl, string $refreshUrl)
    {
        try {
            $accountLink = AccountLink::create([
                'account' => $accountId,
                'refresh_url' => $refreshUrl,
                'return_url' => $returnUrl,
                'type' => 'account_onboarding',
            ]);
            return $accountLink;
        } catch (ApiErrorException $e) {
            throw new Exception('Stripe API Error: ' . $e->getMessage());
        }
    }

    public function createPaymentIntent(int $amount, string $currency, array $metadata = [], string $idempotencyKey = null)
    {
        try {
            $opts = [];
            if ($idempotencyKey) {
                $opts['idempotency_key'] = $idempotencyKey;
            }
            $paymentIntent = PaymentIntent::create(array_merge([
                'amount' => $amount,
                'currency' => $currency,
                'payment_method_types' => ['card'],
                'metadata' => $metadata,
            ], []), $opts);
            return $paymentIntent;
        } catch (ApiErrorException $e) {
            throw new Exception('Stripe API Error: ' . $e->getMessage());
        }
    }

    public function createTransfer(int $amount, string $currency, string $destinationAccountId, string $sourceTransactionId = null, array $metadata = [], string $idempotencyKey = null)
    {
        try {
            $data = [
                'amount' => $amount,
                'currency' => $currency,
                'destination' => $destinationAccountId,
                'metadata' => $metadata,
            ];
            if ($sourceTransactionId) {
                $data['source_transaction'] = $sourceTransactionId;
            }

            $opts = [];
            if ($idempotencyKey) $opts['idempotency_key'] = $idempotencyKey;

            $transfer = Transfer::create($data, $opts);
            return $transfer;
        } catch (ApiErrorException $e) {
            throw new Exception('Stripe API Error: ' . $e->getMessage());
        }
    }

    public function retrieveAccount(string $accountId)
    {
        try {
            $account = Account::retrieve($accountId);
            return $account;
        } catch (ApiErrorException $e) {
            throw new Exception('Stripe API Error: ' . $e->getMessage());
        }
    }

      public function releaseVendorPayment(Booking $booking)
    {
        if ($booking->payment_status !== 'paid_to_platform') {
            throw new Exception('Booking payment is not ready for release.');
        }

        $vendor = $booking->vendor;
        if (!$vendor || !$vendor->stripe_id) {
            throw new Exception('Vendor stripe account not configured.');
        }

        // amounts in cents
        $total = intval($booking->total_price * 100);
        $platformFee = intval(round($total * 0.10)); // 10% platform fee (changeable)
        $vendorAmount = $total - $platformFee;

        // the payment_intent_id should be stored when webhook marked success
        $paymentIntentId = $booking->payment_intent_id;
        if (!$paymentIntentId) {
            throw new Exception('Missing payment intent id on booking.');
        }

        // retrieve charge id from payment intent via Stripe SDK
        try {
            $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
        } catch (\Exception $e) {
            throw new Exception('Unable to retrieve payment intent: ' . $e->getMessage());
        }

        $chargeId = $paymentIntent->charges->data[0]->id ?? null;
        if (!$chargeId) {
            throw new Exception('Charge ID not found for payment intent.');
        }

        // Create a transfer to vendor's connected account
        $transfer = $this->createTransfer(
            $vendorAmount,
            'usd',
            $vendor->stripe_id,
            $chargeId,
            ['booking_id' => $booking->id],
            uniqid('transfer_')
        );

        // store transaction record
        $transaction = Transaction::create([
            'booking_id' => $booking->id,
            'vendor_id' => $vendor->id,
            'payment_intent_id' => $paymentIntentId,
            'charge_id' => $chargeId,
            'transfer_id' => $transfer->id ?? null,
            'total_amount' => $total,
            'platform_fee' => $platformFee,
            'vendor_amount' => $vendorAmount,
            'status' => 'released',
        ]);

        // update booking
        $booking->payment_status = 'released_to_vendor';
        $booking->save();

        return $transfer;
    }

}
