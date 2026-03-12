<?php

namespace App\Http\Controllers;

use App\Models\Clientes;
use App\Models\ComercialCanal;
use App\Models\ComercialMotivoGanada;
use App\Models\ComercialMotivoPerdida;
use App\Models\ComercialSegmento;
use App\Models\CotizacionActividad;
use App\Models\Cotizaciones;
use App\Models\CotizacionesPartidas;
use App\Models\Esquemasimp;
use App\Models\Facturas;
use App\Models\FacturasPartidas;
use App\Models\Inventario;
use App\Models\SeriesFacturas;
use App\Models\Team;
use App\Models\User;
use App\Exports\ComercialArraySheet;
use App\Exports\ComercialDashboardExport;
use App\Services\FacturaFolioService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ComercialDashboardController extends Controller
{
    public function index(Request $request, string $tenantSlug)
    {
        $team = $this->resolveTeam($tenantSlug);
        $this->ensureTeamAccess($request->user(), $team);

        return view('tiadmin.comercial-dashboard', [
            'tenant_slug' => $tenantSlug,
            'team_id' => $team->id,
        ]);
    }

    public function bootstrap(Request $request, string $tenantSlug): JsonResponse
    {
        $team = $this->resolveTeam($tenantSlug);
        $this->ensureTeamAccess($request->user(), $team);

        $user = $request->user();
        $isManager = $this->isManager($user);

        [$periodStart, $periodEnd] = $this->resolvePeriodRange($request, $team);

        $users = User::whereHas('teams', fn ($q) => $q->where('teams.id', $team->id))
            ->get(['id', 'name']);
        $usersById = $users->keyBy('id');

        $segments = ComercialSegmento::where('team_id', $team->id)
            ->where('activo', 1)
            ->orderBy('sort')
            ->get(['id', 'nombre']);
        $channels = ComercialCanal::where('team_id', $team->id)
            ->where('activo', 1)
            ->orderBy('sort')
            ->get(['id', 'nombre']);
        $winReasons = ComercialMotivoGanada::where('team_id', $team->id)
            ->where('activo', 1)
            ->orderBy('sort')
            ->get(['id', 'nombre']);
        $lossReasons = ComercialMotivoPerdida::where('team_id', $team->id)
            ->where('activo', 1)
            ->orderBy('sort')
            ->get(['id', 'nombre']);

        $quotesQuery = Cotizaciones::where('team_id', $team->id)
            ->whereBetween('fecha', [$periodStart, $periodEnd]);
        if (! $isManager) {
            $quotesQuery->where(function ($q) use ($user) {
                $q->where('created_by_user_id', $user->id)
                    ->orWhere(function ($q2) use ($user) {
                        $q2->whereNull('created_by_user_id')
                            ->where('nombre_elaboro', $user->name);
                    });
            });
        }
        $quotes = $quotesQuery->with(['actividades:id,cotizacion_id,tipo,fecha,resultado,proxima_accion,proxima_fecha'])
            ->get([
                'id', 'fecha', 'clie', 'nombre', 'created_by_user_id', 'nombre_elaboro',
                'segmento_id', 'canal_id', 'total', 'descuento_pct', 'estado_comercial',
                'probabilidad', 'cierre_estimado', 'vigencia_hasta', 'motivo_ganada_id',
                'motivo_perdida_id', 'observa',
            ]);

        $quoteIds = $quotes->pluck('id');
        $invoicesQuery = Facturas::where('team_id', $team->id)
            ->whereBetween('fecha', [$periodStart, $periodEnd]);
        if (! $isManager) {
            $invoicesQuery->where('created_by_user_id', $user->id);
        }
        $invoices = $invoicesQuery->get([
            'id', 'cotizacion_id', 'fecha', 'nombre', 'created_by_user_id',
            'segmento_id', 'canal_id', 'total', 'margen_pct', 'cobranza_pct',
            'motivo_ganada_id', 'observa',
        ]);

        $invoiceByQuote = $invoices->whereNotNull('cotizacion_id')->keyBy('cotizacion_id');

        $payloadQuotes = $quotes->map(function (Cotizaciones $q) use ($invoiceByQuote, $usersById) {
            $sellerId = $q->created_by_user_id ?: null;
            $sellerName = $sellerId && $usersById->has($sellerId)
                ? $usersById[$sellerId]->name
                : ($q->nombre_elaboro ?: 'Sin vendedor');

            $activities = $q->actividades->sortByDesc('fecha')->values()->map(function (CotizacionActividad $a) {
                return [
                    'type' => $a->tipo,
                    'date' => $a->fecha,
                    'result' => $a->resultado,
                    'next' => $a->proxima_accion,
                    'nextDate' => $a->proxima_fecha,
                ];
            });

            return [
                'id' => $q->id,
                'createdAt' => $q->fecha,
                'sellerId' => $sellerId,
                'sellerName' => $sellerName,
                'client' => $q->nombre,
                'segmentId' => $q->segmento_id,
                'channelId' => $q->canal_id,
                'total' => (float) $q->total,
                'discountPct' => (float) ($q->descuento_pct ?? 0),
                'status' => $q->estado_comercial ?? 'OPEN',
                'probability' => (float) ($q->probabilidad ?? 0.2),
                'closeEst' => $q->cierre_estimado,
                'validUntil' => $q->vigencia_hasta,
                'lostReasonId' => $q->motivo_perdida_id,
                'winReasonId' => $q->motivo_ganada_id,
                'notes' => $q->observa,
                'activities' => $activities,
                'invoiceId' => $invoiceByQuote->has($q->id) ? $invoiceByQuote[$q->id]->id : null,
            ];
        });

        $payloadInvoices = $invoices->map(function (Facturas $i) use ($usersById) {
            $sellerId = $i->created_by_user_id ?: null;
            $sellerName = $sellerId && $usersById->has($sellerId)
                ? $usersById[$sellerId]->name
                : 'Sin vendedor';

            return [
                'id' => $i->id,
                'quoteId' => $i->cotizacion_id,
                'sellerId' => $sellerId,
                'sellerName' => $sellerName,
                'client' => $i->nombre,
                'segmentId' => $i->segmento_id,
                'channelId' => $i->canal_id,
                'issuedAt' => $i->fecha,
                'total' => (float) $i->total,
                'marginPct' => (float) ($i->margen_pct ?? 0),
                'paidPct' => (float) ($i->cobranza_pct ?? 0),
                'winReasonId' => $i->motivo_ganada_id,
                'notes' => $i->observa,
            ];
        });

        return response()->json([
            'auth' => [
                'role' => $isManager ? 'MANAGER' : 'SELLER',
                'sellerId' => $user->id,
                'userId' => $user->id,
                'userName' => $user->name,
            ],
            'period' => [
                'from' => $periodStart->format('Y-m-d'),
                'to' => $periodEnd->format('Y-m-d'),
                'label' => $this->periodLabel($team, $periodStart),
            ],
            'sellers' => $users->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'goal' => 0,
            ]),
            'catalog' => [
                'segments' => $segments,
                'channels' => $channels,
                'winReasons' => $winReasons,
                'lossReasons' => $lossReasons,
            ],
            'quotes' => $payloadQuotes,
            'invoices' => $payloadInvoices,
        ]);
    }

    public function createQuote(Request $request, string $tenantSlug): JsonResponse
    {
        $team = $this->resolveTeam($tenantSlug);
        $this->ensureTeamAccess($request->user(), $team);
        $user = $request->user();

        $data = $request->validate([
            'client' => ['required', 'string', 'max:255'],
            'segmentId' => ['nullable', 'integer'],
            'channelId' => ['nullable', 'integer'],
            'total' => ['required', 'numeric', 'min:0'],
            'discountPct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'probability' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'closeEst' => ['nullable', 'date'],
            'validUntil' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $cliente = $this->resolveCliente($team->id, $data['client']);
        $esquema = Esquemasimp::where('team_id', $team->id)->first();
        if (! $esquema) {
            return response()->json(['error' => 'No hay esquema configurado para el team.'], 422);
        }

        $serie = SeriesFacturas::where('team_id', $team->id)
            ->where('tipo', SeriesFacturas::TIPO_COTIZACIONES)
            ->first();
        if (! $serie) {
            return response()->json(['error' => 'No hay serie de cotizaciones configurada.'], 422);
        }

        $folioData = SeriesFacturas::obtenerSiguienteFolio($serie->id);
        $total = (float) $data['total'];
        $discountPct = (float) ($data['discountPct'] ?? 0);
        $netTotal = max(0, $total * (1 - ($discountPct / 100)));

        $cotizacion = Cotizaciones::create([
            'team_id' => $team->id,
            'serie' => $folioData['serie'],
            'folio' => $folioData['folio'],
            'docto' => $folioData['docto'],
            'fecha' => Carbon::now(),
            'clie' => $cliente->id,
            'nombre' => $data['client'],
            'esquema' => $esquema->id,
            'subtotal' => $netTotal,
            'iva' => 0,
            'retiva' => 0,
            'retisr' => 0,
            'ieps' => 0,
            'total' => $netTotal,
            'observa' => $data['notes'] ?? null,
            'estado' => 'Activa',
            'metodo' => 'PUE',
            'forma' => '01',
            'uso' => 'G03',
            'condiciones' => 'CONTADO',
            'vendedor' => 0,
            'created_by_user_id' => $user->id,
            'nombre_elaboro' => $user->name,
            'estado_comercial' => 'OPEN',
            'probabilidad' => $data['probability'] ?? 0.2,
            'descuento_pct' => $discountPct,
            'segmento_id' => $data['segmentId'] ?? null,
            'canal_id' => $data['channelId'] ?? null,
            'cierre_estimado' => $data['closeEst'] ?? null,
            'vigencia_hasta' => $data['validUntil'] ?? null,
        ]);

        $item = $this->resolveServicioItem($team->id, $esquema->id);
        CotizacionesPartidas::create([
            'cotizaciones_id' => $cotizacion->id,
            'item' => $item->id,
            'descripcion' => 'Servicio comercial',
            'cant' => 1,
            'pendientes' => 1,
            'precio' => $netTotal,
            'subtotal' => $netTotal,
            'iva' => 0,
            'retiva' => 0,
            'retisr' => 0,
            'ieps' => 0,
            'total' => $netTotal,
            'unidad' => $item->unidad ?? 'H87',
            'cvesat' => $item->cvesat ?? '01010101',
            'costo' => $item->p_costo ?? 0,
            'clie' => $cliente->id,
            'team_id' => $team->id,
        ]);

        return $this->bootstrap($request, $tenantSlug);
    }

    public function updateQuote(Request $request, string $tenantSlug, Cotizaciones $quote): JsonResponse
    {
        $team = $this->resolveTeam($tenantSlug);
        $this->ensureTeamAccess($request->user(), $team);
        $user = $request->user();

        if ((int) $quote->team_id !== (int) $team->id) {
            return response()->json(['error' => 'Cotización no válida.'], 404);
        }
        if (! $this->isManager($user) && (int) $quote->created_by_user_id !== (int) $user->id) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        $data = $request->validate([
            'client' => ['nullable', 'string', 'max:255'],
            'segmentId' => ['nullable', 'integer'],
            'channelId' => ['nullable', 'integer'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'discountPct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'status' => ['nullable', 'string'],
            'probability' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'closeEst' => ['nullable', 'date'],
            'validUntil' => ['nullable', 'date'],
            'lostReasonId' => ['nullable', 'integer'],
            'winReasonId' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ]);

        if (!empty($data['client'])) {
            $quote->nombre = $data['client'];
        }

        if (isset($data['segmentId'])) {
            $quote->segmento_id = $data['segmentId'];
        }
        if (isset($data['channelId'])) {
            $quote->canal_id = $data['channelId'];
        }
        if (isset($data['total'])) {
            $quote->total = $data['total'];
        }
        if (isset($data['discountPct'])) {
            $quote->descuento_pct = $data['discountPct'];
        }
        if (isset($data['status'])) {
            $quote->estado_comercial = $data['status'];
        }
        if (isset($data['probability'])) {
            $quote->probabilidad = $data['probability'];
        }
        if (array_key_exists('closeEst', $data)) {
            $quote->cierre_estimado = $data['closeEst'];
        }
        if (array_key_exists('validUntil', $data)) {
            $quote->vigencia_hasta = $data['validUntil'];
        }
        if (array_key_exists('lostReasonId', $data)) {
            $quote->motivo_perdida_id = $data['lostReasonId'];
        }
        if (array_key_exists('winReasonId', $data)) {
            $quote->motivo_ganada_id = $data['winReasonId'];
        }
        if (array_key_exists('notes', $data)) {
            $quote->observa = $data['notes'];
        }

        if ($quote->estado_comercial === 'WON' && !Facturas::where('cotizacion_id', $quote->id)->exists()) {
            $quote->estado_comercial = 'NEGOTIATION';
        }

        if (in_array($quote->estado_comercial, ['LOST', 'EXPIRED'], true) && ! $quote->motivo_perdida_id) {
            $defaultLoss = ComercialMotivoPerdida::where('team_id', $team->id)->orderBy('sort')->first();
            $quote->motivo_perdida_id = $defaultLoss?->id;
        }

        $quote->save();

        return $this->bootstrap($request, $tenantSlug);
    }

    public function addActivity(Request $request, string $tenantSlug, Cotizaciones $quote): JsonResponse
    {
        $team = $this->resolveTeam($tenantSlug);
        $this->ensureTeamAccess($request->user(), $team);
        $user = $request->user();

        if ((int) $quote->team_id !== (int) $team->id) {
            return response()->json(['error' => 'Cotización no válida.'], 404);
        }
        if (! $this->isManager($user) && (int) $quote->created_by_user_id !== (int) $user->id) {
            return response()->json(['error' => 'No autorizado.'], 403);
        }

        $data = $request->validate([
            'type' => ['required', 'string', 'max:32'],
            'date' => ['required', 'date'],
            'result' => ['nullable', 'string'],
            'next' => ['nullable', 'string'],
            'nextDate' => ['nullable', 'date'],
        ]);

        CotizacionActividad::create([
            'cotizacion_id' => $quote->id,
            'user_id' => $user->id,
            'tipo' => $data['type'],
            'fecha' => $data['date'],
            'resultado' => $data['result'] ?? null,
            'proxima_accion' => $data['next'] ?? null,
            'proxima_fecha' => $data['nextDate'] ?? null,
        ]);

        return $this->bootstrap($request, $tenantSlug);
    }

    public function createInvoice(Request $request, string $tenantSlug): JsonResponse
    {
        $team = $this->resolveTeam($tenantSlug);
        $this->ensureTeamAccess($request->user(), $team);
        $user = $request->user();
        $isManager = $this->isManager($user);

        $data = $request->validate([
            'quoteId' => ['nullable', 'integer'],
            'client' => ['required', 'string', 'max:255'],
            'segmentId' => ['nullable', 'integer'],
            'channelId' => ['nullable', 'integer'],
            'total' => ['required', 'numeric', 'min:0'],
            'issuedAt' => ['nullable', 'date'],
            'paidPct' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'marginPct' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'winReasonId' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string'],
        ]);

        $cotizacion = null;
        if (!empty($data['quoteId'])) {
            $cotizacion = Cotizaciones::where('team_id', $team->id)->find($data['quoteId']);
            if (! $cotizacion) {
                return response()->json(['error' => 'Cotización no válida.'], 404);
            }
            if (! $isManager && (int) $cotizacion->created_by_user_id !== (int) $user->id) {
                return response()->json(['error' => 'No autorizado.'], 403);
            }
        }

        $cliente = $this->resolveCliente($team->id, $data['client']);
        $esquema = Esquemasimp::where('team_id', $team->id)->first();
        if (! $esquema) {
            return response()->json(['error' => 'No hay esquema configurado para el team.'], 422);
        }

        $serie = SeriesFacturas::where('team_id', $team->id)
            ->where('tipo', SeriesFacturas::TIPO_FACTURAS)
            ->first();
        if (! $serie) {
            return response()->json(['error' => 'No hay serie de facturas configurada.'], 422);
        }

        $total = (float) $data['total'];
        $factura = FacturaFolioService::crearConFolioSeguro($serie->id, [
            'fecha' => $data['issuedAt'] ?? Carbon::now()->format('Y-m-d'),
            'clie' => $cliente->id,
            'nombre' => $data['client'],
            'esquema' => $esquema->id,
            'subtotal' => $total,
            'iva' => 0,
            'retiva' => 0,
            'retisr' => 0,
            'ieps' => 0,
            'total' => $total,
            'observa' => $data['notes'] ?? null,
            'estado' => 'Activa',
            'metodo' => 'PUE',
            'forma' => '01',
            'uso' => 'G03',
            'condiciones' => 'CONTADO',
            'moneda' => 'MXN',
            'tcambio' => 1,
            'cotizacion_id' => $cotizacion?->id,
            'team_id' => $team->id,
            'created_by_user_id' => $user->id,
            'segmento_id' => $data['segmentId'] ?? $cotizacion?->segmento_id,
            'canal_id' => $data['channelId'] ?? $cotizacion?->canal_id,
            'motivo_ganada_id' => $data['winReasonId'] ?? $cotizacion?->motivo_ganada_id,
            'margen_pct' => $data['marginPct'] ?? 0,
            'cobranza_pct' => $data['paidPct'] ?? 0,
        ]);

        $item = $this->resolveServicioItem($team->id, $esquema->id);
        FacturasPartidas::create([
            'facturas_id' => $factura->id,
            'item' => $item->id,
            'descripcion' => 'Servicio comercial',
            'cant' => 1,
            'precio' => $total,
            'subtotal' => $total,
            'iva' => 0,
            'retiva' => 0,
            'retisr' => 0,
            'ieps' => 0,
            'total' => $total,
            'unidad' => $item->unidad ?? 'H87',
            'cvesat' => $item->cvesat ?? '01010101',
            'costo' => $item->p_costo ?? 0,
            'clie' => $cliente->id,
            'team_id' => $team->id,
        ]);

        $factura->pendiente_pago = $factura->total;
        $factura->save();
        $factura->recalculateCommercialMetrics();

        if ($cotizacion) {
            $cotizacion->estado_comercial = 'WON';
            $cotizacion->save();
        }

        return $this->bootstrap($request, $tenantSlug);
    }

    public function exportExcel(Request $request, string $tenantSlug)
    {
        $exportData = $this->buildExportData($request, $tenantSlug);
        $sheets = $this->buildExcelSheets($exportData);

        $teamId = $exportData['team']['id'];
        $periodLabel = $exportData['payload']['period']['label'] ?? 'periodo';
        $filename = 'comercial-resumen-' . $teamId . '-' . str_replace(' ', '_', $periodLabel) . '.xlsx';

        return Excel::download(new ComercialDashboardExport($sheets), $filename);
    }

    public function exportPdf(Request $request, string $tenantSlug)
    {
        $exportData = $this->buildExportData($request, $tenantSlug);

        $logoPath = public_path('images/MainLogoTR.png');
        $logoData = null;
        if (is_file($logoPath)) {
            $logoData = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        $exportData['logo_data'] = $logoData;

        $pdf = Pdf::loadView('tiadmin.comercial-dashboard-pdf', $exportData)
            ->setPaper('letter', 'portrait');

        $teamId = $exportData['team']['id'];
        $periodLabel = $exportData['payload']['period']['label'] ?? 'periodo';
        $filename = 'comercial-resumen-' . $teamId . '-' . str_replace(' ', '_', $periodLabel) . '.pdf';

        return $pdf->download($filename);
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

    private function resolvePeriodRange(Request $request, Team $team): array
    {
        $from = $request->query('from');
        $to = $request->query('to');

        if ($from && $to) {
            return [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()];
        }

        $periodo = (int) ($team->periodo ?? 0);
        $ejercicio = (int) ($team->ejercicio ?? 0);
        if ($periodo >= 1 && $periodo <= 12 && $ejercicio > 0) {
            $start = Carbon::create($ejercicio, $periodo, 1)->startOfMonth();
            $end = Carbon::create($ejercicio, $periodo, 1)->endOfMonth();
            return [$start, $end];
        }

        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();
        return [$start, $end];
    }

    private function periodLabel(Team $team, Carbon $start): string
    {
        if ($team->periodo && $team->ejercicio) {
            return $start->translatedFormat('F Y');
        }
        return $start->translatedFormat('F Y');
    }

    private function buildExportData(Request $request, string $tenantSlug): array
    {
        $payload = $this->bootstrap($request, $tenantSlug)->getData(true);
        $user = $request->user();
        $team = $this->resolveTeam($tenantSlug);

        $filters = [
            'sellerId' => $request->query('sellerId', 'ALL'),
            'channel' => $request->query('channel', 'ALL'),
            'segment' => $request->query('segment', 'ALL'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        $isManager = $this->isManager($user);
        $effectiveSellerId = $isManager ? $filters['sellerId'] : ($payload['auth']['sellerId'] ?? $user->id);

        $catalog = $payload['catalog'] ?? ['segments' => [], 'channels' => [], 'winReasons' => [], 'lossReasons' => []];
        $segmentMap = collect($catalog['segments'])->pluck('nombre', 'id')->all();
        $channelMap = collect($catalog['channels'])->pluck('nombre', 'id')->all();
        $winReasonMap = collect($catalog['winReasons'])->pluck('nombre', 'id')->all();
        $lossReasonMap = collect($catalog['lossReasons'])->pluck('nombre', 'id')->all();
        $sellerMap = collect($payload['sellers'] ?? [])->pluck('name', 'id')->all();

        $from = $filters['from'] ? Carbon::parse($filters['from'])->startOfDay() : null;
        $to = $filters['to'] ? Carbon::parse($filters['to'])->endOfDay() : null;

        $quotes = array_values(array_filter($payload['quotes'] ?? [], function (array $q) use ($effectiveSellerId, $filters, $from, $to) {
            if ($effectiveSellerId !== 'ALL' && (string) ($q['sellerId'] ?? '') !== (string) $effectiveSellerId) {
                return false;
            }
            if ($filters['channel'] !== 'ALL' && (string) ($q['channelId'] ?? '') !== (string) $filters['channel']) {
                return false;
            }
            if ($filters['segment'] !== 'ALL' && (string) ($q['segmentId'] ?? '') !== (string) $filters['segment']) {
                return false;
            }
            $createdAt = isset($q['createdAt']) ? Carbon::parse($q['createdAt']) : null;
            if ($from && $createdAt && $createdAt->lt($from)) {
                return false;
            }
            if ($to && $createdAt && $createdAt->gt($to)) {
                return false;
            }
            return true;
        }));

        $invoices = array_values(array_filter($payload['invoices'] ?? [], function (array $inv) use ($effectiveSellerId, $filters, $from, $to) {
            if ($effectiveSellerId !== 'ALL' && (string) ($inv['sellerId'] ?? '') !== (string) $effectiveSellerId) {
                return false;
            }
            if ($filters['channel'] !== 'ALL' && (string) ($inv['channelId'] ?? '') !== (string) $filters['channel']) {
                return false;
            }
            if ($filters['segment'] !== 'ALL' && (string) ($inv['segmentId'] ?? '') !== (string) $filters['segment']) {
                return false;
            }
            $issuedAt = isset($inv['issuedAt']) ? Carbon::parse($inv['issuedAt']) : null;
            if ($from && $issuedAt && $issuedAt->lt($from)) {
                return false;
            }
            if ($to && $issuedAt && $issuedAt->gt($to)) {
                return false;
            }
            return true;
        }));

        $quotes = array_map(function (array $q) use ($segmentMap, $channelMap, $winReasonMap, $lossReasonMap, $sellerMap) {
            $q['segmentLabel'] = $segmentMap[$q['segmentId'] ?? ''] ?? '—';
            $q['channelLabel'] = $channelMap[$q['channelId'] ?? ''] ?? '—';
            $q['sellerLabel'] = $q['sellerName'] ?? ($sellerMap[$q['sellerId'] ?? ''] ?? 'Sin vendedor');
            $q['winReasonLabel'] = $winReasonMap[$q['winReasonId'] ?? ''] ?? '';
            $q['lossReasonLabel'] = $lossReasonMap[$q['lostReasonId'] ?? ''] ?? ($q['lostReasonId'] ? (string) $q['lostReasonId'] : '(Sin motivo)');
            $q['statusLabel'] = $this->statusLabel($q['status'] ?? '');
            return $q;
        }, $quotes);

        $invoices = array_map(function (array $inv) use ($segmentMap, $channelMap, $winReasonMap, $sellerMap) {
            $inv['segmentLabel'] = $segmentMap[$inv['segmentId'] ?? ''] ?? '—';
            $inv['channelLabel'] = $channelMap[$inv['channelId'] ?? ''] ?? '—';
            $inv['sellerLabel'] = $inv['sellerName'] ?? ($sellerMap[$inv['sellerId'] ?? ''] ?? 'Sin vendedor');
            $inv['winReasonLabel'] = $winReasonMap[$inv['winReasonId'] ?? ''] ?? '';
            return $inv;
        }, $invoices);

        $kpis = $this->computeKPIs($quotes, $invoices);
        $lossReasons = $this->computeLossReasons($quotes, $lossReasonMap);
        $pipeline = $this->computePipelineSummary($quotes);
        $latestQuotes = collect($quotes)->sortByDesc('createdAt')->take(8)->values()->all();
        $topInvoices = collect($invoices)->sortByDesc('issuedAt')->take(8)->values()->all();
        $vendors = $this->computeVendorsSummary($payload['sellers'] ?? [], $quotes, $invoices, $isManager, $effectiveSellerId);

        return [
            'team' => ['id' => $team->id, 'name' => $team->name],
            'payload' => $payload,
            'filters' => $filters,
            'effectiveSellerId' => $effectiveSellerId,
            'maps' => [
                'segments' => $segmentMap,
                'channels' => $channelMap,
                'winReasons' => $winReasonMap,
                'lossReasons' => $lossReasonMap,
                'sellers' => $sellerMap,
            ],
            'quotes' => $quotes,
            'invoices' => $invoices,
            'kpis' => $kpis,
            'lossReasons' => $lossReasons,
            'pipeline' => $pipeline,
            'latestQuotes' => $latestQuotes,
            'topInvoices' => $topInvoices,
            'vendors' => $vendors,
            'isManager' => $isManager,
        ];
    }

    private function computeKPIs(array $quotes, array $invoices): array
    {
        $totalInvoiced = array_reduce($invoices, fn ($a, $i) => $a + (float) ($i['total'] ?? 0), 0.0);
        $invoicedFromQuotes = array_values(array_filter($invoices, fn ($i) => ! empty($i['quoteId'])));
        $invoicedFromQuotesValue = array_reduce($invoicedFromQuotes, fn ($a, $i) => $a + (float) ($i['total'] ?? 0), 0.0);
        $invoicedDirectValue = $totalInvoiced - $invoicedFromQuotesValue;

        $totalQuotes = count($quotes);
        $totalQuoted = array_reduce($quotes, fn ($a, $q) => $a + (float) ($q['total'] ?? 0), 0.0);

        $invoicedQuoteIds = array_unique(array_map(fn ($i) => $i['quoteId'], $invoicedFromQuotes));
        $invoicedQuotesCount = count($invoicedQuoteIds);

        $conversion = $totalQuotes ? ($invoicedQuotesCount / $totalQuotes) : 0;
        $weighted = $totalQuoted ? ($invoicedFromQuotesValue / $totalQuoted) : 0;

        $cycles = [];
        foreach ($invoicedFromQuotes as $inv) {
            $quote = collect($quotes)->firstWhere('id', $inv['quoteId']);
            if (! $quote) {
                continue;
            }
            $created = isset($quote['createdAt']) ? Carbon::parse($quote['createdAt']) : null;
            $issued = isset($inv['issuedAt']) ? Carbon::parse($inv['issuedAt']) : null;
            if ($created && $issued) {
                $cycles[] = max(0, $created->diffInDays($issued));
            }
        }
        $avgCycle = count($cycles) ? (array_sum($cycles) / count($cycles)) : 0;

        $paidPctWeighted = $totalInvoiced
            ? array_reduce($invoices, fn ($a, $i) => $a + ((float) ($i['paidPct'] ?? 0)) * (float) ($i['total'] ?? 0), 0.0) / $totalInvoiced
            : 0;
        $marginPctWeighted = $totalInvoiced
            ? array_reduce($invoices, fn ($a, $i) => $a + ((float) ($i['marginPct'] ?? 0)) * (float) ($i['total'] ?? 0), 0.0) / $totalInvoiced
            : 0;

        $avgDiscount = $totalQuotes
            ? array_reduce($quotes, fn ($a, $q) => $a + (float) ($q['discountPct'] ?? 0), 0.0) / $totalQuotes
            : 0;

        $openCount = count(array_filter($quotes, fn ($q) => in_array($q['status'] ?? '', ['OPEN', 'NEGOTIATION'], true)));
        $lostCount = count(array_filter($quotes, fn ($q) => in_array($q['status'] ?? '', ['LOST', 'EXPIRED'], true)));

        return [
            'totalQuotes' => $totalQuotes,
            'totalQuoted' => $totalQuoted,
            'totalInvoiced' => $totalInvoiced,
            'invoicedFromQuotesValue' => $invoicedFromQuotesValue,
            'invoicedDirectValue' => $invoicedDirectValue,
            'invoicedQuotesCount' => $invoicedQuotesCount,
            'openCount' => $openCount,
            'lostCount' => $lostCount,
            'conversion' => $conversion,
            'weighted' => $weighted,
            'avgCycle' => $avgCycle,
            'avgDiscount' => $avgDiscount,
            'paidPctWeighted' => $paidPctWeighted,
            'marginPctWeighted' => $marginPctWeighted,
        ];
    }

    private function computeLossReasons(array $quotes, array $lossReasonMap): array
    {
        $lost = array_filter($quotes, fn ($q) => in_array($q['status'] ?? '', ['LOST', 'EXPIRED'], true));
        $map = [];
        foreach ($lost as $q) {
            $key = $q['lostReasonId'] ?? '(Sin motivo)';
            $label = $q['lostReasonId'] ? ($lossReasonMap[$q['lostReasonId']] ?? '(Sin motivo)') : '(Sin motivo)';
            if (! isset($map[$key])) {
                $map[$key] = ['reason' => $label, 'count' => 0, 'amount' => 0.0];
            }
            $map[$key]['count'] += 1;
            $map[$key]['amount'] += (float) ($q['total'] ?? 0);
        }

        $rows = array_values($map);
        usort($rows, fn ($a, $b) => $b['amount'] <=> $a['amount']);
        return $rows;
    }

    private function computePipelineSummary(array $quotes): array
    {
        $statusGroups = [
            'OPEN' => ['title' => 'Abiertas', 'statuses' => ['OPEN']],
            'NEGOTIATION' => ['title' => 'Negociación', 'statuses' => ['NEGOTIATION']],
            'WON' => ['title' => 'Facturadas', 'statuses' => ['WON']],
            'LOST' => ['title' => 'Perdidas/Expiradas', 'statuses' => ['LOST', 'EXPIRED']],
        ];

        $byStatus = [];
        foreach ($statusGroups as $key => $meta) {
            $list = array_filter($quotes, fn ($q) => in_array($q['status'] ?? '', $meta['statuses'], true));
            $byStatus[] = [
                'key' => $key,
                'title' => $meta['title'],
                'count' => count($list),
                'total' => array_reduce($list, fn ($a, $q) => $a + (float) ($q['total'] ?? 0), 0.0),
            ];
        }

        $open = array_filter($quotes, fn ($q) => in_array($q['status'] ?? '', ['OPEN', 'NEGOTIATION'], true));
        $today = Carbon::today();
        $buckets = [
            ['label' => '0-7', 'min' => 0, 'max' => 7],
            ['label' => '8-15', 'min' => 8, 'max' => 15],
            ['label' => '16-30', 'min' => 16, 'max' => 30],
            ['label' => '31-60', 'min' => 31, 'max' => 60],
            ['label' => '+60', 'min' => 61, 'max' => 9999],
        ];
        $aging = [];
        foreach ($buckets as $bucket) {
            $count = 0;
            foreach ($open as $q) {
                $created = isset($q['createdAt']) ? Carbon::parse($q['createdAt']) : null;
                if (! $created) {
                    continue;
                }
                $age = max(0, $created->diffInDays($today));
                if ($age >= $bucket['min'] && $age <= $bucket['max']) {
                    $count++;
                }
            }
            $aging[] = ['label' => $bucket['label'], 'count' => $count];
        }

        return [
            'byStatus' => $byStatus,
            'aging' => $aging,
        ];
    }

    private function computeVendorsSummary(array $sellers, array $quotes, array $invoices, bool $isManager, string|int $effectiveSellerId = 'ALL'): array
    {
        $rows = [];
        if ($isManager) {
            foreach ($sellers as $seller) {
                $qs = array_filter($quotes, fn ($q) => (string) ($q['sellerId'] ?? '') === (string) ($seller['id'] ?? ''));
                $inv = array_filter($invoices, fn ($i) => (string) ($i['sellerId'] ?? '') === (string) ($seller['id'] ?? ''));
                $k = $this->computeKPIs($qs, $inv);
                $rows[] = [
                    'seller' => $seller['name'] ?? '—',
                    'quotes' => count($qs),
                    'kpis' => $k,
                ];
            }
            usort($rows, fn ($a, $b) => $b['kpis']['totalInvoiced'] <=> $a['kpis']['totalInvoiced']);
            return $rows;
        }

        $k = $this->computeKPIs($quotes, $invoices);
        $sellerName = collect($sellers)->firstWhere('id', $effectiveSellerId)['name'] ?? 'Mi desempeño';
        return [[
            'seller' => $sellerName,
            'quotes' => count($quotes),
            'kpis' => $k,
        ]];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'OPEN' => 'Abierta',
            'NEGOTIATION' => 'En negociación',
            'WON' => 'Facturada',
            'LOST' => 'Perdida',
            'EXPIRED' => 'Expirada',
            default => $status ?: '—',
        };
    }

    private function buildExcelSheets(array $data): array
    {
        $k = $data['kpis'];
        $payload = $data['payload'];
        $filters = $data['filters'];
        $maps = $data['maps'];

        $filterSeller = $filters['sellerId'] === 'ALL'
            ? 'Todos'
            : ($maps['sellers'][$filters['sellerId']] ?? (string) $filters['sellerId']);
        $filterChannel = $filters['channel'] === 'ALL'
            ? 'Todos'
            : ($maps['channels'][$filters['channel']] ?? (string) $filters['channel']);
        $filterSegment = $filters['segment'] === 'ALL'
            ? 'Todos'
            : ($maps['segments'][$filters['segment']] ?? (string) $filters['segment']);

        $dashboardRows = [
            ['Contexto', 'Periodo', $payload['period']['label'] ?? '', ($payload['period']['from'] ?? '').' a '.($payload['period']['to'] ?? '')],
            ['Contexto', 'Usuario', $payload['auth']['userName'] ?? '', $payload['auth']['role'] ?? ''],
            ['Filtros', 'Vendedor', $filterSeller, ''],
            ['Filtros', 'Canal', $filterChannel, ''],
            ['Filtros', 'Segmento', $filterSegment, ''],
            ['KPIs', 'Cotizaciones (total)', $k['totalQuotes'], 'Abiertas: '.$k['openCount'].' · Perdidas: '.$k['lostCount'].' · Facturadas: '.$k['invoicedQuotesCount']],
            ['KPIs', '$ Cotizado', $k['totalQuoted'], ''],
            ['KPIs', '$ Facturado total', $k['totalInvoiced'], 'Desde cotizaciones: '.$k['invoicedFromQuotesValue']],
            ['KPIs', '$ Ventas directas', $k['invoicedDirectValue'], 'Facturas sin cotización'],
            ['KPIs', 'Conversión %', round($k['conversion'] * 100, 1), 'Facturadas / Totales'],
            ['KPIs', 'Conversión ponderada %', round($k['weighted'] * 100, 1), '$ Facturado / $ Cotizado'],
            ['KPIs', 'Ciclo promedio (días)', round($k['avgCycle'], 1), ''],
            ['KPIs', 'Descuento promedio %', round($k['avgDiscount'], 1), ''],
            ['KPIs', 'Margen ponderado %', round($k['marginPctWeighted'] * 100, 1), ''],
            ['KPIs', 'Cobranza ponderada %', round($k['paidPctWeighted'] * 100, 1), ''],
        ];

        $quotesRows = array_map(function (array $q) {
            $latestAct = ! empty($q['activities']) ? $q['activities'][0] : null;
            $lastTxt = $latestAct ? ($latestAct['type'].' · '.$latestAct['date']) : '—';
            return [
                $q['id'] ?? '',
                $q['createdAt'] ?? '',
                $q['client'] ?? '',
                $q['sellerLabel'] ?? '',
                $q['channelLabel'] ?? '',
                $q['segmentLabel'] ?? '',
                $q['statusLabel'] ?? '',
                (float) ($q['total'] ?? 0),
                (float) ($q['discountPct'] ?? 0),
                round((float) ($q['probability'] ?? 0) * 100, 1),
                $q['invoiceId'] ?? '',
                $lastTxt,
                $q['lossReasonLabel'] ?? '',
                $q['winReasonLabel'] ?? '',
                $q['notes'] ?? '',
            ];
        }, $data['quotes']);

        $invoicesRows = array_map(function (array $inv) {
            $origin = ! empty($inv['quoteId']) ? ('Cotización '.$inv['quoteId']) : 'Directa';
            return [
                $inv['id'] ?? '',
                $inv['issuedAt'] ?? '',
                $inv['client'] ?? '',
                $inv['sellerLabel'] ?? '',
                $inv['channelLabel'] ?? '',
                $inv['segmentLabel'] ?? '',
                (float) ($inv['total'] ?? 0),
                $origin,
                round((float) ($inv['paidPct'] ?? 0) * 100, 1),
                round((float) ($inv['marginPct'] ?? 0) * 100, 1),
                $inv['notes'] ?? '',
            ];
        }, $data['invoices']);

        $pipelineRows = [];
        foreach ($data['pipeline']['byStatus'] as $row) {
            $pipelineRows[] = ['Estatus', $row['title'], $row['count'], $row['total']];
        }
        foreach ($data['pipeline']['aging'] as $row) {
            $pipelineRows[] = ['Aging', $row['label'], $row['count'], ''];
        }

        $lossRows = array_map(fn ($r) => [$r['reason'], $r['count'], $r['amount']], $data['lossReasons']);

        $vendorRows = array_map(function (array $row) {
            $k = $row['kpis'];
            return [
                $row['seller'],
                $row['quotes'],
                $k['totalQuoted'],
                $k['totalInvoiced'],
                round($k['conversion'] * 100, 1),
                round($k['weighted'] * 100, 1),
                round($k['avgCycle'], 1),
                round($k['avgDiscount'], 1),
                round($k['marginPctWeighted'] * 100, 1),
                round($k['paidPctWeighted'] * 100, 1),
                $k['invoicedDirectValue'],
            ];
        }, $data['vendors']);

        return [
            new ComercialArraySheet('Dashboard', ['Seccion', 'Indicador', 'Valor', 'Detalle'], $dashboardRows),
            new ComercialArraySheet('Cotizaciones', ['Folio', 'Fecha', 'Cliente', 'Vendedor', 'Canal', 'Segmento', 'Estatus', 'Monto', 'Descuento %', 'Probabilidad %', 'Factura', 'Ultimo seguimiento', 'Motivo perdida', 'Motivo ganada', 'Notas'], $quotesRows),
            new ComercialArraySheet('Pipeline', ['Tipo', 'Etiqueta', 'Cantidad', 'Total'], $pipelineRows),
            new ComercialArraySheet('Facturas', ['Factura', 'Fecha', 'Cliente', 'Vendedor', 'Canal', 'Segmento', 'Total', 'Origen', 'Cobranza %', 'Margen %', 'Notas'], $invoicesRows),
            new ComercialArraySheet('Vendedores', ['Vendedor', 'Cotizaciones', '$ Cotizado', '$ Facturado', 'Conversion %', 'Ponderada %', 'Ciclo prom', 'Descuento prom %', 'Margen %', 'Cobranza %', '$ Directo'], $vendorRows),
            new ComercialArraySheet('Motivos perdida', ['Motivo', 'Cotizaciones', 'Monto'], $lossRows),
        ];
    }

    private function ensureTeamAccess(User $user, Team $team): void
    {
        if (! $user->teams()->where('teams.id', $team->id)->exists()) {
            abort(403);
        }
    }

    private function isManager(User $user): bool
    {
        return $user->hasRole(['administrador', 'contador', 'compras']);
    }

    private function resolveCliente(int $teamId, string $clientName): Clientes
    {
        $cliente = Clientes::where('team_id', $teamId)
            ->where('nombre', $clientName)
            ->first();
        if ($cliente) {
            return $cliente;
        }

        $clave = (string) (Clientes::where('team_id', $teamId)->count() + 1);
        return Clientes::create([
            'clave' => $clave,
            'nombre' => $clientName,
            'rfc' => 'XAXX010101000',
            'lista' => 1,
            'team_id' => $teamId,
        ]);
    }

    private function resolveServicioItem(int $teamId, int $esquemaId): Inventario
    {
        $item = Inventario::where('team_id', $teamId)
            ->where('servicio', 'SI')
            ->where('descripcion', 'Servicio comercial')
            ->first();
        if ($item) {
            return $item;
        }

        return Inventario::create([
            'clave' => 'SERV-COM',
            'descripcion' => 'Servicio comercial',
            'linea' => 1,
            'marca' => '',
            'modelo' => '',
            'u_costo' => 0,
            'p_costo' => 0,
            'precio1' => 0,
            'precio2' => 0,
            'precio3' => 0,
            'precio4' => 0,
            'precio5' => 0,
            'exist' => 0,
            'esquema' => $esquemaId,
            'servicio' => 'SI',
            'unidad' => 'H87',
            'cvesat' => '01010101',
            'team_id' => $teamId,
        ]);
    }
}
