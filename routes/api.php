<?php

use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\Api\Auth\UserController;
use App\Http\Controllers\Api\Auth\SocialLoginController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\FirebaseTokenController;
use App\Http\Controllers\Api\FollowerController;
use App\Http\Controllers\Api\Frontend\categoryController;
use App\Http\Controllers\Api\Frontend\ChallengeController;
use App\Http\Controllers\Api\Frontend\FaqController;
use App\Http\Controllers\Api\Frontend\HomeController;
use App\Http\Controllers\Api\Frontend\ImageController;
use App\Http\Controllers\Api\Frontend\PageController;
use App\Http\Controllers\Api\Frontend\PostController;
use App\Http\Controllers\Api\Frontend\SubcategoryController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\Frontend\SettingsController;
use App\Http\Controllers\Api\Frontend\SocialLinksController;
use App\Http\Controllers\Api\Frontend\SubscriberController;
use App\Http\Controllers\Api\OnetoOneChatCOntroller;
use App\Http\Controllers\Api\PostController as ApiPostController;
use App\Http\Controllers\Api\RiderVehicleController;
use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\Route;


//page
Route::get('/page/home', [HomeController::class, 'index']);

Route::get('/category', [categoryController::class, 'index']);
Route::get('/subcategory', [SubcategoryController::class, 'index']);

Route::get('/social/links', [SocialLinksController::class, 'index']);
Route::get('/settings', [SettingsController::class, 'index']);
Route::get('/faq', [FaqController::class, 'index']);

Route::post('subscriber/store', [SubscriberController::class, 'store'])->name('api.subscriber.store');

/*
# Post
*/
Route::middleware(['auth:api'])->controller(PostController::class)->prefix('auth/post')->group(function () {
    Route::get('/', 'index');
    Route::post('/store', 'store');
    Route::get('/show/{id}', 'show');
    Route::post('/update/{id}', 'update');
    Route::delete('/delete/{id}', 'destroy');
});

Route::get('/posts', [PostController::class, 'posts']);
Route::get('/post/show/{post_id}', [PostController::class, 'post']);

Route::middleware(['auth:api'])->controller(ImageController::class)->prefix('auth/post/image')->group(function () {
    Route::get('/', 'index');
    Route::post('/store', 'store');
    Route::get('/delete/{id}', 'destroy');
});

Route::get('dynamic/page', [PageController::class, 'index']);
Route::get('dynamic/page/show/{slug}', [PageController::class, 'show']);

/*
# Auth Route
*/
//register
Route::post('register', [RegisterController::class, 'register']);
Route::post('/verify-email', [RegisterController::class, 'verifyOtp']);
Route::post('/resend-otp', [RegisterController::class, 'ResendOtp']);
// Route::post('/verify-otp', [RegisterController::class, 'VerifyEmail']);
//login
Route::post('login', [LoginController::class, 'login'])->name('api.login');
//forgot password
Route::post('/forget-password', [ResetPasswordController::class, 'forgotPassword']);
Route::post('/otp-token', [ResetPasswordController::class, 'MakeOtpToken']);
Route::post('/reset-password', [ResetPasswordController::class, 'ResetPassword']);
//social login
Route::post('/social-login', [SocialLoginController::class, 'SocialLogin']);


Route::group(['middleware' => ['auth:api', 'api-otp']], function ($router) {
    Route::get('/refresh-token', [LoginController::class, 'refreshToken']);
    Route::post('/logout', [LogoutController::class, 'logout']);
    Route::get('/me', [UserController::class, 'me']);
    Route::get('/account/switch', [UserController::class, 'accountSwitch']);
    Route::post('/update-profile', [UserController::class, 'updateProfile']);
    Route::post('/update-avatar', [UserController::class, 'updateAvatar']);
    Route::delete('/delete-profile', [UserController::class, 'destroy']);
});

/*
# Firebase Notification Route
*/

Route::middleware(['auth:api'])->controller(FirebaseTokenController::class)->prefix('firebase')->group(function () {
    Route::get("test", "test");
    Route::post("token/add", "store");
    Route::post("token/get", "getToken");
    Route::post("token/delete", "deleteToken");
});

/*
# In App Notification Route
*/

Route::middleware(['auth:api'])->controller(NotificationController::class)->prefix('notify')->group(function () {
    Route::get('test', 'test');
    Route::get('/', 'index');
    Route::get('status/read/all', 'readAll');
    Route::get('status/read/{id}', 'readSingle');
});

