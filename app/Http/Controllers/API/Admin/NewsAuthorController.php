<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\News;
use App\Models\Tag;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class NewsAuthorController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['init_available_data', 'parent_category_res_child_category']]);
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
        $news = News::find($id);
        $news->title = $request->title;
        if ($request->hasFile('title_img')) {
            $uploadedFileUrl = Cloudinary::upload($request->file('title_img')->getRealPath())->getSecurePath();
            $news->title_img = $uploadedFileUrl;
        }
        $news->summary = $request->summary;
        $news->html_content = $request->content;
        $news->hot_or_nor = $request->hot_or_nor;
        $news->save();
        $news->categories()->detach();
        $category_arr = array(intval($request->parent_category), intval($request->child_category));
        $news->categories()->attach($category_arr);
        $tag_attach = $this->get_tag_arr($request->tags);
        $news->tags()->detach();
        $news->tags()->attach($tag_attach);
        return response()->json([
            'success' => 'upload news sucess',
            'news' => $news,
        ], 200);
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

    public function init_available_data()
    {
        $parent_category = Category::where('parent_id', null)->get();

        return response()->json([
            'parent_category' => $parent_category,
        ], 200);
    }

    public function parent_category_res_child_category($id)
    {
        $child_categories = Category::where('parent_id', $id)->get();

        return response()->json([
            'child_categories' => $child_categories,
        ], 200);
    }

    public function get_all_news(Request $request)
    {   
        $user = auth()->user();
        $news = $user->news()->with('categories')->with('tags')->orderBy('updated_at', 'DESC')->paginate(Config::get('app._PAGINATION_ADMIN_OFFSET'));
        return response()->json([
            'author_news' => $news,
        ], 200);
    }

    public function upload_news(Request $request)
    {
        $user = auth()->user();
        if ($request->hasFile('title_img')) {
            $uploadedFileUrl = Cloudinary::upload($request->file('title_img')->getRealPath())->getSecurePath();
        }
        $news = new News;
        $news->title = $request->title;
        $news->title_img = $uploadedFileUrl;
        $news->summary = $request->summary;
        $news->html_content = $request->content;
        $news->date_publish = now();
        $news->hot_or_nor = $request->hot_or_nor;
        $user->news()->save($news);
        $category_arr = array(intval($request->parent_category), intval($request->child_category));
        $news->categories()->attach($category_arr);
        $tag_arr = $this->get_tag_arr($request->tags);
        $news->tags()->attach($tag_arr);
        return response()->json([
            'success' => 'upload news sucess',
            'news' => $news,
        ], 200);
    }

    public static function get_tag_arr($arr_name)
    {
        $tags = explode(',', $arr_name);
        $tag_arr = [];
        foreach ($tags as $t) {
            $tag = Tag::where('name', $t)->first();
            if ($tag) array_push($tag_arr, $tag->id);
            else {
                $tag = Tag::create(['name' => $t]);
                array_push($tag_arr, $tag->id);
            }
        }
        return $tag_arr;
    }

    public function search_author_news(Request $request)
    {
        $keyword = $request->keyword;
        $like_keyword = '%'.$keyword.'%';
        $user = auth()->user();
        $news = $user->news();
        $search_author_news = $news->where([['title', 'like', $like_keyword], ['user_id', $user->id]])->orWhere([['summary', 'like', $like_keyword], ['user_id', $user->id]])
        ->with('categories')->with('tags')->orderBy('updated_at', 'DESC')
        ->paginate(5);
        return response()->json([
            'search_author_news' => $search_author_news,
        ], 200);
    }
}
