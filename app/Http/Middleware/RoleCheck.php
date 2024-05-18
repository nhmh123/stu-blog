<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Role;

class RoleCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $userId=Auth::id();
        $user = User::find($userId);
        $userRole = $user->role_id;

        if ($userRole == 1) {
            return $next($request);
        }

        return response()->json([
            'status' => 403,
            'message' => 'You do not have permission to access this resource.',
        ], 403);
    }
}
