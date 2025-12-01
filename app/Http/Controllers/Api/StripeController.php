<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Vendor;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;

class StripeController extends Controller
{
    protected $stripe;

    public function __construct(StripeService $stripe)
    {
        $this->stripe = $stripe;
    }

    /**
     * Vendor presses "Connect Stripe"
     */
    public function connectStripe(Request $request)
    {
        $vendor = Auth::user(); // assuming vendor is the authenticated user

        if (!$vendor->stripe_id) {
            $account = $this->stripe->createConnectedAccount([
                'email' => $vendor->email,
            ]);
            $vendor->stripe_id = $account->id;
            $vendor->save();
        }

        $returnUrl = env('API_URL') . '/api/stripe/callback?vendor_id=' . $vendor->id;
        $refreshUrl = env('FRONTEND_URL') . '/vendor/stripe/refresh';

        $accountLink = $this->stripe->createAccountLink($vendor->stripe_id, $returnUrl, $refreshUrl);

        return response()->json([
            'account_link' => $accountLink->url ?? $accountLink['url'] ?? null,
        ]);
    }

    /**
     * Stripe redirects here after onboarding completed
     */
    public function callback(Request $request)
    {
        $vendor = Vendor::findOrFail($request->vendor_id);
        $account = $this->stripe->retrieveAccount($vendor->stripe_id);

        if (!empty($account->details_submitted)) {
            $vendor->is_stripe_onboarded = true;
            $vendor->save();
        }

        return response()->json(['message' => 'Stripe callback successful']);
    }

    /**
     * Create payment intent for a booking (customer payment)
     */
    public function createPaymentIntent(Request $request)
    {
        $request->validate(['booking_id' => 'required|exists:bookings,id']);

        $booking = Booking::with('vendor')->findOrFail($request->booking_id);

        // If vendor not onboarded, you can still accept payment to platform.
        $amount = intval($booking->total_price * 100);
        $currency = 'usd';

        $metadata = [
            'booking_id' => $booking->id,
            'vendor_id' => $booking->vendor_id,
        ];

        $paymentIntent = $this->stripe->createPaymentIntent($amount, $currency, $metadata, uniqid('pi_'));

        return response()->json([
            'clientSecret' => $paymentIntent->client_secret ?? $paymentIntent['client_secret'] ?? null,
            'paymentIntentId' => $paymentIntent->id ?? $paymentIntent['id'] ?? null,
        ]);
    }

    /**
     * Stripe webhook for events; configure endpoint in Stripe Dashboard
     *
     * Note: validate signature if you configure a signing secret.
     */
    public function webhook(Request $request)
    {
        // If using Stripe signature verification, verify it here.
        $eventType = $request->input('type');
        $object = $request->input('data.object');

        if ($eventType === 'payment_intent.succeeded') {
            $paymentIntentId = $object['id'];
            $bookingId = $object['metadata']['booking_id'] ?? null;

            if ($bookingId) {
                $booking = Booking::find($bookingId);
                if ($booking) {
                    // Mark collected to platform (store payment_intent_id)
                    $booking->payment_status = 'paid_to_platform';
                    $booking->payment_intent_id = $paymentIntentId;
                    $booking->save();

                    // Optionally create a transaction record for collection (status = paid)
                    Transaction::create([
                        'booking_id' => $booking->id,
                        'vendor_id' => $booking->vendor_id,
                        'payment_intent_id' => $paymentIntentId,
                        'charge_id' => $object['charges']['data'][0]['id'] ?? null,
                        'transfer_id' => null,
                        'total_amount' => $object['amount_received'] ?? $object['amount'] ?? 0,
                        'platform_fee' => 0,
                        'vendor_amount' => 0,
                        'status' => 'paid',
                    ]);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
