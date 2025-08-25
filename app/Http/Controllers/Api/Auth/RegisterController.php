<?php

namespace App\Http\Controllers\Api\Auth;

use App\Events\RegistrationNotificationEvent;
use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use App\Notifications\RegistrationNotification;
use Illuminate\Support\Facades\DB;
use App\Traits\SMS;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{

    use SMS;

    public $select;
    public function __construct()
    {
        parent::__construct();
        $this->select = ['id', 'name', 'email', 'otp', 'avatar', 'otp_verified_at', 'last_activity_at', 'rider_type','otp_expires_at'];
    }

    public function register(Request $request)
    {
        try {
            // Validate inputs
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|unique:users,email',
                'name' => 'required|string|max:255',
                'rider_type' => 'required|string|max:255',
                'password' => 'required|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $otp = rand(1234, 4321);

            $user = new User();
            $user->email = $request->email;
            $user->name = $request->name;
            $user->password = Hash::make($request->password);
            $user->otp = $otp;
            $user->rider_type = $request->rider_type;
            $user->otp_expires_at = now()->addHour(1);
            $user->slug = $request->name . now();

            $user->assignRole('customer');
            $user->save();

            // Mail::to($user->email)->send(new OtpMail($user->otp, $user, 'Verify Your Email Address'));
            $user->makeHidden(['roles']);

            return response()->json([
                'status' => 'success',
                'message' => 'Registration successful. Please check your email and verify the OTP to activate your account before logging in.',
                'user' => $user,
                'expires_in' => auth('api')->factory()->getTTL() * 60
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:4', // assuming 4 digit OTP
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)
            ->select($this->select)
            ->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        }

        if ($user->otp !== $request->otp) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid OTP',
            ], 400);
        }

        if (now()->greaterThan($user->otp_expires_at)) {
            return response()->json([
                'status' => 'error',
                'message' => 'OTP expired, please request a new one',
            ], 400);
        }

        // Mark OTP as verified (example: clear OTP fields or set verified flag)
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->otp_verified_at = now();
        $user->save();
            $token = auth('api')->login($user);

        return response()->json([
            'status' => 'success',
            'message' => 'OTP verified successfully. You can now log in.',
            "data" => $user,
            'token' => $token
        ]);
    }


    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        }

        if ($user->email_verified_at !== null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email is already verified.',
            ], 400);
        }

        // Generate new OTP and update user
        $otp = rand(1234, 4321);
        $user->otp = $otp;
        $user->otp_expires_at = now()->addHour(1);
        $user->save();

        // Mail::to($user->email)->send(new OtpMail($otp, $user, 'Resend OTP Verification'));

        return response()->json([
            'status' => 'success',
            'message' => 'A new OTP has been sent to your email address.',
            'otp' =>  $otp,
        ], 200);
    }
}
