<?php

use App\Http\Controllers\API\Admin\AdminApprovalNewsController;
use App\Http\Controllers\API\Admin\AuthorAccountController;
use App\Http\Controllers\API\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\API\Admin\HomepageController;
use App\Http\Controllers\API\Admin\NewsAuthorController;
use App\Http\Controllers\API\Admin\NewsController as AdminNewsController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\NewsController;
use App\Http\Controllers\API\TagController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Admin\SchedulerController;

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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

// ---------------------- CLIENT ----------------------
// News
Route::post('news/list_news_cate', [NewsController::class, 'list_news_cate']);
Route::post('news/list_news_tag', [NewsController::class, 'list_news_tag']);
Route::get('news/latest_news_from_all_categories', [NewsController::class, 'latest_news_from_all_categories']);
Route::get('news/hot_news_in_week', [NewsController::class, 'hot_news_in_week']);
Route::get('news/feature_news', [NewsController::class, 'feature_news']);
Route::post('search/news_base_keyword', [NewsController::class, 'news_base_keyword']);
//---//
Route::post('news/init_cate_news', [CategoryController::class, 'init_cate_news']);
Route::post('news/change_cate_news', [CategoryController::class, 'change_cate_news']);
Route::post('news/hover_change_header_cate_news', [CategoryController::class, 'hover_change_header_cate_news']);

//Categories
Route::get('categories/news_basein_cate/{category}', [CategoryController::class, 'news_basein_cate']);
Route::get('categories/get_all_categories', [CategoryController::class, 'get_all_categories']);

//Tags
Route::get('tags/random_tags', [TagController::class, 'random_tags']);
Route::get('tags/news_base_tag/{tag}', [TagController::class, 'news_base_tag']);

//Resouces
Route::apiResource('/categories', CategoryController::class);
Route::apiResource('/news', NewsController::class);

// ---------------------- CLIENT ----------------------


// ---------------------- ADMIN ----------------------

//Session
Route::group([
    'prefix' => 'auth',
], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('user-profile', [AuthController::class, 'userProfile']);
    Route::post('update_avatar', [AuthController::class, 'update_avatar']);
    Route::post('update_info', [AuthController::class, 'update_info']);

});

//News Author
Route::group([
    'prefix' => 'news_author',
], function (){
    Route::get('init_available_data', [NewsAuthorController::class, 'init_available_data']);
    Route::get('parent_category_res_child_category/{parent_category}', [NewsAuthorController::class, 'parent_category_res_child_category']);
    Route::get('get_all_news', [NewsAuthorController::class, 'get_all_news']);
    Route::post('upload_news', [NewsAuthorController::class, 'upload_news']);
    Route::post('update/{news}', [NewsAuthorController::class, 'update']);
    Route::post('search_author_news', [NewsAuthorController::class, 'search_author_news']);

});

// Admin
Route::group(['prefix' => 'admin', 'middleware' => 'api'], function () {
    //homepage 
    Route::get('category_with_amount_of_news', [HomepageController::class, 'category_with_amount_of_news']);
    // manage automation for update;
    Route::post('set_schedule', [SchedulerController::class, 'set_schedule']);
    Route::get('get_schedule', [SchedulerController::class, 'get_schedule']);
    // manage category
    Route::apiResource('categories', AdminCategoryController::class);
    Route::post('search_categories', [AdminCategoryController::class, 'search_categories']);
    Route::get('get_parent_category', [AdminCategoryController::class, 'get_parent_category']);
    Route::post('update_category', [AdminCategoryController::class, 'update_category']);
    Route::post('destroy_category', [AdminCategoryController::class, 'destroy_category']);
    // manage approval news
    Route::get('get_all_approval_news', [AdminApprovalNewsController::class, 'get_all_approval_news']);
    Route::post('approving_news', [AdminApprovalNewsController::class, 'approving_news']);
    Route::post('search_approval_news', [AdminApprovalNewsController::class, 'search_approval_news']);
    //manage author account
    Route::get('get_all_author_account', [AuthorAccountController::class, 'get_all_author_account']);
    Route::post('block_or_active', [AuthorAccountController::class, 'block_or_active']);
    Route::post('search_author_account', [AuthorAccountController::class, 'search_author_account']);
    Route::post('create_author_account', [AuthorAccountController::class, 'create_author_account']);
    //manage news
    Route::get('get_white_list_news', [AdminNewsController::class, 'get_white_list_news']);
    Route::post('search_white_list_news', [AdminNewsController::class, 'search_white_list_news']);
    Route::post('push_to_pending', [AdminNewsController::class, 'push_to_pending']);
    
});


// ---------------------- ADMIN ----------------------

