<?php
namespace App\Services;

use Exception;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\Account;
use Stripe\Transfer;
use Stripe\AccountLink;
use Stripe\PaymentIntent;
use Stripe\BalanceTransaction;
use Stripe\Exception\ApiErrorException;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
    }

    public function createConnectedAccount(array $accountData): ?Account
    {
        try {
            $account = Account::Create([
                'type' => 'express',
                'email' => $accountData['email'],
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

    public function createAccountLink(string $accountId, string $returnUrl, string $refreshUrl): ?AccountLink
    {
        try{
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

    public function createPaymentIntent(float $amount, string $currency, array $metadata, string $idempotancyKey): ?PaymentIntent
    {
        try{
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => $currency,
                'payment_method_types' => ['card'],
                'metadata' => $metadata,
            ], [
                'idempotency_key' => $idempotancyKey,
            ]);
            return $paymentIntent;

        } catch (ApiErrorException $e) {
            throw new Exception('Stripe API Error: ' . $e->getMessage());
        }
    }

    public function createTransfer(int $amount, string $currency, string $destinationAccountId, string $sourceTransactionId, array $metadata, string $idempotencyKey): ?Transfer
    {
        try{
             $transfer = Transfer::create([
                'amount' => $amount,
                'currency' => $currency,
                'destination' => $destinationAccountId,
                'source_transaction' => $sourceTransactionId,
                'metadata' => $metadata,
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);
            return $transfer;
        } catch (ApiErrorException $e) {
            throw new Exception('Stripe API Error: ' . $e->getMessage());
        }
    }

    public function retrieveAccount(string $accountId): ?Account
    {
        try{
            $account = Account::retrieve($accountId);
            return $account;
        } catch (ApiErrorException $e) {
            throw new Exception('Stripe API Error: ' . $e->getMessage());
        }
    }
}
