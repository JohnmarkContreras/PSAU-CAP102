<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller {
public function login(Request $r) {
    $r->validate(['email'=>'required|email','password'=>'required']);
    $user = User::where('email',$r->email)->first();
    if (!$user || !Hash::check($r->password,$user->password)) {
    return response()->json(['message'=>'Invalid credentials'],401);
    }
    $token = $user->createToken('mobile')->plainTextToken;
    return response()->json(['token'=>$token,'user'=>$user],200);
}
public function logout(Request $r) {
    $r->user()->tokens()->delete();
    return response()->noContent();
}
}