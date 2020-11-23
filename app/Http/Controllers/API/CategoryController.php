<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validation = $this->validation($request);
        if($validation->fails()){
            return response()->json([
                'message' => 'error with validation or null object',
            ], 400);
        }
        $check = Category::where('name', $request->name);
        if($check->count() > 0)
        return response()->json([
            'message' => 'have existed',
        ],200);
        $category = new Category;
        $category->name = $request->name;
        $category->description = $request->description;
        $category->parent_id = $request->parent_id;
        $category->save();

        return response()->json([
            'message' => 'create success',
            'news' => $category,
        ], 201);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Category::find($id);
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
        $validation = $this->validation($request);
        if($validation->fails()){
            return response()->json([
                'message' => 'error with validation or null object',
            ], 400);
        }
        $check = Category::where('name', $request->name);
        if($check->count() > 0)
        return response()->json([
            'message' => 'have existed',
        ], 200);
        $category = Category::find($id);
        $category->name = $request->name;
        $category->description = $request->description;
        $category->parent_id = $request->parent_id;
        $category->save();

        return response()->json([
            'message' => 'create success',
            'news' => $category,
        ], 201);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $category = Category::find($id);
        if($category == null){
            return response()->json([
                'message' => 'null object cannot delete',
            ], 400);
        }
        $category->news()->detach();
        $category->delete();
        return response()->json([
            'message' => 'deleted',
        ], 200);
    }
    public function validation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required'
        ]);
        return $validator;
    }

    public function get_all_categories()
    {
        $parent_cate = Category::where('parent_id', null)->get()->except('description');
        foreach($parent_cate as $p){
            $p->sub_cate = Category::where('parent_id', $p->id)->get();
        }
        foreach($parent_cate as $p){
            foreach($p->sub_cate as $sub){
                $sub->cate_news= Category::where('name', $sub->name)
                ->first()->news()->where('status', 1)->orderBy('date_publish', 'DESC')->limit(4)->get(
                )->toArray();
            }
        }
        return response()->json([
            'message' => 'success',
            'cate_news' => $parent_cate,
        ], 200);
    }

    public function change_cate_news(Request $request)
    {
        $category_name = $request->category_name;
        $request_cate = Category::where('name', $category_name)->first();
        $parent_cate = Category::where('id', $request_cate->parent_id)->first();
        if($request_cate === null){
            return response()->json([
                'message' => 'data null',
            ],200);
        }
        $respone_cate[$parent_cate->name] = $request_cate->news()->where('status', 1)->orderBy('date_publish', 'DESC')->limit(4)->get();
        return response()->json([
            'message' => 'success',
            'change_cate_news' => $respone_cate,
        ],200); 
    }
    public function init_cate_news()
    {
        $parent_categories = Category::where('parent_id', null)->get();
        $init_cate_news = array();
        foreach($parent_categories as $pc){
            $subcate = Category::where('parent_id', $pc->id)->first();
            $init_cate_news[$pc->name] = $subcate->news()->orderBy('date_publish', 'DESC')->limit(4)->get();
        }
        return response()->json([
            'message' => 'success',
            'init_cate_news' => $init_cate_news,
        ],200); 
    }
    public function hover_change_header_cate_news(Request $request)
    {
        $req_cate = Category::where('name', $request->category_name)->first();
        $res_news = $req_cate->news()->orderBy('date_publish', 'DESC')->limit(4)->get();
        return response()->json([
            'message' => 'success',
            'header_cate_news' => $res_news,
        ],200); 
    }

    public function news_basein_cate($id)
    {
        $get_cate = Category::find($id);
        if($get_cate) {
            $full_news = $get_cate->news()->orderBy('date_publish', 'DESC')->with('categories');
            $latest_news_basein_cate =  $get_cate->news()->orderBy('date_publish', 'DESC')
            ->with('categories')->get()->take(Config::get('app._TAKE_OFFSET'));
            $excluding_news_id = $latest_news_basein_cate->pluck('id');
            $news_basein_cate =  $get_cate->news()->where('status', 1)->whereNotIn('news_id', $excluding_news_id)->orderBy('date_publish', 'DESC')
            ->with('categories')->paginate(Config::get('app._PAGINATION_OFFSET'));
        }
        return response()->json([
            'news_basein_cate' => $news_basein_cate,
            'latest_news_basein_cate' =>$latest_news_basein_cate,
        ], 200);
    }
}
