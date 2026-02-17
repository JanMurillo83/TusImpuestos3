# Solución: Connection Error when try to login using FIEL

## Problema
Error: **"Connection error when try to login using FIEL"**

Este error ocurre cuando el cliente HTTP no puede establecer una conexión SSL/TLS con los servidores del SAT.

## Causas Comunes

1. **Certificados SSL del sistema desactualizados**
2. **Firewall o proxy bloqueando la conexión**
3. **Configuración de CURL incorrecta**
4. **Problemas de red con el SAT**
5. **Extensiones PHP faltantes**

## ✅ Mejoras Ya Implementadas

He actualizado `CfdiSatScraperService` con:

### 1. Configuración HTTP Robusta
- Timeouts extendidos (180s total, 60s conexión)
- Keep-alive habilitado
- Headers completos que simulan un navegador
- Seguimiento de redirecciones
- Verificación SSL apropiada

### 2. Reintentos de Login
- 3 intentos automáticos de login
- Espera de 3 segundos entre intentos
- Logs detallados de cada intento

## 🔧 Soluciones Paso a Paso

### Solución 1: Verificar Extensiones PHP (RECOMENDADO)

```bash
# Verificar extensiones necesarias
php -m | grep -E 'curl|openssl|xml|json'

# Deberías ver:
# - curl
# - openssl
# - xml
# - json
```

Si falta alguna, instalarla:

```bash
# Ubuntu/Debian
sudo apt-get install php-curl php-xml

# CentOS/RHEL
sudo yum install php-curl php-xml
```

### Solución 2: Actualizar Certificados CA del Sistema

```bash
# Ubuntu/Debian
sudo apt-get update
sudo apt-get install ca-certificates
sudo update-ca-certificates

# CentOS/RHEL
sudo yum update ca-certificates
```

### Solución 3: Verificar Conectividad con el SAT

```bash
# Probar conexión HTTPS al SAT
curl -v https://cfdiau.sat.gob.mx/nidp/app/login

# Probar con el portal de autenticación
curl -v https://portalcfdi.facturaelectronica.sat.gob.mx/
```

Si estos comandos fallan, el problema es de red o firewall.

### Solución 4: Verificar Configuración PHP cURL

Crear archivo `test_curl.php`:

```php
<?php
$ch = curl_init('https://cfdiau.sat.gob.mx/nidp/app/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$result = curl_exec($ch);

if (curl_errno($ch)) {
    echo "Error cURL: " . curl_error($ch) . "\n";
    echo "Código de error: " . curl_errno($ch) . "\n";
} else {
    echo "Conexión exitosa!\n";
    echo "Código HTTP: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "\n";
}

curl_close($ch);
?>
```

Ejecutar:
```bash
php test_curl.php
```

### Solución 5: Configuración Temporal sin Verificación SSL (SOLO PARA PRUEBAS)

**⚠️ ADVERTENCIA: Solo usar en desarrollo, NUNCA en producción**

Si necesitas probar sin verificación SSL temporalmente, puedes modificar:

```bash
# Editar .env
CFDI_SSL_VERIFY=false
```

Y modificar el servicio para leer esta variable:

```php
// En CfdiSatScraperService.php, línea ~182
'verify' => env('CFDI_SSL_VERIFY', true),
```

### Solución 6: Usar Proxy si es Necesario

Si tu servidor está detrás de un proxy:

```bash
# Agregar a .env
HTTP_PROXY=http://proxy.ejemplo.com:8080
HTTPS_PROXY=http://proxy.ejemplo.com:8080
```

Y en el servicio:

```php
// Agregar al cliente HTTP
'proxy' => [
    'http'  => env('HTTP_PROXY'),
    'https' => env('HTTPS_PROXY'),
]
```

## 🔍 Diagnóstico Detallado

### Paso 1: Ejecutar Comando de Prueba

```bash
php artisan cfdi:test-connection 10
```

Observa en qué paso falla.

### Paso 2: Revisar Logs con Detalle

```bash
tail -f storage/logs/laravel.log
```

Busca líneas como:
- `Intentando login al portal SAT`
- `Error en intento de login`

