<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use DateTime;
use DateTimeZone;

class UnidProdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $date = new DateTime('now', new DateTimeZone('America/Mexico_City'));
        DB::table('unid_prods')->truncate();
        $csvFile = fopen(public_path('Fseeders/Unidades.csv'), "r");
        $firstline = true;
        while (($data = fgetcsv($csvFile, 2000, ",")) !== FALSE) {
        if (!$firstline) {

            DB::table('unid_prods')->insert([
                'unidad' => $data['0'],
                'descripcion' => $data['1'],
                'unidad_sat' => $data['2'],
                'atributo' => '',
                'created_at' => $date,
                'updated_at' => $date
            ]);
        }
            $firstline = false;
        }
        fclose($csvFile);
    }
}
