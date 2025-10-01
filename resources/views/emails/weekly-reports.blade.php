<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reportes Semanales</title>
</head>
<body>
    <p>Hola,</p>
    <p>Adjuntamos los reportes correspondientes al periodo del <strong>{{ $fechaInicio }}</strong> al <strong>{{ $fechaFin }}</strong> para el equipo <strong>{{ $teamId }}</strong>.</p>
    <ul>
        <li>Estado de Cuenta de Clientes</li>
        <li>Estado de Cuenta de Proveedores</li>
        <li>Costo del Inventario</li>
        <li>Reporte de Facturación</li>
        <li>Reporte de Compras</li>
    </ul>
    <p>Hora programada de envío: {{ $sendAt }} (hora del servidor).</p>
    <p>Saludos,<br>Tu sistema TusImpuestos3</p>
</body>
</html>
