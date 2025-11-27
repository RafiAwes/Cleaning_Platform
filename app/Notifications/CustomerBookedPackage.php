<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerBookedPackage extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */

    public $booking;
    public function __construct($booking)
    {
        //
        $this->booking = $booking;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => 'You have a new Order',
            'booking' => $this->booking->id,
            'customer_id' => $this->booking->customer_id,
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    // public function toMail(object $notifiable): MailMessage
    // {
    //     return (new MailMessage)
    //         ->subject('New Booking Confirmation')
    //         ->line('You have successfully booked a cleaning service.')
    //         ->line('Booking ID: ' . $this->booking->id)
    //         ->action('View Booking', url('/bookings/' . $this->booking->id))
    //         ->line('Thank you for using our cleaning service!');
    // }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    // public function toArray(object $notifiable): array
    // {
    //     return [
    //         'message' => 'You have a new booking',
    //         'booking_id' => $this->booking->id,
    //         'customer_id' => $this->booking->customer_id,
    //     ];
    // }
}