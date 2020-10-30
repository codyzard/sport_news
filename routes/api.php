<?php

use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\NewsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('news/list_news_cate', [NewsController::class, 'list_news_cate']);
Route::post('news/list_news_tag', [NewsController::class, 'list_news_tag']);
Route::get('news/latest_news_from_all_categories', [NewsController::class, 'latest_news_from_all_categories']);
Route::get('news/hot_news_in_week', [NewsController::class, 'hot_news_in_week']);
Route::get('news/feature_news', [NewsController::class, 'feature_news']);


Route::post('news/init_cate_news', [CategoryController::class, 'init_cate_news']);
Route::post('news/change_cate_news', [CategoryController::class, 'change_cate_news']);
Route::post('news/hover_change_header_cate_news', [CategoryController::class, 'hover_change_header_cate_news']);
Route::get('news/news_basein_cate/{category}', [CategoryController::class, 'news_basein_cate']);

//Resouces
Route::apiResource('/categories', CategoryController::class);
Route::apiResource('/news', NewsController::class);