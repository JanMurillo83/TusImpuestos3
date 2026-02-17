# Solución de Problemas de Sesión del SAT

## Error: "It was expected to have the session registered on portal home page with RFC..."

Este error ocurre cuando el portal del SAT no reconoce la sesión FIEL establecida. Las mejoras implementadas en `CfdiSatScraperService` incluyen:

### ✅ Mejoras Implementadas

1. **Login Proactivo**
   - Se fuerza el login al portal antes de la primera consulta
   - Verifica si la sesión ya está activa antes de hacer login
   - Logs detallados del proceso de autenticación

2. **Reintentos Automáticos**
   - Detecta errores de sesión automáticamente
   - Reintenta hasta 2 veces en caso de error de sesión
   - Limpia cookies y crea nueva sesión en cada reintento
   - Espera 2 segundos entre reintentos

3. **Logging Mejorado**
   - Registra cada intento de consulta
   - Logs específicos para errores de sesión
   - Información completa para debugging

### 🔍 Verificaciones Adicionales

Si el error persiste después de las mejoras, verifica:

#### 1. Certificados FIEL
```bash
# Verificar vigencia del certificado
openssl x509 -in /ruta/al/certificado.cer -inform DER -noout -dates

# Verificar que el certificado pertenezca al RFC
openssl x509 -in /ruta/al/certificado.cer -inform DER -noout -subject
```

#### 2. Archivos en Base de Datos
```sql
-- Verificar configuración del team
SELECT id, name, taxid, archivocer, archivokey, fielpass
FROM teams
WHERE id = 10;

-- Verificar que los archivos existan en storage
```

#### 3. Permisos de Archivos
```bash
# Verificar que los archivos FIEL sean legibles
ls -la storage/app/public/ | grep -E '\.cer|\.key'

# Verificar directorio de cookies
ls -la storage/app/public/cookies/
```

#### 4. Logs de Laravel
```bash
# Monitorear logs en tiempo real
tail -f storage/logs/laravel.log | grep -E 'team_id.*10|RFC.*CMA071107GF2'
```

### 📋 Checklist de Troubleshooting

Para el team 10 (RFC: CMA071107GF2), verificar:

- [ ] Los archivos `.cer` y `.key` existen en la ruta especificada
- [ ] La contraseña FIEL es correcta en `teams.fielpass`
- [ ] El certificado no está vencido
- [ ] El certificado pertenece al RFC CMA071107GF2
- [ ] Los permisos de archivos permiten lectura (644 mínimo)
- [ ] El directorio `storage/app/public/cookies/` existe y tiene permisos de escritura
- [ ] No hay procesos bloqueando los archivos de cookies

### 🛠️ Pasos de Resolución

#### Opción 1: Comando de Prueba (RECOMENDADO)

Ejecutar el comando artisan de diagnóstico:

```bash
# Probar la configuración del team 10
php artisan cfdi:test-connection 10
```

Este comando verificará automáticamente:
- Existencia de archivos FIEL
- Validez de credenciales
- Vigencia del certificado
- Conexión con el SAT
- Consulta de prueba

#### Opción 2: Verificar Configuración Manualmente

```php
// Ejecutar en tinker (php artisan tinker)
$team = \App\Models\Team::find(10);
$scraperService = new \App\Services\CfdiSatScraperService($team);

// Validar archivos FIEL
$validation = $scraperService->validateFielFiles();
dd($validation);

// Validar credenciales FIEL
$credentialValidation = $scraperService->validateFielCredentials();
dd($credentialValidation);
```

#### Opción 2: Limpiar Cookies Manualmente

```php
// Ejecutar en tinker
$team = \App\Models\Team::find(10);
$cookieFile = storage_path('/app/public/cookies/' . $team->taxid . '.json');

if (file_exists($cookieFile)) {
    unlink($cookieFile);
    echo "Cookie eliminada\n";
}
```

#### Opción 3: Forzar Reinicio de Sesión

