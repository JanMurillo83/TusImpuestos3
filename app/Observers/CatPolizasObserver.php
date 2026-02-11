<?php

namespace App\Observers;

use App\Models\CatPolizas;
use App\Models\AuditoriaPoliza;
use Illuminate\Support\Facades\Auth;

class CatPolizasObserver
{
    /**
     * Handle the CatPolizas "created" event.
     */
    public function created(CatPolizas $catPolizas): void
    {
        $this->registrarAuditoria($catPolizas, 'crear');
    }

    /**
     * Handle the CatPolizas "updated" event.
     */
    public function updated(CatPolizas $catPolizas): void
    {
        $this->registrarAuditoria($catPolizas, 'modificar', $catPolizas->getOriginal());
    }

    /**
     * Registra la auditoría de una póliza
     */
    private function registrarAuditoria(CatPolizas $catPolizas, string $accion, ?array $datosAnteriores = null): void
    {
        $user = Auth::user();

        // Si no hay usuario autenticado, intentar usar sistema
        if (!$user) {
            $userId = 1; // Usuario sistema por defecto
            $userName = 'Sistema';
            $userEmail = 'sistema@tusimpuestos.com';
        } else {
            $userId = $user->id;
            $userName = $user->name;
            $userEmail = $user->email;
        }

        // Detectar origen
        $origen = 'Desconocido';
        if (request()->is('admin/*') || request()->is('*/livewire/*')) {
            $origen = 'Filament';
        } elseif (request()->is('api/*')) {
            $origen = 'API';
        } elseif (app()->runningInConsole()) {
            $origen = 'Comando/Consola';
        } elseif (request()->ajax()) {
            $origen = 'AJAX';
        } else {
            $origen = 'Web';
        }

        AuditoriaPoliza::create([
            'poliza_id' => $catPolizas->id,
            'accion' => $accion,
            'user_id' => $userId,
            'user_name' => $userName,
            'user_email' => $userEmail,
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => $catPolizas->getAttributes(),
            'origen' => $origen,
            'fecha_hora' => now(),
        ]);
    }
}
