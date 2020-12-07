<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class AuthorAccountController extends Controller
{

    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['get_all_author_account']]);
    }

    public function get_all_author_account()
    {
        $list_author_account = User::where('role', 0)->with('user_info')
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
}