/*
# Chat Route
*/

Route::middleware(['auth:api'])->controller(ChatController::class)->prefix('auth/chat')->group(function () {
    Route::get('/list', 'list');
    Route::post('/send/{receiver_id}', 'send');
    Route::get('/conversation/{receiver_id}', 'conversation');
    Route::get('/room/{receiver_id}', 'room');
    Route::get('/search', 'search');
    Route::get('/seen/all/{receiver_id}', 'seenAll');
    Route::get('/seen/single/{chat_id}', 'seenSingle');
});
Route::middleware(['auth:api'])->controller(RiderVehicleController::class)->prefix('bike')->group(function () {
    Route::get('/details', 'index');
    Route::post('/add', 'store');
    Route::post('/update', 'update');
});
Route::middleware(['auth:api'])->controller(App\Http\Controllers\Api\PostController::class)->prefix('post')->group(function () {
    Route::post('/list', 'index');
    Route::post('/add', 'store');
    Route::post('/update/{id}', 'update');
    Route::delete('/delete-post/{id}', 'destroy');
    Route::post('/like-unlike/{id}', 'like_unlike');
    Route::post('/comment/{id}', 'comment');
    Route::get('/comments/{id}', 'allcomment');
    Route::post('/comment/update/{id}', 'updateComment');
    Route::delete('/comment/delete/{id}', 'deleteComment');
    Route::get('/likes/{id}', 'wholikes');
    Route::post('/my-post/{id?}', 'mypost');
    Route::get('/popular-post/{id?}', 'popularpost');
    Route::get('/latest-post/{id?}', 'latestpost');
});
Route::middleware('auth:api')->prefix('user')->group(function () {
    Route::post('toggleFollow/{userId}', [FollowerController::class, 'toggleFollow']);
    Route::get('followers', [App\Http\Controllers\Api\FollowerController::class, 'followers']);
    Route::get('followings', [App\Http\Controllers\Api\FollowerController::class, 'followings']);
    Route::get('blocked-users', [App\Http\Controllers\Api\FollowerController::class, 'blockedUsers']);
    Route::post('block/{userId}', [App\Http\Controllers\Api\FollowerController::class, 'block']);
    Route::post('unblock/{userId}', [App\Http\Controllers\Api\FollowerController::class, 'unblock']);
});
Route::middleware('auth:api')->prefix('profile')->group(function () {
    Route::get('info/{userId}', [App\Http\Controllers\Api\UserProfileController::class, 'profile']);
    Route::get('friend-request', [App\Http\Controllers\Api\UserProfileController::class, 'requests']);
    Route::get('friends', [App\Http\Controllers\Api\UserProfileController::class, 'friends']);
    Route::post('/friend-request/send/{receiverId}', [App\Http\Controllers\Api\UserProfileController::class, 'send']);
    Route::post('/friend-request/accept/{id}', [App\Http\Controllers\Api\UserProfileController::class, 'accept']);
    Route::post('/friend-request/reject/{id}', [App\Http\Controllers\Api\UserProfileController::class, 'reject']);
});

Route::middleware(['auth:api'])->controller(ChallengeController::class)->prefix('challenge')->group(function () {
    Route::get('/list', 'index');
    Route::get('/my', 'myChallenges');
    Route::post('/store', 'store');
    Route::get('/edit/{id}', 'edit');
    Route::post('/update/{id}', 'update');
    Route::delete('/delete/{id}', 'destroy');
    Route::get('/show/{id}', 'show');

    //join challenge
    Route::post('/join/{challenge_id}', 'join');
});
Route::middleware(['auth:api'])->controller(OnetoOneChatCOntroller::class)->prefix('chat')->group(function () {
    Route::get('/users', [OnetoOneChatCOntroller::class, 'users']);
    Route::get('/{userId}', [OnetoOneChatCOntroller::class, 'messages']);
    Route::post('/send', [OnetoOneChatCOntroller::class, 'send']);
    Route::post('/read/{userId}', [OnetoOneChatController::class, 'markAsRead']);
    Route::get('/get/conversation', [OnetoOneChatController::class, 'conversation']);
   Route::post('update/notifications/{roomId}', [OnetoOneChatCOntroller::class, 'updateNotifications']);

});
/*
# CMS
*/

Route::prefix('cms')->name('cms.')->group(function () {
    Route::get('home', [HomeController::class, 'index'])->name('home');
});
