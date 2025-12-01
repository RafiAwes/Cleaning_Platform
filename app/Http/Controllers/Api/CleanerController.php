<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\Cleaner;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class CleanerController extends Controller
{
    public function create(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
            'image' => 'nullable',
            'status' => 'required|in:active,assigned,completed'
        ]);

        $cleaner = Cleaner::create($data);
        $cleaner->image = $request->file('image')->store('cleaners', 'public');
        $cleaner->save();
        return response()->json([
            'message' => 'Cleaner created successfully',
            'cleaner' => $cleaner
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $cleaner = Cleaner::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
            'image' => 'nullable',
            'status' => 'required|in:active,assigned,completed'
        ]);
        $cleaner->update($data);
        if ($request->file('image')) {
            $cleaner->image = $request->file('image')->store('cleaners', 'public');
        }
        $cleaner->save();
        return response()->json([
            'message' => 'Cleaner updated successfully',
            'cleaner' => $cleaner
        ], 200);
    }

    public function delete($id)
    {
        $cleaner = Cleaner::findOrFail($id);
        $cleaner->delete();
        return response()->json([
            'message' => 'Cleaner deleted successfully'
        ], 200);
    }

    public function getCleanersByVendor()
    {
        $cleaners = Cleaner::where('vendor_id', Auth::id())->paginate(10);
        return response()->json([
            'message' => 'Cleaners retrieved successfully',
            'cleaners' => $cleaners
        ], 200);
    }

    public function cleanerDetails($id)
    {
        $cleaner = Cleaner::findOrFail($id)->where('vendor_id', Auth::id())->first();
        $ongoingBooking = Booking::where('cleaner_id', $cleaner->id)->where('status', 'ongoing')->first();
        $completedBooking = Booking::where('cleaner_id', $cleaner->id)->where('status', 'completed')->first();
        return response()->json([
            'message' => 'Cleaner details',
            'ongoing_booking' => $ongoingBooking,
            'completed_booking' => $completedBooking,
            'cleaner' => $cleaner,
        ], 200);
    }

    public function availableCleaners()
    {
        $cleaners = Cleaner::where('vendor_id', Auth::id())->where('status', 'active')->get();
        return response()->json([
            'message' => 'Available cleaners',
            'cleaners' => $cleaners
        ], 200);
    }

    public function assignCleaners(Request $request, $bookingId)
    {
         $data = $request->validate([
            'cleaner_id' => 'required|exists:cleaners,id'
        ]);

        $booking = Booking::findOrFail($bookingId);
        $cleaner = Cleaner::findOrFail($data['cleaner_id']);

        // STEP 1: Vendor can only assign if booking is accepted
        if ($booking->status !== 'accepted') {
            return response()->json([
                'message' => 'Booking must be accepted before assigning a cleaner.'
            ], 422);
        }

        // STEP 2: Ensure cleaner is not assigned anywhere else
        if ($cleaner->status === 'assigned') {
            return response()->json([
                'message' => 'Cleaner is already assigned to another job.'
            ], 422);
        }

        // STEP 3: Update cleaner status
        $cleaner->update([
            'status' => 'assigned'
        ]);

        // STEP 4: Update booking status + assign cleaner
        $booking->update([
            'cleaner_id' => $cleaner->id,
            'status' => 'ongoing'
        ]);

        return response()->json([
            'message' => 'Cleaner assigned successfully!',
            'booking' => $booking,
            'cleaner' => $cleaner
        ]);
    }
}
