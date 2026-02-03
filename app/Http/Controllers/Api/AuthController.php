<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'required',            
            'password' => 'required|min:6'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => strtolower($request->email),
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
            'role' => 1
        ]);
        
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);

    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required_without:phone|email',
            'phone' => 'required_without:email',
            'password' => 'required'
        ]);

        // Determine credentials based on whether email or phone was provided
        if ($request->filled('email')) {
            $credentials = $request->only('email', 'password');
            $user = User::where('email', $request->email)->first();
        } else {
            $credentials = $request->only('phone', 'password');
            $user = User::where('phone', $request->phone)->first();
        }

        if (!auth()->attempt($credentials)) {
            return response()->json(['message' => 'Invalid login details'], 401);
        }

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function passwordReset(Request $request)
    {
        $user = User::where('email',$request->email)->first();
       
        if(!$user){
            return response()->json(['message' => 'User not found'], 404); 
        }

        // 1. Generate the Code
        $length = 6;
        $code = random_int(10**($length - 1), 10**$length - 1);
        $otpCode = (string) $code;
        
        // 2. Send the Code to User's Email
        // Mail::to($user->email)->send(new OtpMail($otpCode));

        User::where('email', $request->email) ->update([
            'verify_otp' => $otpCode,
            'otp_created_at' => now()
        ]);
        // 3. Return the response

        return response()->json(['message' => 'OTP sent to email successfully','otp'=> $otpCode], 200);
           

    }

    public function resetPasswordVerify(Request $request)
    {
        $user = User::where('email',$request->email)->first();
       
        if(!$user){
            return response()->json(['message' => 'User not found'], 404); 
        }

        // Check if OTP is valid
        if ($user->verify_otp !== $request->otp) {
            return response()->json(['message' => 'Invalid OTP'], 400);
        }

        // Check if OTP is expired (valid for 10 minutes)
        $otpExpiryTime = now()->subMinutes(10);
        if ($user->otp_created_at < $otpExpiryTime) {
            return response()->json(['message' => 'OTP has expired'], 400);
        }
        
        return response()->json(['message' => 'OTP verified successfully'], 200);
    }

    public function updatePassword(Request $request)
    {
        if($request->new_password !== $request->confirm_password){
            return response()->json(['message' => 'Password and Confirm Password do not match'], 400);
        }
        $user = User::where('email',$request->email)->first();
       
        if(!$user){
            return response()->json(['message' => 'User not found'], 404); 
        }

        // Update the password
        $user->password = bcrypt($request->new_password);
        $user->verify_otp = null; // Clear the OTP
        $user->otp_created_at = null; // Clear the OTP creation time
        $user->save();

        return response()->json(['message' => 'Password updated successfully'], 200);
    }
    
}