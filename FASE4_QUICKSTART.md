# Fase 4: Quick Start Guide

## 🚀 Inicio Rápido

### Comandos Esenciales

```bash
# Ver reporte del sistema
php artisan saldos:maintenance report

# Precalentar cache (modo prueba)
php artisan saldos:maintenance cache-warm --dry-run

# Auto-corregir problemas (modo prueba)
php artisan saldos:maintenance auto-correct --dry-run

# Optimizar base de datos (modo prueba)
php artisan saldos:maintenance optimize --dry-run

# Mantenimiento completo (modo prueba)
php artisan saldos:maintenance all --dry-run

# Mantenimiento completo (REAL - aplicar cambios)
php artisan saldos:maintenance all
```

## 📊 ¿Qué hace cada servicio?

### 1. **SaldosIntelligence** - El Cerebro
- ✅ Aprende de tus patrones de uso
- ✅ Precarga datos antes de que los necesites
- ✅ Predice qué consultarás próximamente
- ✅ Optimiza horarios de acceso

### 2. **SaldosAutoCorrection** - El Doctor
- ✅ Detecta saldos incorrectos
- ✅ Corrige automáticamente inconsistencias
- ✅ Limpia cuentas sin movimientos
- ✅ Actualiza jerarquías desactualizadas

### 3. **SaldosQueryOptimizer** - El Mecánico
- ✅ Encuentra queries lentos
- ✅ Crea índices automáticamente
- ✅ Desfragmenta tablas
- ✅ Optimiza cache strategy

## 🔧 Setup en 3 Pasos

### Paso 1: Configurar .env

```env
# Habilitar Fase 4
SALDOS_PHASE4_ENABLED=true
SALDOS_INTELLIGENCE_ENABLED=true
SALDOS_AUTO_CORRECTION_ENABLED=true
SALDOS_QUERY_OPTIMIZER_ENABLED=true
```

### Paso 2: Programar Mantenimiento

Editar `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule): void
{
    // Precalentar cache cada hora (horario laboral)
    $schedule->command('saldos:maintenance cache-warm')
             ->hourly()
             ->between('8:00', '18:00')
             ->weekdays();

    // Auto-corrección diaria (2 AM)
    $schedule->command('saldos:maintenance auto-correct')
             ->dailyAt('02:00');

    // Optimización semanal (domingo 3 AM)
    $schedule->command('saldos:maintenance optimize')
             ->weeklyOn(0, '03:00');

    // Limpieza mensual
    $schedule->command('saldos:maintenance clean')
             ->monthlyOn(1, '04:00');
}
```

### Paso 3: Ejecutar Primera Vez

```bash
# Ver estado actual (sin cambios)
php artisan saldos:maintenance report

# Si todo bien, ejecutar mantenimiento
php artisan saldos:maintenance all
```

## 📈 Resultados Esperados

### Antes de Fase 4:
- ❌ Cache hit rate: 50-60%
- ❌ Queries lentos: 5-10 segundos
- ❌ Inconsistencias manuales
- ❌ Mantenimiento manual

### Después de Fase 4:
- ✅ Cache hit rate: 80-95%
- ✅ Queries lentos: < 100ms
- ✅ Auto-corrección automática
- ✅ Mantenimiento programado

## 🎯 Casos de Uso Comunes

### Problema: Cache Hit Rate Bajo

```bash
# 1. Ver análisis
php artisan saldos:maintenance report

# 2. Precalentar cache
php artisan saldos:maintenance cache-warm

# 3. Verificar mejora
php artisan saldos:maintenance report
```

### Problema: Saldos Inconsistentes

```bash
# 1. Detectar problemas (sin corregir)
php artisan saldos:maintenance auto-correct --dry-run

# 2. Corregir
php artisan saldos:maintenance auto-correct

# 3. Verificar en dashboard
# Dashboard > Monitor de Saldos > Audit Log
```

### Problema: Queries Lentos

```bash
# 1. Identificar queries lentos
php artisan saldos:maintenance report

# 2. Optimizar (crear índices)
php artisan saldos:maintenance optimize

# 3. Verificar mejora
php artisan saldos:maintenance report
```

## 🔍 Monitoreo

### Dashboard Filament
```
/admin/teams/{team_id}/saldos-monitoring
```

Ver en el dashboard:
- ✅ Alertas de Fase 4
- ✅ Audit log con correcciones automáticas
- ✅ Métricas de cache
- ✅ Performance de queries

### Logs
```bash
# Ver logs de correcciones
tail -f storage/logs/laravel.log | grep "auto-corrigiendo"

# Ver logs de optimización
tail -f storage/logs/laravel.log | grep "optimizing"
```

## 🚨 Troubleshooting

### "No se precargó nada"
**Causa**: No hay suficientes patrones de uso
**Solución**: Usar el sistema normalmente durante 1-2 días

### "Errores al crear índices"
**Causa**: Índices ya existen
**Solución**: Normal, usar `--dry-run` primero

### "Auto-corrección no encuentra problemas"
**Causa**: Sistema ya está consistente (¡bien!)
**Solución**: No hacer nada

## 📚 Documentación Completa

Ver `FASE4_IMPLEMENTATION.md` para:
- API completa de servicios
- Arquitectura detallada
- Ejemplos avanzados
- Integración con otras fases

## ✅ Checklist Post-Instalación

- [ ] Configurar `.env` con variables de Fase 4
- [ ] Programar tareas en `Kernel.php`
- [ ] Ejecutar primer mantenimiento: `php artisan saldos:maintenance all --dry-run`
- [ ] Revisar resultados y ejecutar real: `php artisan saldos:maintenance all`
- [ ] Verificar dashboard muestra datos correctos
- [ ] Configurar alertas por email (opcional)
- [ ] Documentar para el equipo

---

**Fase 4 Instalada**: 2026-02-16
**Status**: ✅ Ready to Use
