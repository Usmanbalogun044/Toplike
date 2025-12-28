<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register a new user.
     */
    public function register(Request $request)
    {
        Log::info('Register request received', ['ip' => $request->ip(), 'data' => $request->only(['username', 'email'])]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            Log::warning('Register validation failed', ['errors' => $validator->errors()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = $this->authService->register($request->all());

            Log::info('User registered successfully via controller', ['user_id' => $user->id]);

            return response()->json([
                'message' => 'User registered successfully! Please check your email for the verification code.',
                'email' => $user->email // Return email so frontend can prompt for OTP
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Registration exception', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify email with OTP.
     */
    public function verifyEmail(Request $request)
    {
        Log::info('Verify email request', ['email' => $request->email]);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            Log::warning('Verify email validation failed', ['errors' => $validator->errors()]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($this->authService->verifyOtp($user, $request->otp)) {
            // Auto login after verification
            $token = $user->createToken('auth_token')->plainTextToken;
            Log::info('Email verification successful via controller', ['user_id' => $user->id]);
            return response()->json([
                'message' => 'Email verified successfully.',
                'token' => $token,
                'user' => $user
            ], 200);
        }

        Log::warning('Email verification failed via controller', ['email' => $request->email]);
        return response()->json(['message' => 'Invalid or expired OTP.'], 400);
    }

    /**
     * Resend Verification OTP.
     */
    public function resendVerification(Request $request)
    {
        Log::info('Resend OTP request', ['email' => $request->email]);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email is already verified.'], 400);
        }

        try {
            $this->authService->sendVerificationOtp($user);

            Log::info('Resend OTP initiated successfully');

            return response()->json(['message' => 'Verification code resent successfully.'], 200);
        } catch (\Throwable $e) {
            Log::error('Resend OTP failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to resend OTP: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Login user.
     */
    public function login(Request $request)
    {
        Log::info('Login request received', ['email' => $request->email, 'ip' => $request->ip()]);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning('Login validation failed', ['errors' => $validator->errors()]);
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $result = $this->authService->login($request->only('email', 'password'));

        if (!$result) {
            Log::warning('Login failed: Invalid credentials response sent to user');
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        Log::info('Login response success sent');

        return response()->json([
            'message' => 'Login successful',
            'token' => $result['token'],
            'user' => $result['user']
        ], 200);
    }

    /**
     * Logout user.
     */
    public function logout(Request $request)
    {
        Log::info('Logout request', ['user_id' => $request->user()->id]);
        $request->user()->tokens->each(function ($token) {
            $token->delete();
        });

        return response()->json(['message' => 'Logged out successfully']);
    }
}
