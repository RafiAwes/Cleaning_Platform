<?php

namespace App\Http\Controllers\Api\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Hash, DB};
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\Controller;
use App\Models\{Document, User, Vendor, Category};
use App\Services\{EmailVerificationService, FileUploadService};
use Carbon\Carbon;

class AuthController extends Controller
{
    protected $emailVerificationService;
    protected FileUploadService $fileUploadService;

    public function __construct(EmailVerificationService $emailVerificationService, FileUploadService $fileUploadService)
    {
        $this->emailVerificationService = $emailVerificationService;
        $this->fileUploadService = $fileUploadService;
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
            'role' => 'required|string|in:customer,vendor',
            'address' => 'required_if:role,vendor|string',
            'business_name' => 'required_if:role,vendor|string|max:255',
            'service_categories' => 'required_if:role,vendor|array',
            'service_categories.*' => 'exists:categories,id',
        ]);

        $matchPassword = $data['password'] === $data['password_confirmation'];
        if (! $matchPassword) {
            throw ValidationException::withMessages([
                'password' => 'The password does not match.',
            ]);
        }

        $type = $data['role'];
        if ($type === 'vendor') {
            // Use database transaction to ensure all-or-nothing registration
            $result = DB::transaction(function () use ($data) {
                $user = new User;
                $user->name = $data['name'];
                $user->email = $data['email'];
                $user->password = Hash::make($data['password']);
                $user->role = 'vendor';
                $user->created_at = Carbon::now();
                $user->save();

                // Handle categories - make sure it's an array and filter out any invalid entries
                $categories = [];
                if (isset($data['service_categories'])) {
                    $categories = is_array($data['service_categories']) 
                        ? $data['service_categories'] 
                        : [$data['service_categories']];
                    
                    // Filter out any null/empty values
                    $categories = array_filter($categories, function($cat) {
                        return !empty($cat);
                    });
                }

                // Create vendor profile with pending approval status
                $vendor = new Vendor;
                $vendor->user_id = $user->id;
                $vendor->address = $data['address'];
                $vendor->business_name = $data['business_name'];
                $vendor->approval_status = 'pending';
                $vendor->save();

                // Attach selected categories to the vendor using the relationship (only if categories exist)
                if (!empty($categories)) {
                    $vendor->categories()->attach($categories);
                }

                // Send verification code
                $this->emailVerificationService->sendVerificationCode($user);

                return [
                    'user' => $user,
                    'message' => 'Vendor registered successfully. Please verify your email.'
                ];
            });

            return response()->json([
                'message' => $result['message'],
                'user' => $result['user'],
            ], 201);
        }

        // Handle customer registration (also in transaction for consistency)
        $result = DB::transaction(function () use ($data) {
            $user = new User;
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->password = Hash::make($data['password']);
            $user->role = 'customer';
            $user->created_at = Carbon::now();
            $user->save();

            // Send verification code
            $this->emailVerificationService->sendVerificationCode($user);

            return [
                'user_id' => $user->id,
                'message' => 'Registration successful. Please check your email for verification code.'
            ];
        });

        return response()->json([
            'message' => $result['message'],
            'user_id' => $result['user_id'],
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

        if (! $result['success']) {
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

        if (! $user || ! Hash::check($data['password'], $user->password)) {
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

        //part of code that check if the vendor is approved

    
 // // Check if vendor is approved
        // if ($user->role === 'vendor') {
        //     $vendor = $user->vendor;
        //     if ($vendor && $vendor->approval_status === 'pending') {
        //         return response()->json([
        //             'message' => 'Your vendor account is pending admin approval.',
        //         ], 403);
        //     }

        //     if ($vendor && $vendor->approval_status === 'rejected') {
        //         return response()->json([
        //             'message' => 'Your vendor account has been rejected.',
        //         ], 403);
        //     }
        // }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'user' => $user,
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