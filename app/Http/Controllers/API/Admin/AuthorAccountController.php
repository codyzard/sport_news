<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthorAccountController extends Controller
{

    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['get_all_author_account']]);
    }

    public function get_all_author_account()
    {
        $list_author_account = User::where('role', 0)->with('user_info')->orderBy('updated_at', 'DESC')
        ->paginate(Config::get('app._PAGINATION_OFFSET'));
        return response()->json([
            'list_author_account' => $list_author_account,
        ], 200);
    }

    public function block_or_active(Request $request)
    {
        $author_account = User::find($request->user_id);
        $value = intval($request->value);
        if($author_account === null)
        {
            return response()->json(404);
        }
        $author_account->activation = $value;
        $author_account->save();
        return response()->json([
            'author_account' => $author_account->load('user_info'),    
        ], 200);
    }

    public function search_author_account(Request $request)
    {
        $keyword = $request->keyword;
        $like_keyword = '%'.$keyword.'%';
        $search_author_account = User::where([['name', 'like', $like_keyword], ['role', 0]])
        ->orwhere([['email', 'like', $like_keyword], ['role', 0]])
        ->with('user_info')
        ->paginate(Config::get('app._PAGINATION_OFFSET'));
        return response()->json([
            'search_author_account' => $search_author_account,
        ], 200);
    }

    public function create_author_account(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|between:2,100',
                'email' => 'required|string|email|max:100|unique:users',
                'password' => 'required|string|min:6',
            ]);
            if($validator->fails()){
                return response()->json($validator->errors()->toJson(), 400); 
            }
            $news_author_account = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'email_verified_at' => now(),
                'remember_token' => Str::random(10)
            ]);
            return response()->json([
                'news_author_account' => $news_author_account
            ] ,200);
        }catch(Exception $e){
            echo $e->getMessage();
        }
    }
}
