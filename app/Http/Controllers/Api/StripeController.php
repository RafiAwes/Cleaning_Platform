<?php

namespace App\Http\Controllers\Api;

use Stripe\Account;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Booking;
use Illuminate\Http\Request;
use App\Services\StripeService;
use Laravel\Sanctum\HasApiTokens;
use App\Services\PasswordService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;


/**
 * @property \App\Services\StripeService $stripe
 */
class StripeController extends Controller
{
    protected $stripe;
    protected $password;

    public function __construct(StripeService $stripe, PasswordService $password)
    {
        $this->stripe = $stripe;
        $this->password = $password;
    }

    public function connectStripe(Request $request)
    {
        $vendor = Auth::user();
        if(!$vendor->stripe_id){
            $account = $this->stripe->createConnectedAccount([
                'email' => $vendor->email,
            ]);
            $vendor->stripe_id = $account['id'];
            $vendor->save();
        }
        $returnUrl = env('API_URL') . '/api/stripe/callback?vendor_id=' . $vendor->id;
        $refreshUrl = env('FRONTEND_URL') . '/vendor/stripe/refresh';

        $accountLink = $this->stripe->createAccountLink($vendor->stripe_id, $returnUrl, $refreshUrl);

        return response()->json([
            'account_link' => $accountLink['url'],
        ]);
    }

    public function callback(Request $request)
    {
        $vendor = Vendor::find($request->vendor_id);
        $account = $this->stripe->retrieveAccount($vendor->stripe_id);
        if($account->details_submitted){
            $vendor->is_stripe_onboarded = true;
            $vendor->save();
        }

        return response()->json([
            'message' => 'Stripe callback successful',
        ]);
    }

    public function createPaymentIntent(Request $request)
    {
        $request->validate([
            'booking_id' => 'required',
        ]);
        
        $booking = Booking::with('vendor')->findorfail($request->booking_id);

        if (!$booking->vendor->stripe_onboarding_completed) {
            return response()->json(['error' => 'Vendor is not connected to Stripe'], 400);
        }
          // Amount (convert to cents)
        $amount = intval($booking->total_price * 100);
        $currency = 'usd';

        // Set metadata
        $metadata = [
            'booking_id' => $booking->id,
            'vendor_id' => $booking->vendor->id
        ];

        // Create Payment Intent
        $paymentIntent = $this->stripe->createPaymentIntent($amount, $currency, $metadata, uniqid());

        return response()->json([
            'clientSecret' => $paymentIntent->client_secret,
            'paymentIntentId' => $paymentIntent->id
        ]);
    }

    public function webhook(Request $request)
    {
        $event = $request->type;
        $object = $request->data['object'];

        if ($event == 'payment_intent.succeeded') {

            $paymentIntentId = $object['id'];
            $bookingId = $object['metadata']['booking_id'];
            $vendorId = $object['metadata']['vendor_id'];

            $booking = Booking::find($bookingId);
            $vendor = Vendor::find($vendorId);

            // Platform takes 10%
            $total = $object['amount_received'];
            $platformFee = intval($total * 0.10);
            $vendorAmount = $total - $platformFee;

            // Now transfer vendor amount
            $transfer = $this->stripe->createTransfer(
                $vendorAmount,
                'usd',
                $vendor->stripe_account_id,
                $object['charges']['data'][0]['id'],
                ['booking_id' => $bookingId],
                uniqid()
            );

            // mark booking as paid
            $booking->payment_status = 'paid';
            $booking->save();
        }

        return response()->json(['status' => 'ok']);
    
    }
}