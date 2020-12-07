<?php

namespace App\Http\Controllers\API\Admin;

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
        // $categories = Category::where('parent_id', '!=', null)->paginate(Config::get('app._PAGINATION_OFFSET'));
        $categories = Category::withCount('news')->paginate(Config::get('app._PAGINATION_OFFSET'));
        return response()->json([
            'message' => 'success',
            'categories' => $categories,
        ], 200);
    }

    public function get_parent_category()
    {
        $parent_categories = Category::where('parent_id', null)->get(['id', 'name']);
        return response()->json([
            'message' => 'success',
            'parent_categories' => $parent_categories,
        ], 200);
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
        if ($validation->fails())
            return response()->json([
                'message' => $validation->errors()->first(),
            ], 200);

        if (Category::where('name', $request->name)->first())
            return response()->json([
                'message' => 'already have',
            ], 200);

        $category = Category::store_category($validation->validated());

        return response()->json([
            'message' => 'created success',
            'category' => $category,
        ], 201);
    }

    public function update_category(Request $request)
    {
        $category = Category::find($request->category_id);
        if ($category === null) {
            return response()->json([
                'message' => 'category not found',
            ], 200);
        }
        $validation = $this->validation($request);
        if ($validation->fails()) {
            return response()->json([
                'message' => $validation->errors()->first(),
                'category_update' => $category,
            ], 200);
        }
        $parent_category_id = Category::where('parent_id', null)->get()->pluck('id')->toArray();
        if(!in_array($request->parent_id, $parent_category_id)){
            return response()->json([
                'message' => 'parent category is not exists',
                'category_update' => $category,
            ], 200);
        }
        $category->name = $request->name;
        $category->description = $request->description;
        $category->parent_id = $request->parent_id;
        $category->save();
        return response()->json([
            'message' => 'updated success',
            'category_update' => $category,
        ], 200);
    }

    public function destroy_category(Request $request)
    {
        $category = Category::find($request->category_id);
        if ($category === null) {
            return response()->json([
                'message' => 'category not found',
            ], 200);
        }
        $category->delete();
        return response()->json([
            'message' => 'category not found',
            'category_delete' => $category,
        ]);
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

    public function validation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:1|max:255',
            'description' => 'nullable',
            'parent_id' => 'required|integer'
        ]);
        return $validator;
    }

    public function search_categories(Request $request)
    {   
        $keyword = $request->keyword;
        $search_categories = Category::where('name', 'like', '%'.$keyword.'%')->withCount('news')
        ->paginate(Config::get('app._PAGINATION_OFFSET'));
        return response()->json([
            'search_categories' => $search_categories,
        ], 200);
    }
}
