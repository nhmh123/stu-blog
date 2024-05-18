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
use Illuminate\Support\Facades\Response;

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


            if (Auth::attempt($credentials, true)) {
                $user = Auth::user();
                // $token = $request->user()->createToken('token')->plainTextToken;
                // $cookie = cookie('jwt', $token, 60 * 24);

                return response([
                    'status' => 200,
                    'message' => 'login successfully',
                ], 200);
            } else {
                return response([
                    'status' => 422,
                    'message' => 'email or password incorrect',
                ], 422);
            }

            // $user = User::where('email',$request->email)->first();

            // if (Hash::check(request('password'),$user->getAuthPassword())) {
            //     return ['token' => $user->createToken(time())->plainTextToken];

            // return response([
            //     'status' => 200,
            //     'message' => 'login successfully',
            // ], 200)->withCookie($cookie);

            // } else {
            //     return response([
            //         'status' => 422,
            //         'message' => 'email or password incorrect',
            //     ], 422);
            // }
        }
    }


    // LOGOUT DÙNG SESSION
    // public function logout(Request $request)
    // {

    //     if (!Auth::check()) {
    //         return response()->json([
    //             'status' => 401,
    //             'message' => 'Unauthorized',
    //         ], 401);
    //     }

    //     Auth::logout();

    //     return response()->json([
    //         'status' => 200,
    //         'message' => 'logout successfully',
    //     ], 200);
    // }



    //LOGOUT DÙNG JWT
    public function logout(Request $request)
    {
        
        if (!Auth::check()) {
            return response()->json([
                'status' => 401,
                'message' => 'Unauthorized',
            ], 401);
        }

        $cookie = Cookie::forget('jwt');

        Auth::logout();

        return response()->json([
            'status' => 200,
            'message' => 'logout successfully',
        ], 200)->withCookie($cookie);
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
