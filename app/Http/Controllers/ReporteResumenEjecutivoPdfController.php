<?php

namespace App\Http\Controllers;

use App\Models\DatosFiscales;
use App\Models\ReporteResumenEjecutivo;
use App\Models\Team;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReporteResumenEjecutivoPdfController extends Controller
{
    public function __invoke(Request $request, string $tenantSlug)
    {
        $team = $this->resolveTeam($tenantSlug);
        $this->ensureTeamAccess($request->user(), $team);

        $periodo = $request->query('periodo');
        if (! $periodo) {
            abort(404, 'Periodo no especificado.');
        }

        $registro = ReporteResumenEjecutivo::where('tenant_id', $team->id)
            ->where('periodo', $periodo)
            ->latest()
            ->first();

        if (! $registro) {
            abort(404, 'No se encontró el reporte.');
        }

        $secciones = $this->parsearSecciones($registro->reporte);

        $logoData = null;
        $logoWidth = 160;
        $fiscales = DatosFiscales::where('team_id', $team->id)->first();
        if ($fiscales?->logo_ancho) {
            $logoWidth = (int) $fiscales->logo_ancho;
        }
        if (! empty($fiscales?->logo64)) {
            $logo64 = trim($fiscales->logo64);
            $logoData = Str::startsWith($logo64, 'data:image')
                ? $logo64
                : 'data:image/png;base64,' . $logo64;
        } elseif (! empty($fiscales?->logo)) {
            $logoPath = $this->resolverRutaLogo($fiscales->logo);
            if ($logoPath) {
                $logoData = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
            }
        }
        if (! $logoData) {
            $logoPath = public_path('images/MainLogoTR.png');
            if (! is_file($logoPath)) {
                $logoPath = public_path('images/MainLogo.png');
            }
            if (is_file($logoPath)) {
                $logoData = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
            }
        }

        $pdf = Pdf::loadView('reportes.resumen-ejecutivo-pdf', [
            'registro' => $registro,
            'secciones' => $secciones,
            'logo_data' => $logoData,
            'logo_width' => $logoWidth,
        ])->setPaper('letter', 'portrait');

        $filename = 'resumen-ejecutivo-' . $team->id . '-' . $registro->periodo . '.pdf';

        return $pdf->download($filename);
    }

    private function parsearSecciones(string $texto): ?array
    {
        $heads = [
            'Resumen Ejecutivo',
            'Indicadores Clave',
            'Análisis Financiero',
            'Análisis Comercial',
            'Hallazgos Estratégicos',
            'Recomendaciones Ejecutivas',
            'Conclusión General',
        ];

        $result = array_fill_keys($heads, '');
        $normalized = str_replace("\r\n", "\n", $texto);

        $current = null;
        foreach (explode("\n", $normalized) as $line) {
            $lineTrim = trim($line);
            if ($lineTrim === '') {
                continue;
            }
            $lineNorm = $this->normalizarEncabezado($lineTrim);
            foreach ($heads as $head) {
                if (strcasecmp($lineNorm, $head) === 0) {
                    $current = $head;
                    continue 2;
                }
            }
            if ($current) {
                $result[$current] .= ($result[$current] ? "\n" : '') . $lineTrim;
            }
        }

        $tieneContenido = false;
        foreach ($result as $contenido) {
            if (trim($contenido) !== '') {
                $tieneContenido = true;
                break;
            }
        }

        return $tieneContenido ? $result : null;
    }

    private function normalizarEncabezado(string $linea): string
    {
        $linea = preg_replace('/^[#\\-*\\s]+/', '', $linea) ?? $linea;
        $linea = rtrim($linea, ':');
        return trim($linea);
    }

    private function resolveTeam(string $tenantSlug): Team
    {
        if (ctype_digit($tenantSlug)) {
            $team = Team::find((int) $tenantSlug);
            if ($team) {
                return $team;
            }
        }

        $team = Team::where('taxid', $tenantSlug)->first();
        if (! $team) {
            abort(404);
        }

        return $team;
    }

    private function ensureTeamAccess(User $user, Team $team): void
    {
        if (! $user->teams()->where('teams.id', $team->id)->exists()) {
            abort(403);
        }
    }

    private function resolverRutaLogo(string $logo): ?string
    {
        $logo = ltrim($logo);
        $candidatos = [
            public_path($logo),
            public_path(ltrim($logo, '/')),
            storage_path('app/public/' . ltrim($logo, '/')),
        ];

        foreach ($candidatos as $ruta) {
            if (is_file($ruta)) {
                return $ruta;
            }
        }

        return null;
    }
}
