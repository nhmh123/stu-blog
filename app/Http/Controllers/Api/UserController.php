<?php

namespace App\Http\Controllers\Api;



use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Role;

class UserController extends Controller
{
    public function getUsers(Request $request)
    {
        $userQuery = User::with('role');

        if ($request->keyword) {
            $userQuery->whereRaw("username LIKE N'%{$request->keyword}%'")
                ->where('id', '!=', Auth::user()->id)
                ->orWhere('email', 'LIKE', '%' . $request->keyword . '%');
        }

        if ($request->sortBy && in_array($request->sortBy, ['created_at', 'updated_by', 'email'])) {
            $sortBy = $request->sortBy;
        } else {
            $sortBy = 'username';
        };

        if ($request->sortOrder && in_array($request->sortOrder, ['asc', 'desc'])) {
            $sortOrder = $request->sortOrder;
        } else {
            $sortOrder = 'asc';
        }

        $users = $userQuery->orderBy($sortBy, $sortOrder)->paginate(10);

        if ($users->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'No user found'
            ], 404);
        } else {
            return response()->json([
                'status' => 200,
                'users_list' => $users,
            ], 200);
        }
    }
    public function getRole()
    {
        $roles = Role::all();
        if ($roles->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'No role found!',
            ], 404);
        } else {
            return response()->json([
                'status' => 200,
                'categogy_list' => $roles,
            ], 200);
        }
    }
    public function storeUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:50|regex:/^[a-zA-Z0-9]+$/u|regex:/^\S+$/|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]+$/',
            'phone' => 'required|string|max:11|regex:/^[0-9]{0,11}$/',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:7128',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'error' => $validator->errors()
            ], 422);
        } else {
            if ($request->has('avatar')) {
                $avatar = $request->avatar->getClientOriginalName();
                $avatarName = Str::slug(pathinfo($avatar, PATHINFO_FILENAME));
                $result = $request->file('avatar')->storeOnCloudinaryAs('avatars', $avatarName);
                $path = $result->getSecurePath();
                $publicId = $result->getPublicId();
            } else {
                $path =  url('/public/images/default-avatar.jpg');
                $publicId = null;
            }
            $user = new User();
            $user->username = $request->username;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->phone = $request->phone;
            $user->avatar = $path;
            $user->avatar_public_id = $publicId;
            $user->url = Str::slug($request->username);
            $user->role_id = $request->role;
            $user->save();

            return response()->json([
                'status' => 201,
                'message' => 'User created successfully'
            ], 201);
        }
    }
    public function editUser($user_id)
    {
        $user = User::with('role')->find($user_id);
        $roles = Role::all();
        if ($user == null) {
            return response()->json([
                'status' => 404,
                'message' => 'User not found',
            ], 404);
        } else {
            return response()->json([
                'status' => 200,
                'user' => $user,
                'role_list' => $roles,
            ], 200);
        }
    }
    public function updateUser(Request $request, $userId)
    {
        $user = User::with('role')->find($userId);
        if ($user == null) {
            return response()->json([
                'status' => 404,
                'message' => 'User not found',
            ], 404);
        } else {
            $validatorArray = [
                'username' => 'required|string|max:50|regex:/^[a-zA-Z0-9]+$/u|regex:/^\S+$/',
                'password' => 'nullable|confirmed|string|min:6|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]+$/',
                'password_confirmation' => 'nullable|string|min:6|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]+$/',
                'phone' => 'required|string|max:11|regex:/^[0-9]{0,11}$/',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:7128',
            ];

            if($request->username != $user->username){
                $validatorArray['username']=$validatorArray['username'].'|unique:users';
            }

            $validator = Validator::make($request->all(), $validatorArray);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'error' => $validator->errors()
                ], 422);
            } else {
                if ($request->hasFile('avatar')) {
                    $newAvatarName = Str::slug(pathinfo($request->avatar->getClientOriginalName(), PATHINFO_FILENAME));
                    $newPublicId = 'avatars/' . $newAvatarName;
                }

                $newPath = $user->avatar;
                $publicId = $user->avatar_public_id;

                if (isset($newPublicId)) {
                    if ($newPublicId != $publicId) {
                        $result = $request->file('avatar')->storeOnCloudinaryAs('posts', $newAvatarName);
                        $newPath = $result->getPath();
                        $publicId = $newPublicId;
                    }
                }

                if (!empty($request->password)) {
                    User::where('id', $userId)->update([
                        'username' => $request->username,
                        'password' => Hash::make($request->password),
                        'phone' => $request->phone,
                        'avatar' => $newPath,
                        'avatar_public_id' => $publicId,
                        'url' => Str::slug($request->username),
                    ]);

                    return response()->json([
                        'status' => 200,
                        'message' => 'User updated successfully'
                    ], 200);
                } else {
                    User::where('id', $userId)->update([
                        'username' => $request->username,
                        'phone' => $request->phone,
                        'avatar' => $newPath,
                        'avatar_public_id' => $publicId,
                        'url' => Str::slug($request->username),
                    ]);

                    return response()->json([
                        'status' => 200,
                        'message' => 'User updated successfully'
                    ], 200);
                }
            }
        }
    }
    public function deleteUser($userId)
    {
        if($userId == Auth::id()){
            return response()->json([
                'message'=>'Cannot delete yourself',
            ]);
        }

        $user = User::find($userId);

        if ($user == null) {
            return response()->json([
                'status' => 404,
                'message' => 'user not found'
            ], 404);
        } else {
            $user->delete();
        }

        return response()->json([
            'status' => 200,
            'message' => 'user deleted successfully'
        ], 200);
    }
    public function deleteMultiUser(Request $request){
        if ($request->has('userIds')) {
            $userIds = $request->input('userIds');
            $users = User::whereIn('id', $userIds)->delete();
            return response()->json([
                'status' => 200,
                'users_deleted' => $users,
                'message' => 'Users deleted successfully'
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'No user selected',
            ], 404);
        }
    }
    public function clientEditProfile()
    {
        $user = Auth::user();
        if ($user == null) {
            return response()->json([
                'status' => 404,
                'message' => 'User not found',
            ], 404);
        } else {
            return response()->json([
                'status' => 200,
                'user' => $user,
            ], 200);
        }
    }
    public function clientProfileUpdate(Request $request)
    {

        // $user = User::find($user_id);
        $user = Auth::user();
        if ($user == null) {
            return response()->json([
                'status' => 404,
                'message' => 'User not found',
            ], 404);
        } else {
            $validatorArray = [
                'username' => 'required|string|max:50|regex:/^[a-zA-Z0-9]+$/u|regex:/^\S+$/',
                'phone' => 'required|string|max:11|regex:/^[0-9]{0,11}$/',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:7128',
            ];

            if($request->username != $user->username){
                $validatorArray['username']=$validatorArray['username'].'|unique:users';
            }

            $validator = Validator::make($request->all(), $validatorArray);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'error' => $validator->errors()
                ], 422);
            } else {
                if ($request->hasFile('avatar')) {
                    $newAvatarName = Str::slug(pathinfo($request->avatar->getClientOriginalName(), PATHINFO_FILENAME));
                    $newPublicId = 'avatars/' . $newAvatarName;
                }

                $newPath = $user->avatar;
                $publicId = $user->avatar_public_id;

                if (isset($newPublicId)) {
                    if ($newPublicId != $publicId) {
                        $result = $request->file('avatar')->storeOnCloudinaryAs('posts', $newAvatarName);
                        $newPath = $result->getSecurePath();
                        $publicId = $newPublicId;
                    }
                }

                User::where('id', $user->id)->update([
                    'username' => $request->username,
                    'phone' => $request->phone,
                    'avatar' => $newPath,
                    'avatar_public_id' => $publicId,
                    'url' => Str::slug($request->username),
                    'role_id' => $user->role_id == 1 ? 1 : 2,
                ]);

                return response()->json([
                    'status' => 200,
                    'message' => 'User updated successfully'
                ], 200);
            }
        }
    }
    public function clientChangePassword(Request $request)
    {
        $user = Auth::user();

        if ($user == null) {
            return response()->json([
                'status' => 404,
                'message' => 'User not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string|max:50',
            'new_password' => 'required|confirmed|string|min:6|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]+$/',
            'new_password_confirmation' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors(),
            ], 422);
        }

        $oldPassword = $request->old_password;
        if (!Hash::check($oldPassword, $user->password)) {
            return response()->json([
                'status' => 401,
                'message' => 'Incorrect password.'
            ], 401);
        } else {
            User::where('id', $user->id)->update([
                'password' => Hash::make($request->new_password),
            ]);
            return response()->json([
                'status' => 200,
                'message' => 'Change password successfully! Please login with new password',
                'logout_url' => route('logout'),
            ], 200);
        }
    }
}
