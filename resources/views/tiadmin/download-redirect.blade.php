<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Descargando</title>
</head>
<body>
    <p>Descargando PDF...</p>
    <iframe src="{{ $downloadUrl }}" style="display:none" aria-hidden="true"></iframe>
    <script>
        window.setTimeout(function () {
            window.location.href = @json($returnUrl);
        }, 500);
    </script>
</body>
</html>
