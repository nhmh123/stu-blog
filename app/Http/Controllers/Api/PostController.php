<?php

namespace App\Http\Controllers\Api;



use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Post;
use App\Models\Category;
use App\Models\Comment;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;


class PostController extends Controller
{
    public function getPosts(Request $request)
    {
        $postQuery = Post::with('category');

        if ($request->keyword) {
            $postQuery->whereRaw("post_title LIKE N'%{$request->keyword}%'");
        }

        if ($request->sortBy && in_array($request->sortBy, ['created_at', 'created_by', 'updated_at', 'updated_by'])) {
            $sortBy = $request->sortBy;
        } else {
            $sortBy = 'post_title';
        };

        if ($request->sortOrder && in_array($request->sortOrder, ['asc', 'desc'])) {
            $sortOrder = $request->sortOrder;
        } else {
            $sortOrder = 'asc';
        }

        $posts = $postQuery->orderBy($sortBy, $sortOrder)->paginate(10);

        if ($posts->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'No post found'
            ], 404);
        } else {
            return response()->json([
                'status' => 200,
                'posts_list' => $posts,
            ], 200);
        }
    }

    public function createPost()
    {
        $categories = Category::all();
        if ($categories->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'No category found!',
            ], 404);
        } else {
            return response()->json([
                'status' => 200,
                'categogy_list' => $categories,
            ], 200);
        }
    }

    public function storePost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_title' => 'required|string',
            'post_content' => 'required|string',
            'post_thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif|max:7168',
            'cat_id' => 'required|exists:categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->errors(),
            ], 422);
        } else {
            $thumbnail = $request->post_thumbnail->getClientOriginalName();
            $thumbnailName = Str::slug(pathinfo($thumbnail, PATHINFO_FILENAME));
            $result = $request->file('post_thumbnail')->storeOnCloudinaryAs('posts', $thumbnailName);
            $path = $result->getPath();
            $publicId = $result->getPublicId();

            $post = new Post();
            $post->post_title = $request->post_title;
            $post->post_content = $request->post_content;
            $post->post_thumbnail = $path;
            $post->post_thumbnail_public_id = $publicId;
            $post->url = Str::slug($request->post_title);
            $post->created_by = Auth::user()->username;
            $post->updated_by = Auth::user()->username;
            $post->cat_id = $request->cat_id;
            $post->save();

            return response()->json([
                'status' => 200,
                'message' => 'Post created successfully'
            ], 200);
        }
    }

    public function editPost($postId)
    {
        // if($request->postId){
        //     $postId= $request->postId;
        // }
        $post = Post::with('category')->find($postId);
        $categories = Category::all();
        if ($post == null) {
            return response()->json([
                'status' => 404,
                'message' => 'Post not found',
            ], 404);
        } else {
            return response()->json([
                'status' => 200,
                'post' => $post,
                'categogy_list' => $categories,
            ], 200);
        }
    }

    public function updatePost(Request $request, $post_id)
    {
        $post = Post::with('category')->find($post_id);
        if ($post == null) {
            return response()->json([
                'status' => 404,
                'message' => 'Post not found',
            ], 404);
        } else {
            $validator = Validator::make($request->all(), [
                'post_title' => 'required|string',
                'post_content' => 'required|string',
                'post_thumbnail' => 'image|mimes:jpeg,png,jpg,gif|max:7168',
                'cat_id' => 'required|exists:categories,id',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => $validator->errors(),
                ], 422);
            } else {
                if ($request->hasFile('post_thumbnail')) {
                    $newThumbnailName = Str::slug(pathinfo($request->post_thumbnail->getClientOriginalName(), PATHINFO_FILENAME));
                    $newPublicId = 'posts/' . $newThumbnailName;
                }

                $newPath = $post->post_thumbnail;
                $publicId = $post->post_thumbnail_public_id;

                if (isset($newPublicId)) {
                    if ($newPublicId != $publicId) {
                        $result = $request->file('post_thumbnail')->storeOnCloudinaryAs('posts', $newThumbnailName);
                        $newPath = $result->getPath();
                        $publicId = $newPublicId;
                    }
                }

                Post::where('id', $post_id)->update([
                    'post_title' => $request->post_title,
                    'post_content' => $request->post_content,
                    'post_thumbnail' => $newPath,
                    'post_thumbnail_public_id' => $publicId,
                    'url' => Str::slug($request->post_title),
                    'updated_by' => Auth::user()->username,
                    'cat_id' => $request->cat_id,
                ]);
                return response()->json([
                    'status' => 200,
                    'message' => 'Post updated successfully'
                ], 200);
            }
        }
    }

    public function deletePost($post_id)
    {
        $post = Post::find($post_id);

        if ($post == null) {
            return response()->json([
                'status' => 404,
                'message' => 'Post not found'
            ], 404);
        } else {
            $post->delete();
        }

        return response()->json([
            'status' => 200,
            'message' => 'Post deleted successfully'
        ], 200);
    }

    public function deleteMultiPost(Request $request)
    {
        if ($request->has('postIds')) {
            $postIds = $request->input('postIds');
            $posts = Post::whereIn('id', $postIds)->delete();
            return response()->json([
                'status' => 200,
                'post_deleted' => $posts,
                'message' => 'Posts deleted successfully'
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'No post selected',
            ], 404);
        }
    }

    public function getClientPostDetail($postSlug)
    {
              
        $post = Post::where('url', $postSlug)->with(['comments' => function ($query) {
            $query->whereNull('parent_comment_id')->with('comments','user','comment.user');
        }])->first();

        // $post = Post::where('url',$postSlug)
        // ->with([
        //     'user.id,username',
        //     'comment.replies',
        //     'user:id,username',
        //     'comments.user:id,username',
        //     'comments.replies.user:id,username',
        //     'comments.replies.replies.user:id,username',
        //     'comments.replies.replies.replies.user:id,username'])
        // ->first();

        // $postComment = commentDataTree($post['comemnts']);
        // $post = Post::where('url', $postSlug)->first();

        // $post = Post::where('url',$postSlug)->with('comments')->first();
        
        if ($post) {
            $comments = $post->comments;
            $nestedReplies = getNestedRepliesWithUser($comments);
            $post->comments = $nestedReplies;
        }
    

        return $post;

        // if ($post == null) {
        //     return response()->json([
        //         'status' => 404,
        //         'message' => 'Post not found',
        //     ], 404);
        // } else {
        //     return response()->json([
        //         'status' => 200,
        //         'post' => $post,
        //         // 'comments' => commentDataTree($post->comments)
        //     ], 200);
        // }
    }
}