```bash
# Eliminar todas las cookies del SAT
rm -f storage/app/public/cookies/*.json

# Reintentar la descarga
```

### 🔄 Flujo de Reintentos Implementado

```
Intento 1
├── Inicializar scraper
├── Crear sesión FIEL
├── Login al portal (si no hay sesión activa)
└── Consultar CFDIs
    ├── Éxito → Retornar resultados
    └── Error de sesión → Intento 2

Intento 2
├── Limpiar cookies
├── Esperar 2 segundos
├── Inicializar scraper nuevamente
├── Crear nueva sesión FIEL
└── Consultar CFDIs
    ├── Éxito → Retornar resultados
    └── Error → Retornar mensaje de error
```

### 📊 Logs Esperados

#### Ejecución Exitosa
```
[2025-02-17 14:30:00] local.INFO: SatScraper inicializado correctamente {"team_id":10,"rfc":"CMA071107GF2"}
[2025-02-17 14:30:01] local.INFO: Sesión iniciada correctamente en el portal SAT {"team_id":10,"rfc":"CMA071107GF2"}
[2025-02-17 14:30:02] local.INFO: Consultando CFDIs por período {"team_id":10,"rfc":"CMA071107GF2","fecha_inicial":"2025-01-01","fecha_final":"2025-01-31","tipo":"emitidos","intento":1}
[2025-02-17 14:30:15] local.INFO: Consulta de CFDIs completada {"team_id":10,"rfc":"CMA071107GF2","tipo":"emitidos","count":50}
```

#### Error con Reintento
```
[2025-02-17 14:30:00] local.INFO: SatScraper inicializado correctamente {"team_id":10,"rfc":"CMA071107GF2"}
[2025-02-17 14:30:02] local.INFO: Consultando CFDIs por período {"team_id":10,"intento":1}
[2025-02-17 14:30:05] local.WARNING: Error de sesión detectado, reintentando... {"team_id":10,"rfc":"CMA071107GF2","intento":1,"error":"It was expected to have the session registered..."}
[2025-02-17 14:30:07] local.INFO: SatScraper inicializado correctamente {"team_id":10,"rfc":"CMA071107GF2"}
[2025-02-17 14:30:08] local.INFO: Consultando CFDIs por período {"team_id":10,"intento":2}
[2025-02-17 14:30:20] local.INFO: Consulta de CFDIs completada {"team_id":10,"count":50}
```

### 🚨 Problemas Comunes

#### 1. FIEL Vencida
**Error:** "La FIEL ha expirado"
**Solución:** Renovar el certificado FIEL en el portal del SAT y actualizar los archivos en el sistema

#### 2. Contraseña Incorrecta
**Error:** "Error al validar FIEL"
**Solución:** Verificar y actualizar el campo `fielpass` en la tabla `teams`

#### 3. Archivos Corruptos
**Error:** "No se pudo leer el archivo"
**Solución:** Re-subir los archivos `.cer` y `.key`

#### 4. SAT en Mantenimiento
**Error:** Timeout o errores de conexión
**Solución:** Esperar a que el SAT termine su mantenimiento (usualmente por las noches)

### 💡 Recomendaciones

1. **Horarios de Consulta**
   - Evitar horarios de alta demanda (8am - 2pm, hora del centro)
   - Evitar fines de mes (saturación del portal)

2. **Frecuencia de Descargas**
   - No hacer más de 1 consulta cada 5 segundos
   - Implementar delays entre descargas masivas

3. **Monitoreo**
   - Revisar logs regularmente
   - Configurar alertas para errores recurrentes

4. **Backups**
   - Mantener respaldo de archivos FIEL
   - Documentar contraseñas de forma segura

### 📞 Soporte

Si después de seguir estos pasos el problema persiste:

1. Revisar los logs completos en `storage/logs/laravel.log`
2. Verificar que la librería `phpcfdi/cfdi-sat-scraper` esté actualizada
3. Contactar al soporte del SAT para verificar el estado de la FIEL

---

**Última actualización:** Febrero 2025
