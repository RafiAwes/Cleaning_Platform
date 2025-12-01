<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Cleaner;
use App\Models\Package;
use App\Models\Booking;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Services\StripeService;
use App\Notifications\BookingStatus;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Notifications\BookingCreated;
use App\Notifications\DeliveryRequest;
use App\Notifications\CustomerBookedPackage;



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

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $booking = new Booking();
        $booking->customer_id = $user->id;
        // $booking->cleaner_id = null;
        $booking->package_id = $packageId;
        $booking->booking_date = $data['date'];
        // $booking->time = $data['time'];
        $booking->location = $data['location'];
        $booking->status = $data['status'];
        $booking->save();
        
        $user->notify(new BookingCreated($booking));
        $vendor_id = $booking->package->vendor_id;
        $vendor = User::findOrFail($vendor_id);
        $vendor->notify(new CustomerBookedPackage($booking, 'new'));
        

        return response()->json([
            'message' => 'Booking created successfully',
            'booking' => $booking,
        ], 201);
    }

    public function getBookingDetails($bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        return response()->json([
            'message' => 'Booking details retrieved successfully',
            'booking' => $booking,
        ]);
    }
    public function acceptBooking(Request $request, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        if ($booking->status == 'pending') {
            $booking->status = 'accepted';
            $booking->update();
            $customer_id = $booking->customer_id;
            $customer = User::findOrFail($customer_id);
            $customer->notify(new BookingStatus($booking, 'rejected'));

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
            $customer_id = $booking->customer_id;
            $customer = User::findOrFail($customer_id);
            $customer->notify(new BookingStatus($booking, 'rejected'));
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

    public function checkAvailabilityByDate($packageId)
    {
        // Get package & vendor
        $package = Package::findOrFail($packageId);
        $vendorId = $package->vendor_id;

        // Count vendor cleaners
        $totalCleaners = Cleaner::where('vendor_id', $vendorId)->count();

        if ($totalCleaners === 0) {
            return response()->json([
                'message' => 'No cleaners available for this vendor.',
                'unavailable_dates' => []
            ]);
        }

        //Get bookings for this vendor’s cleaners
        $bookings = Booking::whereHas('cleaner', function($q) use ($vendorId) 
        {
            $q->where('vendor_id', $vendorId);
        })
        ->whereIn('status', ['pending', 'ongoing'])
        ->get();

        //Group bookings by date
        $assignedByDate = [];

        foreach ($bookings as $booking) 
        {
            $date = Carbon::parse($booking->booking_date)->format('Y-m-d');

            if (!isset($assignedByDate[$date])) {
                $assignedByDate[$date] = 0;
            }

            if ($booking->cleaner_id) {
                $assignedByDate[$date]++;
            }
        }

        //Determine unavailable dates
        $unavailableDates = [];

        foreach ($assignedByDate as $date => $assignedCount) {
            if ($assignedCount >= $totalCleaners) {
                $unavailableDates[] = $date;
            }
        }

        return response()->json([
            'unavailable_dates' => $unavailableDates,
            'total_cleaners' => $totalCleaners
        ]);
    }

    public function completeBooking(Request $request, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        $booking->status = 'completed';
        $booking->update();
        $customer_id = $booking->customer_id;
        $customer = User::findOrFail($customer_id);
        $customer->notify(new BookingStatus($booking, 'completed'));
        $vendor_name = $booking->package->vendor->user->name;
        $customer->notify(new DeliveryRequest($booking, $vendor_name));
        return response()->json([
            'message' => 'Booking completed successfully',
            'booking' => $booking,
        ], 200);
    }

    public function cancelBooking(Request $request, $bookingId)
    {
        $user = Auth::user();
        $booking = Booking::findOrFail($bookingId);

        // Only customer who booked OR vendor assigned can cancel
        if ($user->role == 'customer' && $booking->customer_id != $user->id) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($user->role == 'vendor' && $booking->vendor_id != $user->id) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Check if booking was already paid
        $transaction = Transaction::where('booking_id', $bookingId)->first();

        if ($transaction && $transaction->status === 'paid') {
            
            // Refund 90% to customer, platform keeps 10%
            (new StripeService())->processCancellationRefund($transaction); // Fixed syntax error
        }

        // Update booking status
        $booking->status = 'cancelled';
        $booking->notes = ($user->role == 'customer')
            ? 'Customer cancelled the booking'
            : 'Vendor cancelled the booking';

        $booking->save();

        // Notify opposite party
        if ($user->role == 'customer') {
            $vendor = User::find($booking->vendor_id);
            if ($vendor) $vendor->notify(new \App\Notifications\BookingStatus($booking, 'cancelled'));
        } else {
            $customer = User::find($booking->customer_id);
            if ($customer) $customer->notify(new \App\Notifications\BookingStatus($booking, 'cancelled'));
        }

        return response()->json([
            'message' => 'Booking cancelled successfully. Refund processed.',
            'booking' => $booking,
        ], 200);
    }

    public function getBookings()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if($user->role == 'customer'){
            $bookings = Booking::where('customer_id', $user->id)->get();
        } else if($user->role == 'vendor'){
            $bookings = Booking::where('vendor_id', $user->id)->get();
        } else if($user->role == 'admin'){
            $bookings = Booking::all();
        } else {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        return response()->json([
            'message' => 'Bookings retrieved successfully',
            'bookings' => $bookings
        ]);
    }

    public function rateBooking(Request $request, $bookingId)
    {
        $data = $request->validate([
            'rating' => 'required|numeric|between:1,5',
            'note' => 'nullable|string',
        ]);

       $booking = Booking::findOrFail($bookingId);

       if (!$booking) {
           return response()->json([
               'message' => 'Booking not found'
           ], 401);
       }
       if ($booking->status !== 'completed') {
           return response()->json([
               'message' => 'Booking not completed'
           ], 401);
       }
       $booking->rating = $data['rating'];
       $booking->note = $data['note'];
       $booking->update();

       $avgRating = $booking->package->bookings()->whereNotNull('ratings')->avg('ratings');
       $package = $booking->package;
       $package->ratings = $avgRating;
       $package->update();

       return response()->json([
           'message' => 'Booking rated successfully',
           'booking' => $booking,
       ], 200);
    }

}