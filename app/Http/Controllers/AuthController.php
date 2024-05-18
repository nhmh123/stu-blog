<?php

namespace App\Http\Controllers;

use App\Mail\UserResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function registerUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:50|regex:/^[a-zA-Z0-9]+$/u|regex:/^\S+$/|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]+$/',
            'phone' => 'required|string|max:11|regex:/^[0-9]{0,11}$/',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors(),
            ], 422);
        } else {
            $user = new User();
            $user->username = $request->username;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->phone = $request->phone;
            $user->avatar = url('/public/images/default-avatar.jpg');
            $user->url = Str::slug($request->username);
            $user->save();
            return response()->json([
                'message' => 'Registration successfully',
                'user' => $user,
            ], 201);
        }
    }

    // public function loginUser(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'email_or_username' => 'required|string',
    //         'password' => 'required|string',
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => 422,
    //             'message' => $validator->errors(),
    //         ], 422);
    //     } else {
    //         $user = User::where('email', $request->email_or_username)
    //             ->orWhere('username', $request->email_or_username)
    //             ->first();
    //         if ($user == null) {
    //             return response()->json([
    //                 'status' => 404,
    //                 'message' => 'Account not found',
    //             ], 404);
    //         } else {
    //             $isValid = Hash::check($request->password, $user->password);
    //             if (!$isValid) {
    //                 return response()->json([
    //                     'statusCode' => 401,
    //                     'message' => 'Incorrect password/username.',
    //                 ], 401);
    //             }

    //             $token = Str::random(40);
    //             $sessionId = Hash::make($token);
    //             //$request->session()->put($sessionId,  $user->id);
    //             if ($request->has('remember_me') && $request->remember_me == true) {
    //                 //Cookie::make('sessionId', $sessionId, 1);

    //                 return response()->json([
    //                     'statusCode' => 200,
    //                     'data' => $user,
    //                 ]);
    //             }

    //            // Cookie::make('sessionId', $sessionId, 0);
    //             return response()->json([
    //                 'statusCode' => 200,
    //                 'data' => $user,
    //             ], 200);
    //         }
    //     }
    // }

    public function loginUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors(),
            ], 422);
        } else {
            $credentials = [
                'email' => $request->email,
                'password' => $request->password,
            ];

            $remember = $request->remember ? true : false;
            
            if (Auth::attempt($credentials, $remember)) {
                // if($remember){
                //     Cookie::make('remember_token_custom',Auth::user()->getRememberToken(),10);
                // }

                return response([
                    'status' => 200,
                    'message' => 'login successfully',
                    'user' => Auth::user(),
                    'remember_token' => Auth::user()->getRememberToken(),
                ], 200);
            } else {
                return response([
                    'status' => 422,
                    'message' => 'email or password incorrect',
                ], 422);
            }
        }
    }

    // public function logout()
    // {
    //     $sessionId = Cookie::get('sessionId');
    //     $value = DB::table('sessions')->select('*')->where('id', $sessionId)->get()->first();

    //     //$value = session($sessionId);
    //     $value = json_decode($value->payload);
    //     if ($sessionId != null) {
    //         if ($value != null) {
    //             session()->forget($sessionId);
    //         }
    //     }

    //     Cookie::forget('sessionId');

    //     return response()->json([
    //         'status' => 200,
    //         'message' => 'Logout successfully',
    //         $sessionId,
    //         $value
    //     ], 200);
    // }

    public function logout(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthorized',
            ], 401);
        }

        Auth::user()->setRememberToken(null);
        Auth::logout();

        return response()->json([
            'status' => 200,
            'message' => 'logout successfully',
        ], 200);
    }

    public function forgotPassword(Request $request)
    {
        $email = $request->input('email');
        $user = User::where('email', $email)->first();
        if ($user == null) {
            return response()->json([
                'status' => 404,
                'message' => "User not found",
            ], 404);
        } else {
            $token = Hash::make($email);
            Cache::put($email, $token, now()->addSeconds(50));
            $message = $request->root() . '/api/reset-password?token=' . $token . '&email=' . $email;
            $baseUrl = $request->root();
            $data = [
                'url' => $baseUrl,
                'message' => $message,
            ];
            Mail::to($email)->send(new UserResetPassword($data));
            return response()->json([
                'statusCode' => 200,
                'message' => $message,
            ]);
        }
    }

    public function resetPassword(Request $request)
    {
        $token = $request->input('token');
        $email = $request->input('email');
        $validator = Validator::make($request->all(), [
            'password' => 'required|confirmed|string|min:6|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]+$/',
            'password_confirmation' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors(),
            ], 422);
        } else {
            $newpassword = $request->password;
            $value = Cache::get($email);
            if ($token != $value || $token == null) {
                return response()->json([
                    'message' => 'Invalid/Expired token.',
                    'statusCode' => 400
                ]);
            } else {
                User::where('email', $email)->update([
                    'password' => Hash::make($newpassword),
                ]);
                Cache::forget($email);
                return response()->json([
                    'status' => 200,
                    'message' => 'Reset password successfully',
                ], 200);
            };
        }
    }
}
