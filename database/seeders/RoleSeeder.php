<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default roles
        Role::create([
            'name' => 'admin',
            'description' => 'Administrator with full access',
        ]);

        Role::create([
            'name' => 'manager',
            'description' => 'Manager with limited administrative access',
        ]);

        Role::create([
            'name' => 'user',
            'description' => 'Regular user with basic access',
        ]);
    }
}
