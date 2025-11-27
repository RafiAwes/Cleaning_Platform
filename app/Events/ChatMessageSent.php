<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $receiverId;

    public function __construct(Message $message)
    {
        $this->message = $message;
        $this->receiverId = $message->receiver_id;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel("chat.{$this->receiverId}"),
        ];
    }
    public function broadcastAs()
    {
        return 'message.sent';
    }
}
