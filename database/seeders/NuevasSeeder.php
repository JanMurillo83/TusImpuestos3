<?php

namespace Database\Seeders;

use DateTime;
use DateTimeZone;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NuevasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
    }
}
