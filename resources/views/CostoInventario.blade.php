<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Costo del Inventario</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 20px; margin: 0 0 10px 0; }
        .meta { margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        tfoot td { font-weight: bold; }
        .right { text-align: right; }
        .small { font-size: 11px; color: #666; }
    </style>
</head>
<body>
<?php
    use App\Models\Inventario;
    use Illuminate\Support\Facades\DB;

    $teamId = $team ?? null;
    $items = collect();
    $granTotal = 0;

    if ($teamId) {
        $items = Inventario::where('team_id', $teamId)
            ->orderBy('descripcion')
            ->get(['clave','descripcion','u_costo','p_costo','exist','unidad']);

        $items = $items->map(function($row){
            $costo = (float) ($row->p_costo ?? 0);
            if ($costo <= 0) {
                $costo = (float) ($row->u_costo ?? 0);
            }
            $exist = (float) ($row->exist ?? 0);
            $subtotal = $costo * $exist;
            return [
                'clave' => $row->clave,
                'descripcion' => $row->descripcion,
                'unidad' => $row->unidad,
                'exist' => $exist,
                'costo' => $costo,
                'subtotal' => $subtotal,
            ];
        });

        $granTotal = $items->sum('subtotal');
    }
?>
    <h1>Costo del Inventario</h1>
    <div class="meta small">
        Empresa / Team ID: <?= htmlspecialchars((string)($teamId ?? '')) ?>
        <br>
        Fecha de generación: <?= now()->format('Y-m-d H:i') ?>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 120px;">Clave</th>
                <th>Descripción</th>
                <th style="width: 70px;" class="right">Exist.</th>
                <th style="width: 90px;" class="right">Costo</th>
                <th style="width: 90px;" class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><?= htmlspecialchars((string)$it['clave']) ?></td>
                <td><?= htmlspecialchars((string)$it['descripcion']) ?></td>
                <td class="right"><?= number_format((float)$it['exist'], 2) ?></td>
                <td class="right">$<?= number_format((float)$it['costo'], 2) ?></td>
                <td class="right">$<?= number_format((float)$it['subtotal'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="right">Total</td>
                <td class="right">$<?= number_format((float)$granTotal, 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <p class="small">Nota: El costo utilizado corresponde a p_costo cuando está disponible; en caso contrario, se usa u_costo.</p>
</body>
</html>
