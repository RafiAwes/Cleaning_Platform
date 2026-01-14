<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\{Booking, Cleaner, CustomBooking, CustomPrice, Package, Transaction, User, Vendor};
use App\Services\StripeService;
use App\Notifications\{BookingCreated, BookingStatus, CustomerBookedPackage, DeliveryRequest};
use App\Http\Controllers\Controller;
use Carbon\Carbon;




class BookingController extends Controller
{
    public function addCustomBooking(Request $request)
    {
         $request->validate([
            'items' => 'required|array',
            'items.*.custom_price_id' => 'required|exists:custom_prices,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        $customBooking = CustomBooking::create([
            'items' => $request->input('items'),
        ]);
    }

    public function getCustomBooking(Request $request)
    {
        $customBooking = CustomBooking::findOrFail($request->custom_booking_id);
        return response()->json($customBooking, 200);
    }

    private function createCustomBookingOrder(Request $request, $vendor, $user)
    {

        $customBooking = CustomBooking::findOrFail($request->custom_booking_id);

        // Calculate total
        $total = 0;
        foreach ($customBooking->items as $item) {
            $price = CustomPrice::findOrFail($item['custom_price_id']);
            $total += $price->price * $item['qty'];
        }

        $booking = Booking::create([
            'customer_id' => $user->id,
            'cleaner_id' => null,
            'package_id' => null,
            'booking_date_time' => $request->date,
            'is_custom' => true,
            'address' => $request->address,
            'status' => 'new',
            'total_price' => $total,
        ]);

        // Notify vendor
        $vendorUser = User::findOrFail($vendor->user_id);
        $vendorUser->notify(new CustomerBookedPackage($booking, 'new'));

        return response()->json([
            'message' => 'Custom booking created successfully.',
            'booking' => $booking,
        ], 201);
    }

    private function createPackageBookingOrder(Request $request, $vendor, $user)
    {
        $package = Package::findOrFail($request->package_id);

        $booking = Booking::create([
            'customer_id' => $user->id,
            'cleaner_id' => null,
            'package_id' => $package->id,
            'booking_date_time' => $request->date,
            'is_custom' => false,
            'address' => $request->address,
            'status' => 'new',
            'total_price' => $package->price,
        ]);

        // Notify vendor
        $vendorUser = User::findOrFail($vendor->user_id);
        $vendorUser->notify(new CustomerBookedPackage($booking, 'new'));

        return response()->json([
            'message' => 'Package booking created successfully.',
            'booking' => $booking,
        ], 201);
    }

    public function createBooking(Request $request)
    {
        $data = $request->validate([
            'vendor_id' => 'required|exists:users,id',
            'package_id' => 'nullable|exists:packages,id',
            'custom_booking_id' => 'nullable|exists:custom_bookings,id',
            'date' => 'required|date|before:2100-01-01|after:2020-01-01',
            'address' => 'required|string',
            'status' => 'required|string',
        ]);

        /** @var User $user */
        $user = Auth::user();

        $vendor = Vendor::where('user_id', $data['vendor_id'])->firstOrFail();

        if ($vendor->is_custom == true) {

            if (!$data['custom_booking_id']) {
                return response()->json([
                    'message' => 'This vendor requires custom booking options.'
                ], 422);
            }

            return $this->createCustomBookingOrder($request, $vendor, $user);
        }

        if (!$data['package_id']) {
            return response()->json([
                'message' => 'Package ID is required for non-custom vendors.'
            ], 422);
        }

        return $this->createPackageBookingOrder($request, $vendor, $user);
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