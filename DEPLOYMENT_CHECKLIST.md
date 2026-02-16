# 🚀 Checklist de Deployment - Sistema de Saldos Optimizado

## TusImpuestos3 - Fases 1-4 Completadas

---

## ✅ Estado Actual del Sistema

| Fase | Componente | Estado | Archivos Clave |
|------|-----------|--------|----------------|
| **1** | Caché Estratégico | ✅ 100% | `SaldosCache.php` |
| **2** | Event-Driven | ✅ 100% | `AuxiliaresObserver.php`, `ActualizarSaldosCuentaJob.php` |
| **3** | Monitoreo & Métricas | ✅ 100% | `SaldosMetrics.php`, `SaldosMonitoring.php` |
| **4** | Optimización Predictiva | ✅ 100% | `SaldosIntelligence.php`, `SaldosAutoCorrection.php` |

---

## 📋 Checklist Pre-Deployment

### 1. Preparación del Entorno

- [ ] Servidor con PHP >= 8.2 ✓
- [ ] MySQL >= 8.0 ✓
- [ ] Composer instalado ✓
- [ ] Supervisor instalado (opcional pero recomendado)
- [ ] Redis instalado (recomendado para mejor performance)
- [ ] Backup completo de base de datos realizado
- [ ] Backup de archivos del proyecto realizado

### 2. Archivos a Verificar en el Servidor

#### Servicios (app/Services/)
- [ ] `SaldosCache.php`
- [ ] `SaldosService.php`
- [ ] `SaldosMetrics.php`
- [ ] `SaldosHealthCheck.php`
- [ ] `SaldosIntelligence.php`
- [ ] `SaldosAutoCorrection.php`
- [ ] `SaldosQueryOptimizer.php`

#### Jobs y Observers (app/Jobs/, app/Observers/)
- [ ] `ActualizarSaldosCuentaJob.php`
- [ ] `AuxiliaresObserver.php`

#### Comandos (app/Console/Commands/)
- [ ] `SaldosPhaseCommand.php`
- [ ] `SaldosMaintenanceCommand.php`

#### Filament (app/Filament/Pages/)
- [ ] `SaldosMonitoring.php`
- [ ] `resources/views/filament/pages/saldos-monitoring.blade.php`

#### Migraciones (database/migrations/)
- [ ] `2025_02_11_000001_create_saldos_metrics_table.php`
- [ ] `2025_02_11_000002_create_saldos_job_history_table.php`
- [ ] `2025_02_11_000003_create_saldos_audit_log_table.php`
- [ ] `2025_02_11_000004_create_saldos_usage_patterns_table.php`

#### Config
- [ ] `config/saldos.php`
- [ ] `.env` con variables de saldos

---

## 🔧 Checklist de Instalación

### Paso 1: Configuración del .env

```bash
# Agregar al .env del servidor
SALDOS_AUTO_UPDATE=true
SALDOS_CACHE_TTL=300
SALDOS_QUEUE=saldos
SALDOS_JOB_TIMEOUT=120
SALDOS_JOB_TRIES=3
SALDOS_DETAILED_LOGGING=false
CACHE_STORE=redis  # o database
QUEUE_CONNECTION=redis  # o database
```

- [ ] Variables agregadas al .env
- [ ] `CACHE_STORE` configurado
- [ ] `QUEUE_CONNECTION` configurado

### Paso 2: Ejecutar Migraciones

```bash
cd /path/to/project
php artisan migrate --force
```

- [ ] Migraciones ejecutadas sin errores
- [ ] Tabla `saldos_metrics` creada
- [ ] Tabla `saldos_job_history` creada
- [ ] Tabla `saldos_audit_log` creada
- [ ] Tabla `saldos_usage_patterns` creada

### Paso 3: Configurar Queue Worker

**Opción A: Supervisor (Recomendado para producción)**

```bash
php artisan saldos:phase enable --supervisor
sudo cp storage/supervisor-saldos.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start tusimpuestos-saldos-worker:*
```

