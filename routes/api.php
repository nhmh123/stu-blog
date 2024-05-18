<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
//Auth
Route::middleware('alreadyLoggedIn')->group(function () {
    Route::post('/register-user', [AuthController::class, 'registerUser']);
    Route::post('/login-user', [AuthController::class, 'loginUser']);
});
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

//Client
Route::get('/', [HomeController::class, 'index'])->name('home');
//categories-list view
Route::get('/category', function () {
    redirect(route('home'));
});
Route::get('/category/{cat_slug}', [CategoryController::class, 'getClientCategories']);

Route::get('/posts', function () {
    redirect(route('home'));
});
//detail-post view
Route::get('/posts/{post_slug}', [PostController::class, 'getClientPostDetail']);

//login-needed
Route::middleware('auth:sanctum')->group(function () {
    //post-comment
    Route::post('/posts/{post_id}/up-comment', [CommentController::class, 'storeComment']);
    //reply-comment
    Route::post('/posts/{post_id}/{comment_parent_id}/reply-comment', [CommentController::class, 'replyComment']);
    //like-comment
    Route::post('/posts/{comment_liked_id}/like',[CommentController::class,'likeComment']);

    Route::prefix('profile')->group(function () {
        //client-profile-update-view
        Route::get('/', [UserController::class, 'clientEditProfile']);
        //client-profile-update-action
        Route::post('/update', [UserController::class, 'clientProfileUpdate']);
        //client-profile-change-password-action
        Route::post('/change-password', [UserController::class, 'clientChangePassword']);
    });
});

//Admin
Route::middleware(['isLoggedIn', 'isAdmin'])->group(function () {
    Route::prefix('admin')->group(function () {
        Route::get('', [DashboardController::class, 'index']);
        Route::get('search', [DashboardController::class, 'search']);
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::get('dashboard/search', [DashboardController::class, 'search']);

        Route::prefix('categories')->group(function () {
            Route::get('/', [CategoryController::class, 'getCategories']);
            //add-new-category action
            Route::get('add-new-category', [CategoryController::class, 'addCategory']);
            Route::post('create-category', [CategoryController::class, 'storeCategory']);
            //update-category view
            Route::get('edit/{cat_id}', [CategoryController::class, 'editCategory']);
            //update-category action
            Route::post('update-cat/{cat_id}', [CategoryController::class, 'updateCategory']);
            //delete-cat action
            Route::delete('delete-cat/{cat_id}', [CategoryController::class, 'deleteCategory']);
            Route::delete('delete-multicat', [CategoryController::class, 'deleteMultiCategory']);
        });

        Route::prefix('blog-posts')->group(function () {
            //blog-posts view
            Route::get('/', [PostController::class, 'getPosts']);
            //add-new-post view
            Route::get('add-new-post', [PostController::class, 'createPost']);
            //add-new-post action
            Route::post('create-post', [PostController::class, 'storePost']);
            //update-post view
            Route::get('edit/{post_id}', [PostController::class, 'editPost']);
            //update-post action
            Route::post('update-post/{post_id}', [PostController::class, 'updatePost']);
            //delete-post action
            Route::delete('delete-post/{post_id}', [PostController::class, 'deletePost']);
            Route::delete('delete-multipost', [PostController::class, 'deleteMultiPost']);
        });

        Route::prefix('users')->group(function () {
            //user-list view
            Route::get('/', [UserController::class, 'getUsers']);
            //add-new-user view
            Route::get('get-role', [UserController::class, 'getRole']);
            //add-new-user action
            Route::post('create-user', [UserController::class, 'storeUser']);
            //edit-user view
            Route::get('edit/{user_id}', [UserController::class, 'editUser']);
            //edit-user action
            Route::post('update-user/{user_id}', [UserController::class, 'updateUser']);
            //delete-user action
            Route::delete('delete-user/{user_id}', [UserController::class, 'deleteUser']);
            Route::delete('delete-multiuser', [UserController::class, 'deleteMultiUser']);
        });

        Route::prefix('comments')->group(function () {
            Route::get('/', [CommentController::class, 'index'])->name('admin.comments');
            Route::delete('delete-comment/{comment_id}', [CommentController::class, 'deleteComment']);
            Route::delete('delete-multicomment', [CommentController::class, 'deleteMultiComment']);
        });
    });
});
