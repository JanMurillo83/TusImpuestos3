<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use DateTime;
use DateTimeZone;


class RegimenesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $date = new DateTime('now', new DateTimeZone('America/Mexico_City'));
        //DB::table('regimenes')->truncate();
        $csvFile = fopen(public_path('Fseeders/Regimenes.csv'), "r");
        $firstline = true;
        while (($data = fgetcsv($csvFile, 200000, ",")) !== FALSE) {
        if (!$firstline) {

            DB::table('regimenes')->insert([
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
