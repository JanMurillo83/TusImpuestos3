<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Módulo Comercial</title>
  @include('tiadmin.comercial-dashboard-styles')
</head>
<body>
  @include('tiadmin.comercial-dashboard-inline', ['tenant_slug' => $tenant_slug])
</body>
</html>
