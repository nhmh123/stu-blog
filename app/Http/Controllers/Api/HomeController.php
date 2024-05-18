<?php

namespace App\Http\Controllers\Api;



use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cookie;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Models\Comment;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
        }
        $user = null;

        $dataSearch = [];

        if ($request->keyword) {
            $keyword = $request->keyword;
            // $dataQuery->where(function ($query) use ($keyword) {
            //     $query->where('post_title', 'LIKE', '%' . $keyword . '%')
            //         ->orWhereHas('category', function ($query) use ($keyword) {
            //             $query->where('cat_title', 'LIKE', '%' . $keyword . '%');
            //         });
            // });
            $dataSearch['posts'] = Post::where('post_title', 'LIKE', '%' . $keyword . '%')->get();
            $dataSearch['categories'] = Category::where('cat_title', 'LIKE', '%' . $keyword . '%')->get();
        }

        $recentBlogPosts = DB::table('posts')
            ->join('categories', 'posts.cat_id', '=', 'categories.id')
            ->select('posts.*', 'categories.cat_title')
            ->orderBy('posts.created_at', 'desc')
            ->limit(4)
            ->get();

        $recentBlogPosts->map(function ($post) {
            $post->created_at = Carbon::parse($post->created_at)->format('l, j M Y');
            $post->updated_at = Carbon::parse($post->created_at)->format('l, j M Y');
            return $post;
        });

        if($request->has('perPage')){
            $perPage = $request->perPage;
        }else{
            $perPage=6;
        }
        $allBlogPosts = DB::table('posts')
            ->join('categories', 'posts.cat_id', '=', 'categories.id')
            ->select('posts.*', 'categories.cat_title')
            ->orderBy('posts.created_at', 'desc')
            ->paginate($perPage);

        $allBlogPosts->map(function ($post) {
            $post->created_at = Carbon::parse($post->created_at)->format('l, j M Y');
            $post->updated_at = Carbon::parse($post->created_at)->format('l, j M Y');
            return $post;
        });

        $allCategory = Category::all();

        $data = [
            'USER' => $user,
            'DATA_SEARCH' => $dataSearch,
            'RECENT_BLOG_POSTS' => $recentBlogPosts,
            'ALL_BLOG_POSTS' => $allBlogPosts,
            'ALL_CATEGORIES' => $allCategory,
        ];

        return response()->json($data);
    }
}
