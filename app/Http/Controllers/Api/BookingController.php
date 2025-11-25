<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Cleaner;


class BookingController extends Controller
{
    public function createBooking(Request $request, $packageId)
    {
        $data = $request->validate([
            // 'customer_id' => 'required|exists:users,id',
            // 'cleaner_id' => 'nullable',
            'package_id' => 'required|exists:packages,id',
            'date' => 'required|date|before:2100-01-01|after:2020-01-01',
            // 'time' => 'required|string',
            'location' => 'required|string',
            'status' => 'required|string',
        ]);

        /** @var \Illuminate\Contracts\Auth\Guard $auth */
        $auth = auth();
        
        $booking = new Booking();
        $booking->customer_id = $auth->id();
        // $booking->cleaner_id = null;
        $booking->package_id = $packageId;
        $booking->booking_date = $data['date'];
        // $booking->time = $data['time'];
        $booking->location = $data['location'];
        $booking->status = $data['status'];
        $booking->save();

        return response()->json([
            'message' => 'Booking created successfully',
            'booking' => $booking,
        ], 201);
    }
    
    public function acceptBooking(Request $request, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        if ($booking->status == 'pending') {
            $booking->status = 'accepted';
            $booking->update();
        } else {
            return response()->json([
                'message' => 'Booking is not pending',
            ], 400);
        }
        return response()->json([
            'message' => 'Booking accepted successfully',
            'booking' => $booking,
        ], 200);
    }

    public function rejectBooking(Request $request, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        if ($booking->status == 'pending') {
            $booking->status = 'rejected';
            $booking->update();
        } else {
            return response()->json([
                'message' => 'Booking is not pending',
            ], 400);
        }
        return response()->json([
            'message' => 'Booking rejected successfully',
            'booking' => $booking,
        ], 200);
    }
}
