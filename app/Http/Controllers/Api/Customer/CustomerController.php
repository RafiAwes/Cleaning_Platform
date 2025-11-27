<?php

namespace App\Http\Controllers\Api\Customer;

use App\Models\Booking;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Notifications\DeliveryCustomerStatus;


class CustomerController extends Controller
{
    public function dashboard()
    {
        return "Customer Dashboard";
    }

    public function acceptDelivery(Request $request, $package_id)
    {
        $booking = Booking::where('package_id', $package_id)->first();
        if ($booking) {
            $booking->status = 'accepted';
            $booking->update();
            $vendor = $booking->vendor;
            $vendor->notify(new DeliveryCustomerStatus($booking));
            return response()->json([
                'message' => 'Delivery accepted',
                'booking' => $booking
            ]);
        } else {
            return response()->json([
                'message' => 'Booking not found'
            ], 404);
        }
    }

    public function rejectDelivery(Request $request, $package_id)
    {
        $booking = Booking::where('package_id', $package_id)->first();
        if ($booking) {
            $booking->status = 'rejected';
            $booking->update();
            $vendor = $booking->vendor;
            $vendor->notify(new DeliveryCustomerStatus($booking));
            return response()->json([
                'message' => 'Delivery rejected',
                'booking' => $booking
            ]);
        } else {
            return response()->json([
                'message' => 'Booking not found'
            ], 404);
        }
    }
}
