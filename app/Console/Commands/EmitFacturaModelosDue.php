<?php

namespace App\Console\Commands;

use App\Filament\Clusters\AdmVentas\Resources\FacturaModelosResource;
use App\Models\FacturaModelo;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class EmitFacturaModelosDue extends Command
{
    protected $signature = 'facturas:modelos:emitir-debidas {--team=}';
    protected $description = 'Emite facturas modelo programadas cuya fecha de próxima emisión sea hoy o anterior';

    public function handle(): int
    {
        $teamId = $this->option('team');
        $hoy = Carbon::now()->toDateString();

        $query = FacturaModelo::query()->where('activa', true)
            ->whereNotNull('proxima_emision')
            ->whereDate('proxima_emision', '<=', $hoy);

        if ($teamId) {
            $query->where('team_id', $teamId);
            Filament::setTenant(app(\App\Models\Team::class)::find($teamId));
        }

        $count = 0; $errors = 0;
        $query->chunkById(50, function ($chunk) use (&$count, &$errors) {
            foreach ($chunk as $plantilla) {
                try {
                    FacturaModelosResource::emitirDesdePlantilla($plantilla);
                    $count++;
                } catch (\Throwable $e) {
                    $errors++;
                    $this->error('Error con plantilla ID '.$plantilla->id.': '.$e->getMessage());
                }
            }
        });

        $this->info("Facturas generadas: {$count}. Errores: {$errors}.");
        return self::SUCCESS;
    }
}
