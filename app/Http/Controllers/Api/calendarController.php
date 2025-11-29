<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\GoogleCalendarService;

class calendarController extends Controller
{
      protected $calendar;

    public function __construct(GoogleCalendarService $calendar)
    {
        $this->calendar = $calendar;
    }

    public function createAppointment(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'title' => 'required|string',
            'description' => 'nullable|string',
        ]);

        // user selected date
        $date = $request->date;

        // create from 10:00 AM to 10:30 AM (example)
        $start = $date . ' 10:00:00';
        $end   = $date . ' 10:30:00';

        $event = $this->calendar->createEvent(
            $request->title,
            $request->description ?? 'Appointment',
            $start,
            $end
        );

        return response()->json([
            'success' => true,
            'message' => 'Appointment added to Google Calendar',
            'data' => $event
        ]);
    }
}
