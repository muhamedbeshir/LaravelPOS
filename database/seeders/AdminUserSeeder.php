<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user if it doesn't exist
        $admin = User::where('email', 'admin@admin.com')->first();

        if (!$admin) {
            $admin = User::create([
                'name' => 'Admin',
                'email' => 'admin@admin.com',
                'username' => 'admin',
                'password' => 'admin123'  // Store password without hashing
            ]);
            
            // Assign admin role if it exists
            $adminRole = Role::where('name', 'admin')->first();
            if ($adminRole) {
                $admin->assignRole($adminRole);
            }
        }
    }
} 