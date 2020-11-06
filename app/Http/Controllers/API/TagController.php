<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class TagController extends Controller
{
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function random_tags()
    {
        $random_tags = Tag::all()->random(10);
        if($random_tags === null){
            return response()->json([
                'message' => 'data null',
            ],200);
        }
        return response()->json([
            'message' => 'success',
            'random_tags' => $random_tags,
        ],200);
    }

    public function news_base_tag($id)
    {   
        $get_tag = Tag::find($id);
        if($get_tag) {
            $news_base_tag = $get_tag->news()->orderBy('date_publish', 'DESC')->with('categories')->paginate(Config::get('app._PAGINATION_OFFSET'));
        }
        return response()->json([
            'news_base_tag' => $news_base_tag,
        ], 200);
    }
}
