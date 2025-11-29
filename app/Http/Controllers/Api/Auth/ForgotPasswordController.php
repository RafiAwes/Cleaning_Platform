<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\PasswordResetRequested;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    /**
     * Send password reset token to user's email
     */
    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        // Generate reset token
        $resetToken = Str::random(60);
        
        // Set expiration time (60 minutes from now)
        $expiresAt = Carbon::now()->addMinutes(60);
        
        // Save reset token and expiration time
        $user->update([
            'reset_token' => Hash::make($resetToken),
            'reset_token_expires_at' => $expiresAt,
        ]);

        // Send reset token to user's email
        $user->notify(new PasswordResetRequested($resetToken));

        return response()->json([
            'message' => 'Password reset link sent to your email'
        ], 200);
    }

    /**
     * Reset the user's password
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        // Check if reset token has expired
        if (Carbon::now()->isAfter($user->reset_token_expires_at)) {
            return response()->json([
                'message' => 'Password reset token has expired. Please request a new one.'
            ], 422);
        }

        // Verify the token
        if (!Hash::check($request->token, $user->reset_token)) {
            return response()->json([
                'message' => 'Invalid password reset token'
            ], 422);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->password),
            'reset_token' => null,
            'reset_token_expires_at' => null,
        ]);

        return response()->json([
            'message' => 'Password reset successfully'
        ], 200);
    }
}