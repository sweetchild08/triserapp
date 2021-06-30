<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Profile;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);
        $user = User::where("username", $request->username)->first();
        if(!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials are incorrect.'],
            ]);
        }
        return response()->json(['user' => $user, 'token' => $user->createToken($user->username)->plainTextToken]);
    }

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'cpnum' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
                'username' => $validatedData['username'],
                'cpnum' => $validatedData['cpnum'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
            ]);
            if($user)
                Profile::create([
                    'name' => $validatedData['name'],
                    'user_id' => $user->id,
                ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
                    'access_token' => $token,
                    'token_type' => 'Bearer',
        ]);
    }
    public function logout()
    {
        $request->user()->currentAccessToken()->delete();
        return \response(['status'=>'successful','msg'=>'Log out successful']);
    }
    public function myAccount()
    {
        return \response([
            'status'=>'successful',
            'user'=> auth('sanctum')->user(),
            'profile'=> auth('sanctum')->user()->profile
        ]);
    }
    public function update(Request $request)
    {
        $user=auth('sanctum')->user();
        $data=[
            'username'=>$request->input('username'),
            'cpnum'=>$request->input('cpnum'),
            'email'=>$request->input('email'),
        ];
        if($request->input('password'))
            $data['password']=$request->input('password');
        $user->update($data);
        $user->profile->first()->update(['name'=>$request->input('name')]);
        return \response([
            'status'=>'successful',
            'msg'=> "Profile Updated Successfully",
        ]);
    }
}
