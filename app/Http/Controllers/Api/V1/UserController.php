<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\UserResource;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\AuthResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class UserController extends BaseController
{
    public function index()
    {
        $users = User::all();

        return $this->sendResponse('Users successfully fetched', UserResource::collection($users));
    }

    public function destroy(int $userId)
    {
        try {
            $user = User::findOrFail($userId);

            if ($user->image_name && $user->image_path) {
                Storage::delete('user_images/' . $user->image_name);
            }

            $user->delete();

            return $this->sendResponse('User deleted successfully');
        } catch (Exception $exception) {
            return $this->sendError($exception->getMessage(), [], 500);
        }
    }

    public function register(Request $request)
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:25'],
            'last_name' => ['required', 'string', 'max:25'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $user->assignRole('user');

            DB::commit();
            return $this->sendResponse('User registered successfully', new AuthResource($user));
        } catch (Exception $exception) {
            DB::rollBack();
            return $this->sendError($exception->getMessage(), [], 500);
        }
    }
}
