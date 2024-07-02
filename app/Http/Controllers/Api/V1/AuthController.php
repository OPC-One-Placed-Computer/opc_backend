<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\AuthResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthController extends BaseController
{
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:25'],
            'last_name' => ['required', 'string', 'max:25'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);



        return $this->sendResponse('User registered successfully', new AuthResource($user));
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->sendResponse('User logged in successfully', new AuthResource($user), [
            'token' => $token
        ]);
    }

    public function current_authentication ()
    {
        $user = auth()->user();

        if ($user){
            return $this->sendResponse('User authenticated', new AuthResource($user));
            } else {
            return response()->json(['message' => 'Unauthenticated User'], 401);
        }
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $request->user()->tokens()->delete();
        }

        return $this->sendResponse('Logged out succesfully');
    }

}
