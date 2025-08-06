<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Helpers\Helper;
use App\Mail\OtpMail;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    public $select;
    public function __construct()
    {
        parent::__construct();
        $this->select = ['id', 'name', 'email', 'avatar'];
    }

    public function forgotPassword(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        try {
            $email = $request->input('email');
            $user  = User::where('email', $email)->firstOrFail();

            // Generate OTP
            $otp = rand(1000, 9999);

            // Save OTP and expiry
            $user->otp = $otp;
            $user->otp_expires_at = Carbon::now()->addMinutes(60);
            $user->save();

            // Send OTP via mail (optional)
            // Mail::to($email)->send(new OtpMail($otp, $user, 'Reset Your Password'));

            return response()->json([
                'success' => true,
                'message' => 'OTP has been sent to your email address.',
                'otp' => $otp
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }



    public function resetPassword(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'email' => 'required|email|exists:users,email',
                'otp' => 'required|digits:4',
                'password' => 'required|string|min:6|confirmed',
            ]);

            // Find the user
            $user = User::where('email', $request->email)->first();

            // Check OTP and expiry
            if (!$user || $user->otp !== $request->otp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP code.',
                ], 401);
            }

            if (Carbon::now()->gt(Carbon::parse($user->otp_expires_at))) {
                return response()->json([
                    'success' => false,
                    'message' => 'OTP code has expired.',
                ], 403);
            }

            // Update password
            $user->password = Hash::make($request->password);
            $user->otp = null;
            $user->otp_expires_at = null;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
