<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Image;
use App\Models\News;
use App\Models\Tag;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class NewsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return News::orderBy('date_publish', 'desc')->get();
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
        $news = new News;
        $news->title = $request->title;
        $news->title_img = $request->title_img;
        $news->summary = $request->summary;
        $news->content = $request->content;
        $news->date_publish =  now();
        $news->status = $request->status;
        $news->hot_or_nor = $request->hot_or_nor;
        $news->save();
        
        $this->add_images_tags_categories($request, $news);

        return response()->json([
            'message' => 'create success',
            'news' => $news,
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
        $news = News::find($id)->load('author')->load('tags')->load('categories')->load('images');
        if($news === null){
            return response()->json([
                'message' => 'data null'
            ], 200);
        };
        $news->view_count += 1;
        $news->save();
        return response()->json([
            'message' => 'success',
            'news_detail' => $news,
        ], 200);
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
        $news = News::find($id);
        if($validation->fails() || $news == null){
            return response()->json([
                'message' => 'error with validation or null object',
            ], 400);
        }        
        $news->title = $request->title;
        $news->title_img = $request->title_img;
        $news->summary = $request->summary;
        $news->content = $request->content;
        $news->status = $request->status;
        $news->hot_or_nor = $request->hot_or_not;
        // $news->date_publish =  now()->createFromFormat('Y-m-d', $request->date_publish, 'GMT+7');
        $news->save();
        
        $this->add_images_tags_categories($request, $news);

        return response()->json([
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
        $news = News::find($id);
        if($news == null){
            return response()->json([
                'message' => 'null object cannot delete',
            ], 400);
        }
        $news->categories()->detach();
        $news->tags()->detach();
        $news->delete();
        return response()->json([
            'message' => 'deleted',
        ], 200);
    }

    public function list_news_cate(Request $request)
    {
        $cate_news = Category::where(['name' => $request->category])->first()->news()->where('status', 1);
        if($cate_news->count() === 0)
        {
            return [];
        }
        return $cate_news->orderBy('date_publish', 'desc')->get([
            'title',
            'title_img',
            'summary',
            'view_count',
            'date_publish',
        ]);
    }

    public function list_news_tag(Request $request)
    {
        $tag_news = Tag::where('name', $request->tag)->first()->news();
        if($tag_news->count() === 0)
        {
            return [];
        }
        return $tag_news->get();
    }

    public function validation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|min:1|max:255',
            'title_img' => 'required|max:255',
            'summary' => 'required|min:3|',
            'status' => 'boolean',
            'hot_or_nor' => 'boolean',
            'date_publish' => 'required',
        ]);
        return $validator;
    }

    public function add_images_tags_categories(Request $request, $news)
    {
        $categories = [];
        $tags = [];
        $images = [];
        if(!empty($request->categories)){
            foreach($request->categories as $cate){
                //kiem tra xem cate co ton tai hay chua
                $cate_exist = Category::where('name', $cate)->first();
                if($cate_exist !== null &&
                    count(DB::select('select * from category_news where category_id = '.$cate_exist->id.' and news_id = '.$news->id)) === 0) 
                array_push($categories, $cate_exist->id);
            }
            $news->categories()->attach($categories);
        }   

        if(!empty($request->tags)){
            foreach($request->tags as $index=>$tag){
                $tag_exist = Tag::where('name', $tag)->first();
                if($tag_exist === null) $tag_exist = Tag::create([
                    'name' => $tag,
                ]);
                if(count(DB::select('select * from tag_news where tag_id = '.$tag_exist->id.' and news_id = '.$news->id)) === 0)
                array_push($tags, $tag_exist->id);
            }
            $news->tags()->attach($tags);
        }

        if(!empty($request->images)){
            foreach($request->images as $index=>$img){
                $img_exist = Image::where('src', $img)->first();
                if($img_exist === null) $img_exist = Image::create([
                    'src' => $img,
                    'description' => $request->images_des[$index] !== null? $request->images_des[$index] : null,
                ]);
                array_push($images, $img_exist);
            }
            $news->images()->saveMany($images);
        }
    }

    public function latest_news_from_all_categories()
    {
        $latest_news = News::where('status', 1)->orderBy('date_publish', 'DESC')->orderBy('view_count', 'DESC')->limit(6)->get();
        if ($latest_news->count() === 0){
            return response()->json([
                'message' => 'data null'
            ], 200);
        }
        return response()->json([
            'message' => 'success',
            'latest_news' => $latest_news,
        ], 200);
    }
    public function hot_news_in_week()
    {
        $hot_news = News::where('hot_or_nor', true)->where('status', 1)->whereBetween('date_publish', [now()->subWeeks(2), now()])
        ->orderBy('date_publish', 'DESC')->orderBy('view_count', 'DESC')->limit(5)->get();
        if ($hot_news->count() === 0){
            return response()->json([
                'message' => 'data null'
            ], 200);
        }
        return response()->json([
            'message' => 'success',
            'hot_news_in_week' => $hot_news,
        ], 200);
    }
    public function feature_news(){
        $feature_news = array();
        $bongda_anh = Category::where('name', 'Anh')->first()->news()->where('status', 1)->whereBetween('date_publish', [now()->subWeek(), now()])
                    ->orderBy('date_publish','DESC')->orderBy('view_count', 'DESC')->limit(1)->get();
                
        $bongda = Category::where('name', 'Bóng đá')->first()->news()->where('status', 1)->whereBetween('date_publish', [now()->subWeek(), now()])
                    ->orderBy('date_publish','DESC')->orderBy('view_count', 'DESC')->limit(1)->get();
        $thethao = Category::where('name', 'Thể thao')->first()->news()->where('status', 1)->whereBetween('date_publish', [now()->subMonths(12), now()])
        ->orderBy('date_publish','DESC')->orderBy('view_count', 'DESC')->limit(1)->get();
        $esports = Category::where('name', 'E-sports')->first()->news()->where('status', 1)->whereBetween('date_publish', [now()->subWeek(2), now()])
        ->orderBy('date_publish','DESC')->orderBy('view_count', 'DESC')->limit(1)->get();
        array_push($feature_news, $bongda_anh, $bongda, $thethao, $esports);
        return response()->json([
            'message' => 'success',
            'feature_news' => $feature_news,
        ], 200);
    }

    public function news_base_keyword(Request $request)
    {
        $keyword = $request->keyword;
        $news_base_search = News::where('title', 'like', '%'.$keyword.'%')
        ->where('status', 1)
        ->orWhere('summary','like','%'.$keyword.'%')
        ->orderBy('date_publish', 'DESC')
        ->paginate(Config::get('app._PAGINATION_OFFSET'));
        if($news_base_search){
            return response()->json([
                'news_base_search' => $news_base_search,
            ], 200);
        }
        return response()->json([
            'message' => 'data null',
        ],200);
    }
}
