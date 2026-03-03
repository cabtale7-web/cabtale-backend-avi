<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check if the admin user already exists
        $adminEmail = 'admin@admin.com';
        
        if (!DB::table('users')->where('email', $adminEmail)->exists()) {
            DB::table('users')->insert([
                'id' => Uuid::uuid4(),
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => $adminEmail,
                'password' => Hash::make('12345678'), // safer than bcrypt()
                'user_type' => 'super-admin',
                'is_active' => true,
            ]);
        }
    }
}