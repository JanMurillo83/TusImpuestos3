<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Team::create([
            'name' => 'Tus Impuestos',
            'taxid' => 'TUSIMPUESTOS',
        ]);
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@tusimpuestos.com',
            'password'=>Hash::make('admin'),
            'team_id'=>1,
        ]);
        DB::table('team_user')->insert([
            'team_id'=>1,
            'user_id'=>1
        ]);
        $this->call(Cvesatseeder::class);
        $this->call(UnidProdSeeder::class);
    }
}
