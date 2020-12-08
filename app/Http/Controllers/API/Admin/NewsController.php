<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class NewsController extends Controller
{
    public function get_white_list_news()
    {
        $white_list_news = News::with('tags')->with('categories')
        ->orderBy('created_at', 'DESC')->paginate(Config::get('app._PAGINATION_OFFSET'));
        return response()->json([
            'white_list_news' =>$white_list_news
        ], 200);
    }

    public function search_white_list_news(Request $request)
    {
        $keyword = $request->keyword;
        $like_keyword = '%'.$keyword.'%';
        $search_white_list_news = News::where('title', 'like', $like_keyword)->orWhere('summary', 'like', $like_keyword)
        ->orderBy('created_at', 'DESC')
        ->with('categories')->with('tags')
        ->paginate(Config::get('app._PAGINATION_OFFSET'));
        return response()->json([
            'search_white_list_news' => $search_white_list_news,
        ], 200);
    }

    public function push_to_pending(Request $request)
    {
       $news = News::find($request->news_id);
       $news->status = intval($request->status);
       $news->save();
       return response()->json([
           'news' => $news->load('tags')->load('categories'),
       ], 200);
    }
}
