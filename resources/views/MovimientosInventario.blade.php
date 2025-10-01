<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Movimientos de Inventario</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 20px; margin: 0 0 10px 0; }
        .meta { margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .right { text-align: right; }
        .small { font-size: 11px; color: #666; }
    </style>
</head>
<body>
<?php
    use App\Models\Inventario;
    use App\Models\Movinventario;
    use Illuminate\Support\Facades\DB;
    use Carbon\Carbon;

    $teamId = $team ?? null;
    $productoId = $producto_id ?? null;
    $fi = $fecha_inicio ?? null;
    $ff = $fecha_fin ?? null;

    // Normalizar fechas a strings Y-m-d para comparaciones por fecha
    $fiDate = $fi ? Carbon::parse($fi)->startOfDay()->format('Y-m-d') : null;
    $ffDate = $ff ? Carbon::parse($ff)->endOfDay()->format('Y-m-d') : null;

    $movs = collect();

    if ($teamId) {
        // Construir consulta base
        $query = DB::table('movinventarios as m')
            ->leftJoin('inventarios as i', 'i.id', '=', 'm.producto')
            ->select(
                'm.id',
                'm.producto',
                'm.tipo',
                'm.fecha',
                'm.cant',
                'm.costo',
                'm.precio',
                'm.concepto',
                'm.tipoter',
                'm.tercero',
                DB::raw('COALESCE(i.clave, "") as clave'),
                DB::raw('COALESCE(i.descripcion, "") as descripcion')
            )
            // Filtrar por team por inventario (por si m.team_id no fue llenado en algunos registros)
            ->where('i.team_id', $teamId);

        if ($productoId) {
            $query->where('m.producto', $productoId);
        }
        if ($fiDate) {
            $query->whereDate('m.fecha', '>=', $fiDate);
        }
        if ($ffDate) {
            $query->whereDate('m.fecha', '<=', $ffDate);
        }

        $movs = $query->orderBy('i.clave')->orderBy('m.fecha')->get();
    }

    // Agrupar por producto para mostrar bloques
    $agrupado = $movs->groupBy('producto');
?>
    <h1>Movimientos de Inventario</h1>
    <div class="meta small">
        Empresa / Team ID: <?= htmlspecialchars((string)($teamId ?? '')) ?>
        <br>
        Rango: <?= htmlspecialchars((string)($fiDate ?? 'Inicio')) ?> a <?= htmlspecialchars((string)($ffDate ?? 'Hoy')) ?>
        <?php if ($productoId): ?>
            <br> Producto: <?php
                $prod = Inventario::find($productoId);
                echo htmlspecialchars((string)($prod?->clave . ' - ' . $prod?->descripcion));
            ?>
        <?php endif; ?>
        <br>
        Fecha de generación: <?= now()->format('Y-m-d H:i') ?>
    </div>

    <?php if ($agrupado->isEmpty()): ?>
        <div class="small">No hay movimientos de inventario para los filtros seleccionados.</div>
    <?php else: ?>
        <?php foreach ($agrupado as $producto => $rows): ?>
            <?php
                $head = $rows->first();
                $clave = $head->clave ?? '';
                $desc = $head->descripcion ?? '';
            ?>
            <h3><?= htmlspecialchars((string)$clave) ?> - <?= htmlspecialchars((string)$desc) ?></h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 100px;">Fecha</th>
                        <th style="width: 90px;">Tipo</th>
                        <th>Concepto</th>
                        <th style="width: 90px;" class="right">Cantidad</th>
                        <th style="width: 90px;" class="right">Costo</th>
                        <th style="width: 90px;" class="right">Precio</th>
                        <th style="width: 140px;">Tercero</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)Carbon::parse($r->fecha)->format('Y-m-d')) ?></td>
                        <td><?= htmlspecialchars((string)$r->tipo) ?></td>
                        <td><?= htmlspecialchars((string)$r->concepto) ?></td>
                        <td class="right"><?= number_format((float)$r->cant, 2) ?></td>
                        <td class="right">$<?= number_format((float)$r->costo, 2) ?></td>
                        <td class="right">$<?= number_format((float)$r->precio, 2) ?></td>
                        <td><?= htmlspecialchars((string)($r->tipoter . ':' . $r->tercero)) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <br>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
