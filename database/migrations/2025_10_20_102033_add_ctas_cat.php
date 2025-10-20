<?php

use App\Models\CatCuentas;
use Filament\Facades\Filament;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $teams = \Illuminate\Support\Facades\DB::table('teams')->where('id','!=',39);
        foreach ($teams->get() as $team) {
            $data = [
                ['codigo' => '21611000', 'nombre' => 'Retenciones de IMSS a los trabajadores', 'acumula' => '21600000', 'tipo' => 'D', 'naturaleza' => 'A', 'csat' => '216.11', 'team_id' => $team->id],
                ['codigo' => '21612000', 'nombre' => 'Descuento credito Infonavit', 'acumula' => '21600000', 'tipo' => 'D', 'naturaleza' => 'A', 'csat' => '216.11', 'team_id' => $team->id],
                ['codigo' => '21613000', 'nombre' => 'Descuentos prestamo empresa', 'acumula' => '21600000', 'tipo' => 'D', 'naturaleza' => 'A', 'csat' => '216.11', 'team_id' => $team->id],
            ];
            CatCuentas::insert($data);
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
