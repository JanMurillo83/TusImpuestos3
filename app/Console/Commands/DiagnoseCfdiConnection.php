<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DiagnoseCfdiConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cfdi:diagnose';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnostica problemas de conexión con el SAT';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('==============================================');
        $this->info('Diagnóstico de Conexión CFDI con el SAT');
        $this->info('==============================================');
        $this->newLine();

        // Test 1: Extensiones PHP
        $this->info('Test 1: Verificando extensiones PHP...');
        $requiredExtensions = ['curl', 'openssl', 'xml', 'json', 'simplexml', 'dom'];
        $missingExtensions = [];

        foreach ($requiredExtensions as $ext) {
            if (extension_loaded($ext)) {
                $this->line("  ✓ {$ext}");
            } else {
                $this->error("  ✗ {$ext} - NO INSTALADA");
                $missingExtensions[] = $ext;
            }
        }

        if (count($missingExtensions) > 0) {
            $this->newLine();
            $this->error('Faltan extensiones requeridas: ' . implode(', ', $missingExtensions));
            $this->info('Instalar con: sudo apt-get install php-' . implode(' php-', $missingExtensions));
            return 1;
        }
        $this->newLine();

        // Test 2: Versión OpenSSL
        $this->info('Test 2: Verificando versión de OpenSSL...');
        $opensslVersion = OPENSSL_VERSION_TEXT;
        $this->line("  Versión: {$opensslVersion}");

        if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
            $this->line('  ✓ PHP ' . PHP_VERSION);
        } else {
            $this->error('  ✗ PHP version muy antigua: ' . PHP_VERSION);
        }
        $this->newLine();

        // Test 3: Certificados CA
        $this->info('Test 3: Verificando certificados CA del sistema...');
        $caPaths = [
            '/etc/ssl/certs/ca-certificates.crt',
            '/etc/pki/tls/certs/ca-bundle.crt',
            '/etc/ssl/ca-bundle.pem',
            '/usr/local/share/certs/ca-root-nss.crt',
        ];

        $caFound = false;
        foreach ($caPaths as $caPath) {
            if (file_exists($caPath)) {
                $this->line("  ✓ Encontrado: {$caPath}");
                $caFound = true;
                break;
            }
        }

        if (!$caFound) {
            $this->error('  ✗ No se encontraron certificados CA');
            $this->info('  Ejecutar: sudo update-ca-certificates');
        }
        $this->newLine();

        // Test 4: Configuración PHP cURL
        $this->info('Test 4: Verificando configuración de cURL en PHP...');
        $curlInfo = curl_version();
        $this->line("  Versión cURL: {$curlInfo['version']}");
        $this->line("  SSL Versión: {$curlInfo['ssl_version']}");

        if (isset($curlInfo['features'])) {
            $features = [];
            if ($curlInfo['features'] & CURL_VERSION_SSL) $features[] = 'SSL';
            if ($curlInfo['features'] & CURL_VERSION_LIBZ) $features[] = 'LIBZ';
            if ($curlInfo['features'] & CURL_VERSION_IPV6) $features[] = 'IPv6';
            $this->line('  Features: ' . implode(', ', $features));
        }
        $this->newLine();

        // Test 5: Resolución DNS
        $this->info('Test 5: Verificando resolución DNS del SAT...');
        $satHosts = [
            'cfdiau.sat.gob.mx',
            'portalcfdi.facturaelectronica.sat.gob.mx',
        ];

        foreach ($satHosts as $host) {
            $ip = gethostbyname($host);
            if ($ip !== $host) {
                $this->line("  ✓ {$host} → {$ip}");
            } else {
                $this->error("  ✗ {$host} - NO SE PUEDE RESOLVER");
            }
        }
        $this->newLine();

        // Test 6: Conectividad HTTPS
        $this->info('Test 6: Probando conectividad HTTPS con el SAT...');
        $testUrl = 'https://cfdiau.sat.gob.mx/nidp/app/login';

        $this->line("  Probando: {$testUrl}");

        try {
            $ch = curl_init($testUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $errno = curl_errno($ch);

            curl_close($ch);

            if ($errno === 0 && $httpCode >= 200 && $httpCode < 400) {
                $this->line("  ✓ Conexión exitosa (HTTP {$httpCode})");
            } else if ($errno === 0) {
                $this->warn("  ⚠ Conectado pero HTTP {$httpCode}");
            } else {
                $this->error("  ✗ Error {$errno}: {$error}");
            }
        } catch (\Exception $e) {
            $this->error("  ✗ Excepción: {$e->getMessage()}");
        }
        $this->newLine();

        // Test 7: Permisos de directorios
        $this->info('Test 7: Verificando permisos de directorios...');
        $directories = [
            'storage/app/public/cookies',
            'storage/app/public/cfdis',
            'storage/logs',
        ];

        foreach ($directories as $dir) {
            $fullPath = base_path($dir);
            if (is_dir($fullPath)) {
                if (is_writable($fullPath)) {
                    $this->line("  ✓ {$dir} (escribible)");
                } else {
                    $this->error("  ✗ {$dir} (sin permisos de escritura)");
                }
            } else {
                $this->warn("  ⚠ {$dir} (no existe)");
                $this->info("    Crear con: mkdir -p {$fullPath} && chmod 775 {$fullPath}");
            }
        }
        $this->newLine();

        // Test 8: Variables de entorno
        $this->info('Test 8: Verificando variables de entorno...');
        $envVars = [
            'APP_ENV',
            'APP_DEBUG',
        ];

        foreach ($envVars as $var) {
            $value = env($var, 'no definida');
            $this->line("  {$var}: {$value}");
        }
        $this->newLine();

        // Resumen
        $this->info('==============================================');
        $this->info('Diagnóstico Completado');
        $this->info('==============================================');
        $this->newLine();

        // Recomendaciones
        $this->info('Recomendaciones:');
        $this->line('1. Si hay errores de SSL/TLS:');
        $this->line('   sudo apt-get update');
        $this->line('   sudo apt-get install --reinstall ca-certificates');
        $this->line('   sudo update-ca-certificates --fresh');
        $this->newLine();
        $this->line('2. Si hay errores de conexión:');
        $this->line('   - Verificar firewall/proxy');
        $this->line('   - Revisar logs: tail -f storage/logs/laravel.log');
        $this->newLine();
        $this->line('3. Probar con un team específico:');
        $this->line('   php artisan cfdi:test-connection {team_id}');

        return 0;
    }
}
