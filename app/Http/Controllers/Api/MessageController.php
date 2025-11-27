<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Message;
use Illuminate\Http\Request;
use App\Events\ChatMessageSent;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    
    public function sendMessage(Request $request)
    {
         $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string',
            // 'booking_id' => 'nullable|exists:bookings,id',
        ]);

            $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            // 'booking_id' => $request->booking_id,
            'message' => $request->message
        ]);

        event(new ChatMessageSent($message));

        return response()->json(['message' => 'Message sent', 'data' => $message]);
    }

    public function getMessages($userId)
    {
        $messages = Message::where(function ($q) use ($userId) {
            $q->where('sender_id', Auth::id())
            ->where('receiver_id', $userId);
        })->orWhere(function ($q) use ($userId) {
            $q->where('sender_id', $userId)
            ->where('receiver_id', Auth::id());
        })
        ->orderBy('created_at', 'asc')
        ->get();

        return response()->json($messages);
    }

}
