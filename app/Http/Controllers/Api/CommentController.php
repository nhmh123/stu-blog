<?php

namespace App\Http\Controllers\Api;



use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;


class CommentController extends Controller
{
    public function index(Request $request)
    {
        $commentQuery = Comment::with('user', 'post');

        if ($request->keyword) {
            $keyword = $request->keyword;
            $commentQuery->where(function ($query) use ($keyword) {
                $query->whereRaw("comment_content LIKE N'%{$keyword}%'")
                    ->orWhereHas('user', function ($query) use ($keyword) {
                        $query->where('username', 'LIKE', "%{$keyword}%");
                    })
                    ->orWhereHas('post', function ($query) use ($keyword) {
                        $query->whereRaw("post_title LIKE N'%{$keyword}%'");
                    });
            });
        }

        if ($request->sortBy && in_array($request->sortBy, ['user_id', 'post_id', 'parent_comment_id', 'created_at', 'updated_at'])) {
            $sortBy = $request->sortBy;
        } else {
            $sortBy = 'created_at';
        };

        if ($request->sortOrder && in_array($request->sortOrder, ['asc', 'desc'])) {
            $sortOrder = $request->sortOrder;
        } else {
            $sortOrder = 'asc';
        }

        $comments = $commentQuery->orderBy($sortBy, $sortOrder)->paginate(10);
        if ($comments->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'No comment found',
            ], 404);
        } else {
            return response()->json([
                'status' => 200,
                'comments' => $comments,
            ], 200);
        }
    }
    public function storeComment(Request $request, $postId)
    {
        $post = Post::where('id', $postId)->with('comments')->first();
        if ($post == null) {
            return response()->json([
                'status' => 404,
                'message' => 'Post not found',
            ], 404);
        } else {
            $validator = Validator::make($request->all(), [
                'comment_content' => 'required|string',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => $validator->errors(),
                ], 422);
            } else {
                $comment = new Comment();
                $comment->comment_content = htmlspecialchars($request->comment_content);
                $comment->user_id = Auth::user()->id;
                $comment->post_id = $post->id;
                $comment->save();

                return response()->json([
                    'status' => 200,
                    'message' => 'Comment added successfully',
                    'post' => $post,
                ], 200);
            }
        }
       
    }
    public function replyComment(Request $request, $postId, $parentCommentId)
    {
        $post = Post::where('id', $postId)->with(['comments' => function ($query) {
            $query->whereNull('parent_comment_id')->with('comments','user');
        }])->first();
        if ($post == null) {
            return response()->json([
                'status' => 404,
                'message' => 'Post not found',
            ], 404);
        } else {
            $validator = Validator::make($request->all(), [
                'comment_content' => 'required|string',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => $validator->errors(),
                ], 422);
            } else {
                $comment = new Comment();
                $comment->comment_content = htmlspecialchars($request->comment_content);
                $comment->user_id = Auth::user()->id;
                $comment->post_id = $post->id;
                $comment->parent_comment_id = $parentCommentId;
                $comment->save();

                return response()->json([
                    'status' => 200,
                    'message' => 'Reply successfully',
                    'post' => $post,
                ], 200);
            }
        }
    }
    public function likeComment(Request $request, $commentLikedId){
        $comment = Comment::find($commentLikedId);
        $comment->like = $comment->like + 1;
        $comment->save(); // Lưu lại thay đổi
        return response()->json([
            'status'=>200,
            'message'=>'Liked',
            'comment'=>$comment,
        ],200);
    }
    public function deleteComment($commentId)
    {
        $comment = Comment::find($commentId);

        if ($comment == null) {
            return response()->json([
                'status' => 404,
                'message' => 'Comment not found'
            ], 404);
        } else {
            $comment->delete();
        }

        return response()->json([
            'status' => 200,
            'message' => 'Comment deleted successfully'
        ], 200);
    }
    public function deleteMultiComment(Request $request){
        if ($request->has('commentIds')) {
            $commentIds = $request->input('commentIds');
            $comments = Comment::with('post','user')->whereIn('id', $commentIds)->delete();
            return response()->json([
                'status' => 200,
                'comment_deleted' => $comments,
                'message' => 'Comments deleted successfully'
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'No comment selected',
            ], 404);
        }
    }
}
