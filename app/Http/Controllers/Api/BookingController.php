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
    /**
     * Get availability dates for a package
     *
     * @param int $packageId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailabilityDate($packageId)
    {
        $package = Package::findOrFail($packageId);

        // Get all bookings for this package in the next 90 days
        // Start from tomorrow to avoid past dates
        $startDate = Carbon::now()->addDay()->startOfDay();
        $endDate = $startDate->copy()->addDays(89);

        $bookedDates = Booking::where('package_id', $packageId)
            ->whereBetween('booking_date_time', [$startDate, $endDate])
            ->pluck('booking_date_time')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();

        // Generate available dates (all dates in the range except booked ones)
        $availableDates = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateString = $currentDate->format('Y-m-d');
            if (!in_array($dateString, $bookedDates)) {
                $availableDates[] = $dateString;
            }
            $currentDate->addDay();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'package_id' => $packageId,
                'available_dates' => $availableDates,
                'booked_dates' => $bookedDates,
                'total_available' => count($availableDates),
            ]
        ], 200);
    }

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
        $items = is_array($customBooking->items)
            ? $customBooking->items
            : json_decode($customBooking->items ?? '[]', true);

        foreach ($items as $item) {
            $price = CustomPrice::findOrFail($item['custom_price_id']);
            $total += $price->price * $item['qty'];
        }

        $booking = Booking::create([
            'customer_id' => $user->id,
            'vendor_id' => $vendor->user_id,
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
        $vendorUser->notify(new CustomerBookedPackage($booking));

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
            'vendor_id' => $vendor->user_id,
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
        $vendorUser->notify(new CustomerBookedPackage($booking));

        return response()->json([
            'message' => 'Package booking created successfully.',
            'booking' => $booking,
        ], 201);
    }

    public function createBooking(Request $request)
    {
        // Validate request with flexible parameters for both old and new format
        $data = $request->validate([
            // New format from frontend
            'package_id' => 'required_without:vendor_id|exists:packages,id',
            'booking_date_time' => 'required_without:date|date',
            'date' => 'required_without:booking_date_time|date|before:2100-01-01|after:2020-01-01',
            'addons' => 'sometimes|array',
            'addons.*.id' => 'integer',
            'addons.*.quantity' => 'integer|min:1',
            'address' => 'required|string',
            'city' => 'sometimes|string',
            'postal_code' => 'sometimes|string',
            'country' => 'sometimes|string',
            'total_price' => 'sometimes|numeric|min:0',

            // Old format for backward compatibility
            'vendor_id' => 'sometimes|exists:users,id',
            'custom_booking_id' => 'nullable|exists:custom_bookings,id',
            'status' => 'sometimes|string',
        ]);

        /** @var User $user */
        $user = Auth::user();

<<<<<<< HEAD
        // Handle new frontend format
        if ($request->has('package_id') && $request->has('booking_date_time')) {
            return $this->createPackageBookingFromFrontend($request, $user);
        }
=======
        $vendor = Vendor::where('user_id', '=', $data['vendor_id'], 'and')->firstOrFail();