### Paso 3: Verificar Versión de OpenSSL

```bash
php -i | grep "OpenSSL"
```

Debe ser al menos OpenSSL 1.1.1 o superior.

### Paso 4: Probar con curl CLI

```bash
# Probar descarga directa con FIEL
curl -v --cert /ruta/al/certificado.cer \
     --key /ruta/a/la/llave.key \
     --pass "contraseña" \
     https://cfdiau.sat.gob.mx/nidp/app/login
```

## 📊 Tabla de Errores Comunes

| Error | Causa | Solución |
|-------|-------|----------|
| `SSL certificate problem: unable to get local issuer certificate` | Certificados CA faltantes | Actualizar ca-certificates |
| `Could not resolve host` | DNS no resuelve | Verificar /etc/resolv.conf |
| `Connection timed out` | Firewall bloqueando | Revisar reglas de firewall |
| `SSL connection timeout` | Timeout muy bajo | Aumentar timeouts |
| `curl error 60` | Problema SSL general | Actualizar curl/openssl |

## 🎯 Solución Específica para tu Caso

Basándome en que el login funciona en el navegador pero no en el código, probablemente el problema es:

### Opción A: Certificados del Sistema

```bash
# En Ubuntu/Debian
sudo mkdir -p /usr/local/share/ca-certificates
sudo cp /etc/ssl/certs/ca-certificates.crt /usr/local/share/ca-certificates/
sudo update-ca-certificates

# Reiniciar servicios
sudo systemctl restart php8.2-fpm  # o tu versión de PHP
sudo systemctl restart nginx       # o apache2
```

### Opción B: Configuración PHP

```bash
# Verificar php.ini
php --ini

# Buscar estas líneas y asegurarse que estén correctas:
# curl.cainfo = /etc/ssl/certs/ca-certificates.crt
# openssl.cafile = /etc/ssl/certs/ca-certificates.crt
```

Editar el archivo php.ini correspondiente:

```ini
; Agregar o descomentar
curl.cainfo = "/etc/ssl/certs/ca-certificates.crt"
openssl.cafile = "/etc/ssl/certs/ca-certificates.crt"
```

Luego reiniciar PHP-FPM.

## 🔒 Verificación de Seguridad

Después de aplicar las soluciones, verificar:

```bash
# 1. Probar OpenSSL
openssl s_client -connect cfdiau.sat.gob.mx:443

# 2. Probar PHP cURL
php -r '$ch = curl_init("https://cfdiau.sat.gob.mx/nidp/app/login"); curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); echo curl_exec($ch) ? "OK\n" : "Error: ".curl_error($ch)."\n";'

# 3. Probar el comando Laravel
php artisan cfdi:test-connection 10
```

## 📝 Log de Errores Esperado

### Antes de la solución:
```
[2025-02-17] local.WARNING: Error en intento de login
{"team_id":10,"rfc":"CMA071107GF2","intento":1,"error":"Connection error when try to login using FIEL"}
```

### Después de la solución:
```
[2025-02-17] local.INFO: Intentando login al portal SAT
{"team_id":10,"rfc":"CMA071107GF2","intento":1}
[2025-02-17] local.INFO: Sesión iniciada correctamente en el portal SAT
{"team_id":10,"rfc":"CMA071107GF2","intentos":1}
```

## 🆘 Si Nada Funciona

Contactar al administrador del servidor para:

1. Verificar reglas de firewall
2. Verificar configuración de proxy
3. Verificar que el servidor pueda acceder a:
   - `cfdiau.sat.gob.mx`
   - `portalcfdi.facturaelectronica.sat.gob.mx`
4. Revisar logs del sistema: `/var/log/syslog` o `/var/log/messages`

## 💡 Recomendación Final

**La solución más probable es actualizar los certificados CA del sistema:**

```bash
sudo apt-get update
sudo apt-get install --reinstall ca-certificates
sudo update-ca-certificates --fresh
sudo systemctl restart php8.2-fpm
```

Luego prueba:
```bash
php artisan cfdi:test-connection 10
```

---

**Última actualización:** Febrero 2025
