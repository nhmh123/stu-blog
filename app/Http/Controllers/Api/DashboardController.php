<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Models\Comment;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $post_count = Post::all()->count();
        $category_count = Category::all()->count();
        $comment_count = Comment::all()->count();
        $comment_list = Comment::limit(3);
        $user_count = User::all()->count();
        $view_all_comment_link = route('admin.comments');
        return response()->json([
            'user' => $user,
            'post_count' => $post_count,
            'category_count' => $category_count,
            'comment_count' => $comment_count,
            'comment_list' => $comment_list,
            'user_count' => $user_count,
            'view_all_comment_link' => $view_all_comment_link,
        ]);
    }
    public function search(Request $request)
    {
        if ($request->has('keyword')) {
            $keyword = $request->input('keyword');
        }

        $categoriesData = Category::where('cat_title', 'like', "%$keyword%")->get();
        $postData = Post::where('post_title', 'like', "%$keyword%")
            ->orWhere('post_content', 'like', "%$keyword%")->get();
        $userData = User::where('username', 'like', "%$keyword%")
            ->where('username','!=',Auth::user()->username)
            ->orWhere('email', 'like', "%$keyword%")->get();

        $data = [
            'categories' => $categoriesData,
            'posts' => $postData,
            'users' => $userData,
        ];

        if (empty($data['categories']) && $postData->isEmpty() && $userData->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'No record found',
            ], 404);
        } else {
            return response()->json([
                'status' => 200,
                'data' => $data,
            ], 200);
        }
    }
}
