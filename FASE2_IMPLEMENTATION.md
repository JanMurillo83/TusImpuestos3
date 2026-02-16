# Implementación Fase 2: Event-Driven Saldos Contables

## Resumen

Fase 2 implementa **actualización automática e incremental** de saldos contables mediante arquitectura event-driven, reemplazando la regeneración completa por updates selectivos.

---

## Arquitectura

### Componentes Implementados

1. **AuxiliaresObserver** (`app/Observers/AuxiliaresObserver.php`)
   - Detecta cambios en tabla `auxiliares` (create/update/delete)
   - Dispara jobs async para actualización incremental
   - Invalida caché selectivamente

2. **ActualizarSaldosCuentaJob** (`app/Jobs/ActualizarSaldosCuentaJob.php`)
   - Job queued para actualización de saldos
   - Queue: `saldos`
   - Timeout: 120 segundos
   - Reintentos: 3

3. **SaldosService** (`app/Services/SaldosService.php`)
   - Lógica de actualización incremental
   - Actualiza solo cuenta afectada + jerarquía padre
   - Mantiene lógica Balance vs Resultados (fix previo)

4. **Config** (`config/saldos.php`)
   - Feature flags para habilitar/deshabilitar Fase 2
   - Configuración de caché, queue, timeouts

5. **Migration** (`database/migrations/2026_02_16_122701_add_updated_at_to_saldos_tables.php`)
   - Agrega columna `updated_at` a `saldoscuentas` y `saldos_reportes`
   - Índices para queries optimizadas

---

## Estado Actual

### ✅ FASE 1 (ACTIVA)
- Caché estratégico con TTL de 5 minutos
- Modelos Sushi optimizados
- Widgets de indicadores cacheados
- Invalidación automática al contabilizar

### 🔄 FASE 2 (DISPONIBLE - DESHABILITADA POR DEFECTO)
- Observer registrado pero **inactivo** (`auto_update_enabled = false`)
- Jobs y servicios implementados y listos
- Requiere queue worker activo
- Testing pendiente antes de activación

---

## Cómo Habilitar Fase 2

### Prerrequisitos

1. **Verificar que queue worker está corriendo**:
```bash
# Verificar proceso activo
ps aux | grep "queue:work"

# Si no está corriendo, iniciar:
php artisan queue:work --queue=saldos --tries=3 --timeout=120
```

2. **Monitoreo de logs**:
```bash
tail -f storage/logs/laravel.log
```

### Activación Gradual (RECOMENDADO)

#### Paso 1: Testing en Desarrollo
```bash
# En .env
SALDOS_AUTO_UPDATE=true
SALDOS_DETAILED_LOGGING=true

# Limpiar config
php artisan config:clear
```

#### Paso 2: Validación Manual
1. Crear una póliza de prueba
2. Verificar en logs que job se ejecutó
3. Validar que saldos se actualizaron correctamente
4. Comparar con método anterior (ContabilizaReporte)

#### Paso 3: Testing por Team ID (Opcional)
Para testing más granular, modificar observer:
```php
// En AuxiliaresObserver.php, método created()
if (config('saldos.auto_update_enabled', false)) {
    // Solo para teams específicos durante testing
    $teams_piloto = [1, 5, 10]; // IDs de teams de prueba
    if (in_array($auxiliares->team_id, $teams_piloto)) {
        ActualizarSaldosCuentaJob::dispatch(...);
    }
}
```

#### Paso 4: Producción Completa
```bash
# En .env
SALDOS_AUTO_UPDATE=true
SALDOS_DETAILED_LOGGING=false  # Desactivar logging detallado

# Configurar supervisor para queue worker persistente
# Ver sección "Supervisor Setup" abajo
```

---

## Supervisor Setup (Producción)

Crear archivo `/etc/supervisor/conf.d/tusimpuestos-saldos-worker.conf`:

```ini
[program:tusimpuestos-saldos-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/a/proyecto/artisan queue:work --queue=saldos --tries=3 --timeout=120 --max-jobs=1000
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/ruta/a/proyecto/storage/logs/saldos-worker.log
stopwaitsecs=3600
```

Comandos:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start tusimpuestos-saldos-worker:*
sudo supervisorctl status
```

---

## Comparativa: Antes vs Después

### ANTES (Método Actual - Fase 1 Activa)
```
Usuario crea póliza
  ↓
Se guardan auxiliares en BD
  ↓
[Nada sucede automáticamente]
  ↓
Usuario va a /dash-board-indicadores
  ↓
ContabilizaReporte() regenera TODO (DELETE + INSERT)
  - Procesa todas las cuentas del catálogo
  - Toma 3-10 segundos en empresas grandes
  ↓
Caché se invalida
  ↓
Dashboard muestra datos actualizados
```

### DESPUÉS (Fase 2 - Cuando se habilite)
```
Usuario crea póliza
  ↓
Se guardan auxiliares en BD
  ↓
AuxiliaresObserver detecta cambio
  ↓
ActualizarSaldosCuentaJob se encola (async)
  ↓
Job procesa EN BACKGROUND:
  - Solo actualiza cuenta afectada (ej: 10501001)
  - Actualiza su jerarquía padre (105010, 1050, 10)
  - Toma < 500ms
  ↓
