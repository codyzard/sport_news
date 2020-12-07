<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;

class AdminApprovalNewsController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    public function get_all_approval_news()
    {
        $user = auth()->user();
        if ($user->role !== 1) {
            return response()->json('Unauthorized', 401);
        }
        $approval_news = News::where('status', 0)->with('categories')->with('tags')->orderBy('created_at', 'ASC')->paginate(5);
        return response()->json([
            'message' => 'success',
            'approval_news' => $approval_news,
        ], 200);
    }

    public function approving_news(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 1) {
            return response()->json('Unauthorized', 401);
        }
        $news = News::find($request->news_id);
        $news->status = intval($request->status);
        $news->save();
        if ($news->status === 1) {
            return response()->json([
                'message' => 'accepted',
                'news' => $news,
            ], 200);
        } else if ($news->status === 2) {
            return response()->json([
                'message' => 'denied',
                'news' => $news,
            ], 200);
        }
        return $news;
    }
    
    public function search_approval_news(Request $request)
    {
        $keyword = $request->keyword;
        $like_keyword = '%'.$keyword.'%';
        $search_approval_news = News::where([['title', 'like', $like_keyword], ['status', '=', 0]])
        ->orWhere([['summary', 'like', $like_keyword], ['status', '=', 0]])
        ->with('categories')->with('tags')->orderBy('created_at', 'ASC')
        ->paginate(5);
        return response()->json([
            'search_approval_news' => $search_approval_news,
        ], 200);
    }
}
