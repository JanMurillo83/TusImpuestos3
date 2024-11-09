<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use DateTime;
use DateTimeZone;

class Cvesatseeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $date = new DateTime('now', new DateTimeZone('America/Mexico_City'));
        DB::table('cvesats')->truncate();
        $csvFile = fopen(public_path('Fseeders/CveSAT.csv'), "r");
        $firstline = true;
        while (($data = fgetcsv($csvFile, 2000, ",")) !== FALSE) {
        if (!$firstline) {

            DB::table('cvesats')->insert([
                'clave' => $data['0'],
                'descripcion' => $data['1'],
                'created_at' => $date,
                'updated_at' => $date
            ]);
        }
            $firstline = false;
        }
        fclose($csvFile);
    }
}