Caché se invalida selectivamente
  ↓
Usuario va a /dash-board-indicadores
  ↓
Lee desde caché (datos ya actualizados)
  ↓
Dashboard muestra datos en < 100ms
```

---

## Performance Esperado

| Métrica | Fase 1 (Actual) | Fase 2 (Event-Driven) | Mejora |
|---------|-----------------|------------------------|--------|
| Tiempo regeneración completa | 3-10s | N/A (no regenera) | - |
| Tiempo update incremental | N/A | 200-500ms | - |
| Carga del dashboard | 100-200ms (caché) | 100-200ms (caché) | = |
| Updates por póliza | 1 (manual) | 1 (automático) | ✅ |
| Queries ejecutadas | ~500 (todas cuentas) | ~5 (cuenta + padres) | **99% menos** |
| Bloqueo de UI | 3-10s | 0s (async) | **Sin bloqueo** |

---

## Monitoreo y Debugging

### Ver jobs en queue
```bash
php artisan queue:monitor saldos

# Ver jobs fallidos
php artisan queue:failed

# Reintentar job fallido
php artisan queue:retry {id}

# Reintentar todos
php artisan queue:retry all
```

### Logs importantes
```bash
# Errores de jobs
grep "ActualizarSaldosCuentaJob failed" storage/logs/laravel.log

# Actualizaciones de saldos
grep "actualizando saldos incrementales" storage/logs/laravel.log

# Performance
grep "SaldosService" storage/logs/laravel.log
```

### Verificar consistencia de datos
```sql
-- Comparar saldos_reportes vs auxiliares (para una cuenta)
SELECT
    sr.codigo,
    sr.cargos as saldos_reportes_cargos,
    sr.abonos as saldos_reportes_abonos,
    COALESCE(SUM(a.cargo), 0) as auxiliares_cargos,
    COALESCE(SUM(a.abono), 0) as auxiliares_abonos,
    sr.cargos - COALESCE(SUM(a.cargo), 0) as diferencia_cargos,
    sr.abonos - COALESCE(SUM(a.abono), 0) as diferencia_abonos
FROM saldos_reportes sr
LEFT JOIN auxiliares a ON a.codigo = sr.codigo
    AND a.team_id = sr.team_id
WHERE sr.team_id = 49
    AND sr.codigo = '10501001'
GROUP BY sr.codigo, sr.cargos, sr.abonos;
```

---

## Rollback (Si algo falla)

### Deshabilitar Fase 2 inmediatamente
```bash
# En .env
SALDOS_AUTO_UPDATE=false

# Limpiar config
php artisan config:clear
```

### Regenerar saldos manualmente
```php
// En cualquier controller o comando
app(\App\Services\SaldosService::class)->recalcularTodosSaldos($team_id, $ejercicio, $periodo);

// O usar método original
app(\App\Http\Controllers\ReportesController::class)->ContabilizaReporte($ejercicio, $periodo, $team_id);
```

---

## Testing Checklist

Antes de habilitar en producción, verificar:

- [ ] Queue worker corre sin errores durante 24h
- [ ] Crear póliza dispara job correctamente
- [ ] Logs no muestran errores de ActualizarSaldosCuentaJob
- [ ] Saldos en dashboard coinciden con auxiliares
- [ ] Balanza de comprobación coincide con indicadores
- [ ] Performance: < 500ms por update incremental
- [ ] Caché se invalida correctamente
- [ ] Modificar/eliminar póliza también funciona
- [ ] Testing en 3+ teams diferentes
- [ ] No hay race conditions (múltiples users simultáneos)

---

## Variables de Entorno

Agregar a `.env`:
```env
# FASE 2: Event-Driven Saldos (POR DEFECTO: false)
SALDOS_AUTO_UPDATE=false

# Caché TTL (segundos)
SALDOS_CACHE_TTL=300

# Queue para saldos
SALDOS_QUEUE=saldos

# Timeout de jobs (segundos)
SALDOS_JOB_TIMEOUT=120

# Reintentos de jobs
SALDOS_JOB_TRIES=3

# Logging detallado (solo para desarrollo)
SALDOS_DETAILED_LOGGING=false
```

---

## Próximos Pasos

1. **Semana 1-2**: Testing en desarrollo con `SALDOS_AUTO_UPDATE=true`
2. **Semana 3**: Testing en staging con teams piloto
3. **Semana 4**: Validación de performance y estabilidad
4. **Semana 5**: Rollout gradual a producción (20% teams)
5. **Semana 6**: Rollout completo (100% teams)
6. **Semana 7+**: Monitoreo continuo, ajustes finos

---

## Soporte

En caso de problemas:
1. Revisar logs: `storage/logs/laravel.log`
2. Verificar queue worker: `sudo supervisorctl status`
3. Deshabilitar Fase 2: `SALDOS_AUTO_UPDATE=false`
4. Regenerar saldos manualmente: Ver sección Rollback
5. Reportar issue con contexto completo (team_id, periodo, error)

---

**Última actualización**: 2026-02-16
**Versión**: 2.0.0
**Estado**: FASE 2 IMPLEMENTADA - DESHABILITADA POR DEFECTO (TESTING PENDIENTE)
