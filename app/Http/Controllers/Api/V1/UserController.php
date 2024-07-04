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

            DB::commit();
            return $this->sendResponse('User registered successfully', new AuthResource($user));
        } catch (Exception $exception) {
            return $this->sendError('Failed to register user', [], 500);
        }
    }

    public function updateProfile(Request $request, int $userId)
    {
        $user = User::findOrFail($userId);

        $request->validate([
            'first_name' => ['required', 'string', 'max:25'],
            'last_name' => ['required', 'string', 'max:25'],
            'address' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        DB::beginTransaction();
        try {

            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->address = $request->address;

            if ($request->hasFile('image')) {
                $previousImage = $user->image_path;
                Storage::disk('local')->delete($previousImage);

                $image = $request->file('image');
                $imageName = $this->normalizeFileName($image->getClientOriginalName());
                $imagePath = 'user_images/' . $imageName;

                $image->storeAs('user_images', $imageName, 'local');

                $imageName = basename($imagePath);

                $user->image_name = $imageName;
                $user->image_path = $imagePath;
            }

            $user->save();
            DB::commit();
            return $this->sendResponse('Profile updated successfully', new UserResource($user));
        } catch (Exception $exeption) {
            DB::rollBack();
            return $this->sendError('Failed to update profile', [], 500);
        }
    }

    public function changePassword(Request $request, int $userId)
    {
        $request->validate([
            'old_password' => ['required'],
            'new_password' => ['required', 'confirmed'],
            'new_password_confirmation' => ['required']
        ]);

        $user = User::findOrFail($userId);

        if (!Hash::check($request['old_password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid old password'
            ], 401);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return $this->sendResponse('Password change successfully');
    }

    public function destroy(int $userId)
    {
        try {
            $user = User::findOrFail($userId);

            // Delete user's image if it exists
            if ($user->image_name && $user->image_path) {
                Storage::delete('user_images/' . $user->image_name);
            }

            $user->delete();

            return $this->sendResponse('User deleted successfully');
        } catch (Exception $exeption) {
            $this->sendError($exeption);
        }
    }
}
