<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class HomepageController extends Controller
{
    public function category_with_amount_of_news(){
        $parent_cate = Category::where('parent_id', null)->get()->except('description');
        foreach($parent_cate as $p){
            $p->sub_cate = Category::where('parent_id', $p->id)->get();
        }
        foreach($parent_cate as $p){
            foreach($p->sub_cate as $sub){
                $sub->cate_news= Category::where('name', $sub->name)
                ->first()->news()->get()->count();
            }
        }
        return response()->json([
            'message' => 'success',
            'cate_news' => $parent_cate->toArray(),
        ], 200);
    }
}
