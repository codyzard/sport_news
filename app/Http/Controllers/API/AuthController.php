<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['login', 'register']]);
    }

    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (!$token = Auth::attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (auth()->user()->activation !== 1) {
            return response()->json(['error' => 'Account is block'], 401);
        }
        return $this->createNewToken($token);
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
        ));

        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
    }


    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'User successfully signed out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->createNewToken(auth()->refresh());
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile()
    {
        return response()->json(auth()->user());
    }

    public function update_avatar(Request $request)
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file')->getRealPath();
            $user = auth()->user();
            $user_info = null;
            if ($user->user_info === null) {
                $user_info = $user->user_info()->create();
            } else {
                $user_info = $user->user_info()->first();
            }
            $uploadedFileUrl = Cloudinary::upload($file, array(
                "resource_type" => "image",
                "upload_preset" => "yzqbrnqm"
            ))->getSecurePath();
            return response()->json([
                'message' => 'update avatar success',
                'user' => $user->load('user_info'),
            ], 200);
            $user_info->avatar_src = $uploadedFileUrl;
            $user_info->save();
            return response()->json([
                'message' => 'update avatar success',
                'user' => $user->load('user_info'),
            ], 200);
        }
        return response()->json([
            'message' => 'upload file failed',
        ], 200);
    }

    public function update_info(Request $request)
    {
        try {
            $user = auth()->user();
            $user_info = null;
            if ($user->user_info === null) {
                $user_info = $user->user_info()->create();
            } else {
                $user_info = $user->user_info()->first();
            }
            if ($request->name !== null) {
                $user->name = $request->name;
            }
            $user_info->gender = $request->gender;
            $user_info->birthday = $request->birthday ? now()->createFromFormat('Y-m-d', $request->birthday) : null;
            $user_info->address = $request->address;
            $user_info->phone = $request->phone;
            $user_info->save();
            $user->save();
            return response()->json([
                'message' => 'update info success',
                'user' => $user->load('user_info'),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'update info failed',
            ], 200);
        }
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 3600,
            'user' => auth()->user()->load('user_info'),
        ]);
    }
}
