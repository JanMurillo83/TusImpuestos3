<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Team;
use DateTime;
use DateTimeZone;
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
        $date = new DateTime('now', new DateTimeZone('America/Mexico_City'));
        $csvFile = fopen(public_path('Fseeders/Formas.csv'), "r");
        $firstline = true;
        while (($data = fgetcsv($csvFile, 200000, ",")) !== FALSE) {
        if (!$firstline) {

            DB::table('formas')->insert([
                'clave' => $data['0'],
                'descripcion' => $data['1'],
                'mostrar' => $data['2'],
                'created_at' => $date,
                'updated_at' => $date
            ]);
        }
            $firstline = false;
        }
        fclose($csvFile);
        $csvFile = fopen(public_path('Fseeders/Metodos.csv'), "r");
        $firstline = true;
        while (($data = fgetcsv($csvFile, 200000, ",")) !== FALSE) {
        if (!$firstline) {

            DB::table('metodos')->insert([
                'clave' => $data['0'],
                'descripcion' => $data['1'],
                'mostrar' => $data['2'],
                'created_at' => $date,
                'updated_at' => $date
            ]);
        }
            $firstline = false;
        }
        fclose($csvFile);
        $csvFile = fopen(public_path('Fseeders/Usos.csv'), "r");
        $firstline = true;
        while (($data = fgetcsv($csvFile, 200000, ",")) !== FALSE) {
        if (!$firstline) {

            DB::table('usos')->insert([
                'clave' => $data['0'],
                'descripcion' => $data['1'],
                'mostrar' => $data['2'],
                'created_at' => $date,
                'updated_at' => $date
            ]);
        }
            $firstline = false;
        }
        fclose($csvFile);
        $this->call(Cvesatseeder::class);
        $this->call(UnidProdSeeder::class);
        $this->call(RegimenesSeeder::class);
    }
}
