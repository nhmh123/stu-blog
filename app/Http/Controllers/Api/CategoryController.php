<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CategoryController extends Controller
{
    public function getCategories(Request $request)
    {
        $categoryQuery = DB::table('categories');

        if ($request->keyword) {
            // $categoryQuery->where('cat_title', 'LIKE', "N'%" . $request->keyword . "%");
            $categoryQuery->whereRaw("cat_title LIKE N'%{$request->keyword}%'");
        }

        if ($request->sortBy && in_array($request->sortBy, ['created_at', 'updated_at', 'created_by', 'updated_by'])) {
            $sortBy = $request->sortBy;
        } else {
            $sortBy = 'cat_title';
        }

        if ($request->sortOrder && in_array($request->sortOrder, ['asc', 'desc'])) {
            $sortOrder = $request->sortOrder;
        } else {
            $sortOrder = 'asc';
        }

        $categories = $categoryQuery->orderBy($sortBy, $sortOrder)->paginate(10);

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
    public function addCategory()
    {
        //return view
    }
    public function storeCategory(Request $request)
    {
        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'cat_title' => 'required|string|unique:categories',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'error' => $validator->errors(),
            ], 422);
        } else {
            $category = new Category();
            $category->cat_title = $request->cat_title;
            $category->url = Str::slug($request->cat_title);
            $category->created_by = $user->username;
            $category->save();

            return response()->json([
                'status' => 200,
                'message' => 'Category created successfully',
                'category' => $category,
            ], 200);
        }
    }
    public function editCategory($catId)
    {
        // if ($request->catId) {
        //     $catId = $request->catId;
        // }

        $category = Category::find($catId);
        if ($category == null) {
            return response()->json([
                'status' => 404,
                'message' => 'Category not found',
            ], 404);
        } else {
            return response()->json([
                'status' => 200,
                'category' => $category,
            ], 200);
        }
    }
    public function updateCategory(Request $request, $cat_id)
    {
        $category = Category::find($cat_id);
        if ($category == null) {
            return response()->json([
                'status' => 404,
                'error' => 'Category not found!',
            ], 404);
        } else {
            $validator = Validator::make($request->all(), [
                'cat_title' => 'required|string|unique:categories',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'error' => $validator->errors(),
                ], 422);
            } else {
                Category::where('id', $cat_id)->update([
                    'cat_title' => $request->cat_title,
                    'url' => Str::slug($request->cat_title),
                    'updated_by' => Auth::user()->username,
                ]);

                $category = Category::find($cat_id);

                return response()->json([
                    'status' => 200,
                    'message' => 'Category updated successfully',
                    'category_after_update' => $category,
                ], 200);
            }
        }
    }
    public function deleteCategory($cat_id)
    {
        $category = Category::find($cat_id);

        if (!$category) {
            return response()->json([
                'status' => 404,
                'message' => 'Category not found'
            ], 404);
        } else {
            $category->delete();
        }

        return response()->json([
            'status' => 200,
            'message' => 'Category deleted successfully',
            'category' => $category,
        ], 200);
    }
    public function deleteMultiCategory(Request $request)
    {
        if ($request->has('catIds')) {
            $catIds = $request->input('catIds');
            $categories = Category::whereIn('id', $catIds)->delete();
            return response()->json([
                'status' => 200,
                'categories_deleted' => $categories,
                'message' => 'categories deleted successfully'
            ], 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'No category selected',
            ], 404);
        }
    }
    public function getClientCategories($catSlug)
    {
        $category = Category::where('cat_title', $catSlug)
            ->orWhere('url', $catSlug)
            ->first();

        if ($category == null) {
            return response()->json([
                'status' => 404,
                'message' => 'Category not found',
            ], 404);
        } else {
            $categoryId = $category->id;

            $postWithCategory = Post::where('cat_id', $categoryId)->with('category')->get();

            $postWithCategory->map(function ($post) {
                $post->created_at = Carbon::parse($post->created_at)->format('l, j M Y');
                $post->updated_at = Carbon::parse($post->created_at)->format('l, j M Y');
                $post->category->created_at = Carbon::parse($post->category->created_at)->format('l, j M Y');
                $post->category->updated_at = Carbon::parse($post->category->updated_at)->format('l, j M Y');
                return $post;
            });

            return response()->json([
                'posts_of_category' => $postWithCategory,
            ]);
        }
    }
    
}
