<?php

namespace App\Services\Herramientas;

use App\Models\Clientes;
use App\Models\Inventario;
use App\Models\Proveedores;

class CatalogosResetService
{
    /**
     * @param array{inventario?:bool,clientes?:bool,proveedores?:bool} $purge
     * @return array{inventario?:int,clientes?:int,proveedores?:int}
     */
    public function purgeForTeam(int $teamId, array $purge): array
    {
        $counts = [];

        if (!empty($purge['inventario'])) {
            $counts['inventario'] = Inventario::query()->where('team_id', $teamId)->delete();
        }

        if (!empty($purge['clientes'])) {
            $counts['clientes'] = Clientes::query()->where('team_id', $teamId)->delete();
        }

        if (!empty($purge['proveedores'])) {
            $counts['proveedores'] = Proveedores::query()->where('team_id', $teamId)->delete();
        }

        return $counts;
    }
}
