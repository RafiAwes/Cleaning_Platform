<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
     public function run()
    {
        // Admin
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('11111111'),
                'role' => 'admin'
            ]
        );

        // Vendor
        User::firstOrCreate(
            ['email' => 'vendor@example.com'],
            [
                'name'     => 'Default Vendor',
                'password' => Hash::make('11111111'),
                'role'     => 'vendor'
            ]
        );

        // Customer
        User::firstOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name'     => 'Default Customer',
                'password' => Hash::make('11111111'),
                'role'     => 'customer'
            ]
        );
    }
}
