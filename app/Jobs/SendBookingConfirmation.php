<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Booking;

class SendBookingConfirmation implements ShouldQueue
{
    use Queueable, InteractsWithQueue, Dispatchable, SerializesModels;

    public $booking;

    /**
     * Create a new job instance.
     */
    public function __construct($booking)
    {
        $this->booking = $booking;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Booking confirmation sent for booking ID: ' . $this->booking->id);
    }
}
