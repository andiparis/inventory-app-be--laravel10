<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
  public function register(Request $request)
  {
    try {
      $validation = Validator::make($request->all(), [
        'name'      => 'required|max:255',
        'email'     => 'required|email|max:255|unique:users',
        'password'  => 'required|min:8|max:255|confirmed',
        'role'      => 'required'
      ]);

      if ($validation->fails()) {
        return response()->json([
          'success' => false,
          'message' => $validation->errors(),
        ], 422);
      }

      $user = User::create([
        'name'      => $request->name,
        'email'     => $request->email,
        'password'  => bcrypt($request->password),
        'role'      => $request->role,
      ]);

      if ($user) {
        return response()->json([
          'success' => true,
          'message' => 'Registration successfull',
          'user'    => $user,
        ], 201);
      }

      return response()->json([
        'success' => false,
        'message' => 'Registration failed',
      ], 409);
    } catch (\Exception $e) {
      return response()->json(['error' => 'Internal server error'], 500);
    }
  }

  public function login(Request $request)
  {
    try {
      $validation = Validator::make($request->all(), [
        'email'     => 'required',
        'password'  => 'required',
      ]);

      if ($validation->fails()) {
        return response()->json([
          'success' => false,
          'message' => $validation->errors(),
        ], 422);
      }

      $credentials = $request->only('email', 'password');
      $token = JWTAuth::attempt($credentials);

      if (!$token) {
        return response()->json([
          'success' => false,
          'message' => 'Your email or password is incorrect',
        ], 401);
      }

      return response()->json([
        'success' => true,
        'user'    => auth()->user(),
        'token'   => $token,
      ], 200);
    } catch (\Exception $e) {
      return response()->json(['error' => 'Internal server error'], 500);
    }
  }

  public function logout(Request $request)
  {
    try {
      $removeToken = JWTAuth::invalidate(JWTAuth::getToken());

      if ($removeToken) {
        return response()->json([
          'success' => true,
          'message' => 'Logout was successful',
        ]);
      }

      return response()->json([
        'success' => false,
        'message' => 'Logout failed',
      ]);
    } catch (\Exception $e) {
      return response()->json(['error' => 'Internal server error'], 500);
    }
  }
}
