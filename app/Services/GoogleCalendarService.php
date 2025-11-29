<?php
namespace App\Services;

use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;

class GoogleCalendarService
{
    protected $service;

    public function __construct()
    {
        $client = new Google_Client();
        $client->setAuthConfig(storage_path('app/' . env('GOOGLE_SERVICE_ACCOUNT')));
        $client->setScopes([Google_Service_Calendar::CALENDAR]);
        $client->setSubject(env('GOOGLE_CALENDAR_OWNER')); // optional but RECOMMENDED

        $this->service = new Google_Service_Calendar($client);
    }

    public function createEvent($summary, $description, $startDate, $endDate)
    {
        $event = new Google_Service_Calendar_Event([
            'summary' => $summary,
            'description' => $description,
            'start' => [
                'dateTime' => $startDate,
                'timeZone' => 'Asia/Dhaka'
            ],
            'end' => [
                'dateTime' => $endDate,
                'timeZone' => 'Asia/Dhaka'
            ],
        ]);

        return $this->service->events->insert(env('GOOGLE_CALENDAR_ID'), $event);
    }
}
