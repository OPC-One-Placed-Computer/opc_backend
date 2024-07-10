<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $user = User::find(24);
        $user->assignRole("admin");

        $user = User::find(25);
        $user->assignRole("user");

        $user = User::find(26);
        $user->assignRole("super-admin");
    }
}