>>>>>>> 0e957735c0968fac7bab88b1465322d09bf19d6f

        // Handle old format
        if ($request->has('vendor_id')) {
            $vendor = Vendor::where('user_id', '=', $data['vendor_id'], 'and')->firstOrFail();

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

        return response()->json([
            'message' => 'Invalid booking request format.'
        ], 422);
    }

    /**
     * Create package booking from new frontend format
     */
    private function createPackageBookingFromFrontend(Request $request, $user)
    {
        try {
            $package = Package::with('addons')->findOrFail($request->package_id);

            // Calculate total price from package
            $totalPrice = (float) $package->price;

            // Prepare add-ons data for validation and storage
            $addonsData = [];
            if ($request->has('addons') && is_array($request->addons)) {
                foreach ($request->addons as $addonInput) {
                    // Validate that addon exists in package
                    $packageAddon = $package->addons()->where('addon_id', $addonInput['id'])->first();

                    if (!$packageAddon) {
                        return response()->json([
                            'success' => false,
                            'message' => "Add-on ID {$addonInput['id']} is not available for this package."
                        ], 422);
                    }

                    // Get the actual price from database (don't trust frontend)
                    $addonPrice = (float) $packageAddon->pivot->price;

                    // Store addon with validated price
                    $addonsData[$addonInput['id']] = ['price' => $addonPrice];

                    // Add to total price
                    $totalPrice += $addonPrice;
                }
            }

            $booking = Booking::create([
                'customer_id' => $user->id,
                'vendor_id' => $package->vendor_id,
                'cleaner_id' => null,
                'package_id' => $package->id,
                'booking_date_time' => $request->booking_date_time,
                'is_custom' => false,
                'address' => $request->address,
                'status' => 'pending',
                'total_price' => $totalPrice,
                'notes' => $request->input('notes', ''),
            ]);

            // Attach add-ons to booking with validated prices
            if (!empty($addonsData)) {
                $booking->addons()->attach($addonsData);
            }

            // Load relationships for response
            $booking->load(['package', 'addons']);

            // Notify vendor about new booking
            try {
                $vendorUser = User::findOrFail($package->vendor_id);
                $vendorUser->notify(new CustomerBookedPackage($booking));
            } catch (\Exception $notificationError) {
                // Log notification error but don't fail the booking
                \Log::error('Failed to send vendor notification: ' . $notificationError->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully!',
                'booking' => $booking,
                'booking_id' => $booking->id,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Booking creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking: ' . $e->getMessage(),
                'error' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null
            ], 500);
        }
    }


    public function getBookingDetails($bookingId)
    {
        $booking = Booking::with(['customer', 'package', 'addons'])->findOrFail($bookingId);
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
        $totalCleaners = Cleaner::where('vendor_id', '=', $vendorId, 'and')->count();

        if ($totalCleaners === 0) {
            return response()->json([
                'message' => 'No cleaners available for this vendor.',
                'unavailable_dates' => []
            ]);
        }

        //Get bookings for this vendor’s cleaners
        $bookings = Booking::whereHas('cleaner', function($q) use ($vendorId)
        {
            $q->where('vendor_id', '=', $vendorId);
        })
        ->whereIn('status', ['pending', 'ongoing'], 'and', false)
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
        $transaction = Transaction::where('booking_id', '=', $bookingId, 'and')->first();

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
            $vendor = User::find($booking->vendor_id, ['*']);
            if ($vendor) $vendor->notify(new \App\Notifications\BookingStatus($booking, 'cancelled'));
        } else {
            $customer = User::find($booking->customer_id, ['*']);
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
<<<<<<< HEAD
            $bookings = Booking::with(['package', 'addons'])->where('customer_id', '=', $user->id, 'and')->get();
        } else if($user->role == 'vendor'){
            $bookings = Booking::with(['package', 'addons', 'customer'])->where('vendor_id', '=', $user->id, 'and')->get();
=======
            $bookings = Booking::where('customer_id', '=', $user->id, 'and')->get();
        } else if($user->role == 'vendor'){
            $bookings = Booking::where('vendor_id', '=', $user->id, 'and')->get();
>>>>>>> 0e957735c0968fac7bab88b1465322d09bf19d6f
        } else if($user->role == 'admin'){
            $bookings = Booking::with(['package', 'addons', 'customer'])->get();
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

    public function vendorBookings()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
<<<<<<< HEAD

        $vendorId = $user->id;

        // Include bookings directly linked to vendor_id as well as legacy ones linked via package vendor_id
        $bookings = Booking::with(['customer', 'package', 'cleaner', 'addons'])
=======
        
        $vendorId = $user->id;

        // Include bookings directly linked to vendor_id as well as legacy ones linked via package vendor_id
        $bookings = Booking::with(['customer', 'package', 'cleaner'])
>>>>>>> 0e957735c0968fac7bab88b1465322d09bf19d6f
            ->where(function ($query) use ($vendorId) {
                $query->where('vendor_id', '=', $vendorId, 'and')
                    ->orWhereHas('package', function ($packageQuery) use ($vendorId) {
                        $packageQuery->where('vendor_id', '=', $vendorId, 'and');
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();
<<<<<<< HEAD

=======
        
>>>>>>> 0e957735c0968fac7bab88b1465322d09bf19d6f
        return response()->json([
            'message' => 'Vendor bookings retrieved successfully',
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
