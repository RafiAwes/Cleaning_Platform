<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\EmailVerificationRequested;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class EmailVerificationController extends Controller
{
    /**
     * Send verification code to user's email
     */
    public function sendVerificationCode(Request $request)
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
        
        // Check if user is already verified
        if ($user->email_verified_at !== null) {
            return response()->json([
                'message' => 'Email is already verified'
            ], 422);
        }

        // Generate 6-digit verification code
        $verificationCode = rand(100000, 999999);
        
        // Set expiration time (30 minutes from now)
        $expiresAt = Carbon::now()->addMinutes(30);
        
        // Save verification code and expiration time
        $user->update([
            'verification_code' => Hash::make($verificationCode),
            'verification_expires_at' => $expiresAt,
        ]);

        // Send verification code to user's email
        $user->notify(new EmailVerificationRequested($verificationCode));

        return response()->json([
            'message' => 'Verification code sent to your email'
        ], 200);
    }

    /**
     * Verify the email with the provided code
     */
    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'verification_code' => 'required|numeric|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        // Check if user is already verified
        if ($user->email_verified_at !== null) {
            return response()->json([
                'message' => 'Email is already verified'
            ], 422);
        }

        // Check if verification code has expired or doesn't exist
        if ($user->verification_expires_at === null || Carbon::now()->isAfter($user->verification_expires_at)) {
            return response()->json([
                'message' => 'Verification code has expired or not requested. Please request a new one.'
            ], 422);
        }

        // Verify the code
        if (!Hash::check($request->verification_code, $user->verification_code)) {
            return response()->json([
                'message' => 'Invalid verification code'
            ], 422);
        }

        $user->email_verified_at = Carbon::now();
        $user->verification_code = null;
        $user->verification_expires_at = null;
        $user->update();

        // Create token for the user after successful verification
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Email verified successfully',
            'access_token' => $token,
            'user' => $user
        ], 200);
    }
}