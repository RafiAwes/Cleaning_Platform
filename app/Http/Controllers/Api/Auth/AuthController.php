<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use App\Services\EmailVerificationService;

class AuthController extends Controller
{
    protected $emailVerificationService;

    public function __construct(EmailVerificationService $emailVerificationService)
    {
        $this->emailVerificationService = $emailVerificationService;
    }

    public function registerCustomer(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
            // 'role' => 'required|string|in:admin,user,author',
        ]);
        $matchPassword = $data['password'] === $data['password_confirmation'];
        if (!$matchPassword) {
            throw ValidationException::withMessages([
                'password' => 'The password does not match.',
            ]);
        }
        
        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = Hash::make($data['password']);
        $user->role = "customer";
        $user->created_at = Carbon::now();
        $user->save();

        // Send verification code
        $this->emailVerificationService->sendVerificationCode($user);

        return response()->json([
            'message' => 'Registration successful. Please check your email for verification code.',
            'user_id' => $user->id,
        ], 201);
    }

    public function registerVendor(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'address' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
            // 'role' => 'required|string|in:admin,user,author',
        ]);
        $matchPassword = $data['password'] === $data['password_confirmation'];
        if (!$matchPassword) {
            throw ValidationException::withMessages([
                'password' => 'The password does not match.',
            ]);
        }
        
        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->address = $data['address'];
        $user->password = Hash::make($data['password']);
        $user->role = "vendor";
        $user->created_at = Carbon::now();
        $user->save();

        // Send verification code
        $this->emailVerificationService->sendVerificationCode($user);

        return response()->json([
            'message' => 'Registration successful. Please check your email for verification code.',
            'user_id' => $user->id,
        ], 201);
    }

    public function verifyRegistration(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'verification_code' => 'required|numeric|digits:6',
        ]);

        $user = User::find($data['user_id']);

        $result = $this->emailVerificationService->verifyEmail($user, $data['verification_code']);

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], 422);
        }

        // Create token only after successful verification
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => $result['message'],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
           throw ValidationException::withMessages([
               'email' => 'The provided credentials are incorrect.',
           ]);
        }

        // Check if email is verified
        if ($user->email_verified_at === null) {
            return response()->json([
                'message' => 'Please verify your email before logging in.',
            ], 422);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    public function logout(Request $request)
    {
        /** @var \Laravel\Sanctum\PersonalAccessToken $personalAccessToken */
        $personalAccessToken = $request->user()->currentAccessToken();
        $personalAccessToken->delete();

        return response()->json([
            'message' => 'Logged out Successfully.',
        ], 200);
    }
}