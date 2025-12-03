<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use App\Models\Vendor;
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
            'business_name' => 'required|string|max:255',
            'service_category' => 'required',
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
        // Note: Not storing address in users table for vendors
        $user->password = Hash::make($data['password']);
        $user->role = "vendor";
        $user->created_at = Carbon::now();
        $user->save();

        $categories = is_array($data['service_category']) ? $data['service_category'] : [$data['service_category']];

        // Create vendor profile with pending approval status
        $vendor = new Vendor();
        $vendor->user_id = $user->id; 
        $vendor->address = $data['address']; 
        $vendor->business_name = $data['business_name'];
        $vendor->service_category = json_encode($categories);     
        $vendor->approval_status = 'pending';
        $vendor->save();

        // Send verification code
        $this->emailVerificationService->sendVerificationCode($user);

        return response()->json([
            'message' => 'Registration successful. Your account is pending admin approval. Please check your email for verification code.',
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

        // Check if vendor is approved
        if ($user->role === 'vendor') {
            $vendor = $user->vendor;
            if ($vendor && $vendor->approval_status === 'pending') {
                return response()->json([
                    'message' => 'Your vendor account is pending admin approval.',
                ], 403);
            }
            
            if ($vendor && $vendor->approval_status === 'rejected') {
                return response()->json([
                    'message' => 'Your vendor account has been rejected.',
                ], 403);
            }
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