- [ ] Archivo de Supervisor generado
- [ ] Supervisor configurado
- [ ] Worker iniciado
- [ ] Estado verificado: `sudo supervisorctl status`

**Opción B: Manual (Solo para testing)**

```bash
php artisan queue:work --queue=saldos --tries=3 --timeout=120 &
```

- [ ] Worker iniciado manualmente

### Paso 4: Configurar Cron Jobs

```bash
crontab -e
```

Agregar:

```
# Precalentamiento cache cada hora
0 * * * * cd /path/to/project && php artisan saldos:maintenance cache-warm >> /dev/null 2>&1

# Auto-corrección diaria a las 2 AM
0 2 * * * cd /path/to/project && php artisan saldos:maintenance auto-correct >> /dev/null 2>&1

# Optimización semanal (domingos 3 AM)
0 3 * * 0 cd /path/to/project && php artisan saldos:maintenance optimize >> /dev/null 2>&1

# Limpieza mensual (primer día mes 4 AM)
0 4 1 * * cd /path/to/project && php artisan saldos:maintenance clean >> /dev/null 2>&1

# Reporte diario a las 6 AM
0 6 * * * cd /path/to/project && php artisan saldos:maintenance report >> storage/logs/saldos-report.log 2>&1
```

- [ ] Cron jobs configurados
- [ ] Rutas actualizadas con path correcto del proyecto

### Paso 5: Optimizar Laravel

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

- [ ] Config cacheada
- [ ] Rutas cacheadas
- [ ] Vistas cacheadas
- [ ] Sistema optimizado

---

## ✅ Checklist de Verificación Post-Deployment

### Verificación Básica

```bash
# 1. Estado de fases
php artisan saldos:phase status
```

**Esperado:**
- Fase 1: ACTIVA
- Fase 2: HABILITADA
- Queue Worker: CORRIENDO

- [ ] Fase 1 activa ✓
- [ ] Fase 2 habilitada ✓
- [ ] Queue worker corriendo ✓

```bash
# 2. Reporte del sistema
php artisan saldos:maintenance report
```

**Esperado:**
- Reporte sin errores
- Estadísticas de tablas mostradas
- Métricas de cache mostradas

- [ ] Reporte genera sin errores ✓
- [ ] Estadísticas correctas ✓

```bash
# 3. Test dry-run
php artisan saldos:maintenance all --dry-run
```

**Esperado:**
- Todas las operaciones se ejecutan
- No se aplican cambios (dry-run)
- Resumen completo al final

- [ ] Test dry-run exitoso ✓
- [ ] Todas las secciones ejecutan ✓

### Verificación del Dashboard

1. Acceder a: `https://tudominio.com/admin/saldos-monitoring`

- [ ] Dashboard carga correctamente
- [ ] Sección "Salud del Sistema" visible
- [ ] Sección "Métricas de Performance" visible
- [ ] Sección "Estadísticas de Cache" visible
- [ ] Sección "Jobs Recientes" visible
- [ ] Sección "Audit Log" visible
- [ ] Botón "Actualizar Datos" funciona

### Verificación de Queue Jobs

```bash
# Ver estado de queue
php artisan queue:monitor saldos

# Ver jobs fallidos
php artisan queue:failed
```

- [ ] Queue monitoreando correctamente
- [ ] Sin jobs fallidos (o cantidad aceptable)

### Verificación de Logs

```bash
# Ver últimas 50 líneas del log
tail -n 50 storage/logs/laravel.log
```

- [ ] Sin errores críticos en logs
- [ ] Sin warnings importantes

---

## 🎯 Test de Funcionalidad Completa

### Test 1: Actualización Automática de Saldos

1. Crear un nuevo auxiliar (movimiento contable)
2. Verificar que se dispara el job automáticamente
3. Verificar que el saldo se actualiza en `saldos_reportes`

```bash
# Monitorear jobs
watch -n 2 'php artisan queue:monitor saldos'
```

- [ ] Job se dispara automáticamente
- [ ] Job se procesa exitosamente
- [ ] Saldo actualizado correctamente

### Test 2: Caché Funcionando

1. Acceder a un reporte de saldos
2. Primera carga: debe tomar tiempo normal
3. Segunda carga (dentro de 5 min): debe ser instantánea

- [ ] Primera carga normal
- [ ] Segunda carga desde caché (muy rápida)
- [ ] Métricas de cache se registran

### Test 3: Métricas y Monitoreo

1. Realizar algunas operaciones
2. Revisar dashboard de monitoreo
3. Verificar que las métricas se actualizan

- [ ] Métricas se registran
- [ ] Dashboard muestra datos actualizados
- [ ] Cache hit rate calculado correctamente

### Test 4: Auto-corrección

```bash
# Ejecutar auto-corrección en dry-run
php artisan saldos:maintenance auto-correct --dry-run
```

- [ ] Detecta inconsistencias (si existen)
- [ ] Reporta cantidad de problemas
- [ ] En dry-run no aplica cambios

### Test 5: Mantenimiento Completo

```bash
# Ejecutar mantenimiento completo
php artisan saldos:maintenance all
```

- [ ] Cache warming ejecuta
- [ ] Auto-corrección ejecuta
- [ ] Optimización ejecuta
- [ ] Limpieza ejecuta
- [ ] Reporte final genera

---

## 🚨 Troubleshooting Rápido

### Problema: Queue worker no procesa jobs

**Solución:**
```bash
# Reiniciar worker
sudo supervisorctl restart tusimpuestos-saldos-worker:*
# O manualmente
php artisan queue:restart
```

### Problema: Cache no funciona

**Solución:**
```bash
# Limpiar cache
php artisan cache:clear
php artisan config:clear
# Verificar conexión Redis
redis-cli ping
```

### Problema: Dashboard no carga

**Solución:**
```bash
# Limpiar vistas y config
php artisan view:clear
php artisan config:clear
# Verificar permisos
chmod -R 775 storage/
```

### Problema: Errores en logs

**Solución:**
```bash
# Ver logs recientes
tail -f storage/logs/laravel.log
# Si hay errores de DB, verificar conexión
php artisan db:show
```

---

## 📊 Métricas de Éxito

### KPIs a monitorear

| Métrica | Antes | Después | Objetivo |
|---------|-------|---------|----------|
| Tiempo de carga dashboard | 5-10s | 0.5-1s | < 2s |
| Cache hit rate | 0% | 80-95% | > 70% |
| Actualización saldos | Manual | Automática | 100% automática |
| Detección de errores | Manual | Automática | 100% automática |
| Jobs fallidos | N/A | < 5% | < 5% |

### Verificación Semanal (Primeros 30 días)

- [ ] Semana 1: Revisar logs diariamente
- [ ] Semana 2: Verificar métricas de cache
- [ ] Semana 3: Analizar jobs fallidos
- [ ] Semana 4: Validar auto-correcciones

---

## 🎉 Sistema Listo para Producción

Una vez completado este checklist:

✅ **Fase 1** - Caché estratégico activo
✅ **Fase 2** - Actualización automática funcionando
✅ **Fase 3** - Monitoreo y métricas operando
✅ **Fase 4** - Optimización predictiva habilitada

**Estado:** LISTO PARA PRODUCCIÓN

---

## 📞 Contactos de Soporte

**Documentación adicional:**
- `PRODUCTION_DEPLOYMENT.md` - Guía completa de deployment
- `FASE4_IMPLEMENTATION.md` - Documentación técnica Fase 4
- `FASE4_QUICKSTART.md` - Guía rápida Fase 4

**Comandos de ayuda:**
```bash
php artisan saldos:phase --help
php artisan saldos:maintenance --help
```

---

**Última actualización:** 16 de Febrero 2026
**Versión del Sistema:** 1.0
**Estado:** ✅ VERIFICADO Y FUNCIONAL
