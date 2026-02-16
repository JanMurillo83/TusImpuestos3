# Dashboard Visual de Monitoreo - Fase 3

## 📊 Vista General

Se ha implementado un dashboard visual completo en Filament para monitorear el sistema de saldos contables en tiempo real.

## 🎯 Componentes Implementados

### 1. Página Principal: SaldosMonitoring
**Ubicación**: `app/Filament/Pages/SaldosMonitoring.php`
**Ruta**: `/admin/teams/{team}/saldos-monitoring`
**Navegación**: Grupo "Herramientas"

**Características**:
- ✅ Actualización manual de datos en tiempo real
- ✅ Ejecución de health checks on-demand
- ✅ Resolución de alertas directamente desde la UI
- ✅ Vista completa del estado del sistema

### 2. Widgets de Estado

#### SaldosHealthWidget
**Ubicación**: `app/Filament/Widgets/SaldosHealthWidget.php`

Muestra 4 métricas clave:
- **Estado General del Sistema**: Pass/Warning/Fail con emoji visual
- **Checks Exitosos**: Número de verificaciones que pasaron
- **Advertencias**: Problemas que requieren atención
- **Fallos Críticos**: Problemas urgentes que requieren acción inmediata

Códigos de color:
- 🟢 Verde: Todo funcionando correctamente
- 🟡 Amarillo: Advertencias presentes
- 🔴 Rojo: Fallos críticos detectados

#### SaldosPerformanceWidget
**Ubicación**: `app/Filament/Widgets/SaldosPerformanceWidget.php`

Muestra 2 métricas de rendimiento:
- **Tiempo Promedio de Job**: En milisegundos (últimas 24h)
  - Verde: < 100ms
  - Amarillo: 100-500ms
  - Rojo: > 500ms
- **Tasa de Éxito**: Porcentaje de jobs completados exitosamente
  - Verde: ≥ 95%
  - Amarillo: 80-95%
  - Rojo: < 80%

Incluye gráfico sparkline de tendencia.

#### SaldosCacheStatsWidget
**Ubicación**: `app/Filament/Widgets/SaldosCacheStatsWidget.php`

Muestra 2 métricas de cache:
- **Cache Hit Rate**: Porcentaje de aciertos
  - Verde: ≥ 80%
  - Amarillo: 50-80%
  - Rojo: < 50%
- **Cache Hits vs Misses**: Contadores absolutos (24h)

Incluye gráfico sparkline de tendencia.

### 3. Gráficos de Rendimiento

#### SaldosJobPerformanceChart
**Ubicación**: `app/Filament/Widgets/SaldosJobPerformanceChart.php`
**Tipo**: Gráfico de línea (line chart)

Visualiza:
- Tiempo promedio de ejecución de jobs por hora
- Tendencia de rendimiento en las últimas 24 horas
- Eje Y: Milisegundos
- Eje X: Horas (formato HH:mm)

#### SaldosCacheHitRateChart
**Ubicación**: `app/Filament/Widgets/SaldosCacheHitRateChart.php`
**Tipo**: Gráfico combinado (line + bar)

Visualiza:
- Línea: Tasa de acierto de cache (%)
- Barras azules: Número de cache hits
- Barras rojas: Número de cache misses
- Doble eje Y (porcentaje + cantidad)

### 4. Panel de Alertas
**Vista**: Integrada en `saldos-monitoring.blade.php`

**Características**:
- Lista de últimas 10 alertas
- Badges de severidad con colores:
  - 🔴 Critical: Rojo
  - 🟡 Warning: Amarillo
  - 🔵 Info: Azul
- Botón "Resolver" para marcar alertas como atendidas
- Timestamp relativo (hace X minutos/horas)
- Opacidad reducida para alertas ya resueltas

### 5. Registro de Auditoría
**Vista**: Sección colapsable en `saldos-monitoring.blade.php`

**Muestra últimas 20 entradas**:
- Fecha y hora del cambio
- Código de cuenta afectada
- Campo modificado
- Valor anterior vs valor nuevo (formato numérico)
- Usuario que realizó el cambio (o "Sistema")

Tabla responsive con formato:
```
| Fecha              | Cuenta  | Campo | Anterior | Nuevo    | Usuario |
|--------------------|---------|-------|----------- |----------|---------|
| 2026-02-16 14:30:15| 40100000| final | 1,234.56  | 1,456.78 | Sistema |
```

### 6. Historial de Jobs
**Vista**: Sección colapsable en `saldos-monitoring.blade.php`

**Muestra últimos 50 jobs**:
- Fecha y hora de ejecución
- Job ID (truncado a 20 caracteres)
- Código de cuenta procesada
- Estado con badge:
  - 🟢 Completado
  - 🔴 Fallido
  - 🟡 Otros estados
- Duración en milisegundos
- Mensaje de error (si aplica, truncado a 50 caracteres)

## 🎨 Vista del Dashboard (Blade Template)
**Ubicación**: `resources/views/filament/pages/saldos-monitoring.blade.php`

**Estructura**:
```
┌─────────────────────────────────────────────┐
│ [Actualizar] [Ejecutar Health Check]       │
├─────────────────────────────────────────────┤
│ SaldosHealthWidget (4 stats)               │
├─────────────────────────────────────────────┤
│ SaldosPerformanceWidget │ SaldosCacheStats │
├─────────────────────────────────────────────┤
│ Estado del Sistema (Health Checks)         │
│ ✅ data_consistency: OK                    │
│ ✅ performance: OK                         │
│ ⚠️  cache_health: Hit rate bajo           │
├─────────────────────────────────────────────┤
│ JobPerformanceChart  │ CacheHitRateChart  │
├─────────────────────────────────────────────┤
│ Alertas Recientes                          │
│ [Lista de alertas con botones resolver]    │
├─────────────────────────────────────────────┤
│ ▼ Registro de Auditoría (colapsable)      │
│   [Tabla con últimos 20 cambios]          │
├─────────────────────────────────────────────┤
│ ▼ Historial de Jobs (colapsable)          │
│   [Tabla con últimos 50 jobs]             │
└─────────────────────────────────────────────┘
```

## 🔧 Funcionalidades Interactivas

### Botón "Actualizar"
```php
public function refreshData(): void
{
    $this->mount();
    $this->notify('success', 'Datos actualizados correctamente');
}
```
Recarga todos los datos del dashboard sin recargar la página completa.

### Botón "Ejecutar Health Check"
```php
public function runHealthCheck(): void
{
    $team_id = Filament::getTenant()->id;
    $this->healthChecks = SaldosHealthCheck::runAllChecks($team_id);
    $this->notify('success', 'Health check ejecutado correctamente');
}
```
Ejecuta todos los health checks en tiempo real y actualiza el panel.

### Botón "Resolver" en Alertas
```php
public function resolveAlert(int $alertId): void
{
    DB::table('saldos_alerts')
        ->where('id', $alertId)
        ->update(['resolved_at' => now()]);

    $this->mount();
    $this->notify('success', 'Alerta marcada como resuelta');
}
```
Marca una alerta como resuelta y actualiza la vista.

## 📍 Acceso al Dashboard

### Método 1: Navegación
1. Acceder al panel de administración de Filament
2. Seleccionar el Team correspondiente
3. En el menú lateral, ir a grupo "Herramientas"
4. Clic en "Monitor de Saldos"

### Método 2: URL Directa
```
/admin/teams/{team_id}/saldos-monitoring
```

## 🔄 Actualización Automática

El dashboard **NO** se actualiza automáticamente por defecto para evitar sobrecarga del servidor.

**Para actualización automática** (opcional):
1. Agregar livewire polling al componente:
```php
// En SaldosMonitoring.php
protected $pollInterval = 30000; // 30 segundos
```

2. O usar WebSockets/Broadcasting (requiere configuración adicional).

## 📊 Métricas Disponibles

### Desde SaldosMetrics Service:
- `getCacheStats()`: Estadísticas de cache por hora
- `getJobPerformance()`: Rendimiento de jobs por hora
- `getDashboardSummary()`: Resumen completo del sistema

### Desde SaldosHealthCheck Service:
- `runAllChecks()`: Ejecuta todos los health checks
- `checkDataConsistency()`: Verifica consistencia de datos
- `checkPerformance()`: Verifica rendimiento del sistema
- `checkQueueHealth()`: Estado de la cola de jobs
- `checkCacheHealth()`: Estado del sistema de cache
- `checkDatabaseHealth()`: Tiempo de respuesta de la base de datos

## 🎨 Personalización

### Cambiar Colores de Badges
Editar en `SaldosMonitoring.php`:
```php
public function getHealthStatusColor(string $status): string
{
    return match ($status) {
        'pass' => 'success',
        'warning' => 'warning',
        'fail' => 'danger',
        default => 'gray',
    };
}
```

### Cambiar Umbrales de Performance
Editar en los widgets:
```php
// SaldosPerformanceWidget.php
$performanceColor = $avgJobTime < 100 ? Color::Green :
                   ($avgJobTime < 500 ? Color::Yellow : Color::Red);

// SaldosCacheStatsWidget.php
$cacheColor = $hitRate >= 80 ? Color::Green :
             ($hitRate >= 50 ? Color::Yellow : Color::Red);
```

### Agregar más Widgets
1. Crear nuevo widget: `php artisan make:filament-widget MiWidget`
2. Registrar en `SaldosMonitoring.php`:
```php
protected function getHeaderWidgets(): array
{
    return [
        // ... widgets existentes
        \App\Filament\Widgets\MiWidget::class,
    ];
}
```

## 🧪 Testing

### Verificar que la página carga:
```bash
php artisan route:list --name=filament | grep -i saldos
```

### Generar datos de prueba:
```bash
# Ejecutar algunos jobs para generar métricas
php artisan saldos:phase status

# Crear alertas de prueba manualmente
php artisan tinker
>>> DB::table('saldos_alerts')->insert([
    'team_id' => 1,
    'alert_type' => 'test',
    'severity' => 'info',
    'title' => 'Alerta de Prueba',
    'message' => 'Esto es una prueba',
    'created_at' => now(),
]);
```

### Ejecutar health check:
```bash
php artisan saldos:health-check
```

## 📋 Checklist de Implementación

- ✅ Página principal de monitoreo
- ✅ Widget de estado general de salud
- ✅ Widget de métricas de rendimiento
- ✅ Widget de estadísticas de cache
- ✅ Gráfico de rendimiento de jobs
- ✅ Gráfico de tasa de acierto de cache
- ✅ Panel de alertas con resolución interactiva
- ✅ Tabla de auditoría (últimas 20 entradas)
- ✅ Tabla de historial de jobs (últimos 50)
- ✅ Botones de actualización manual
- ✅ Integración con services de Fase 3
- ✅ Vista responsive y collapsible
- ✅ Notificaciones de Filament

## 🚀 Próximos Pasos (Opcionales)

1. **Actualización en Tiempo Real**:
   - Implementar Laravel Echo + Pusher/WebSockets
   - Broadcasting de eventos cuando cambien métricas

2. **Exportación de Reportes**:
   - Botón para exportar métricas a Excel/PDF
   - Reportes programados por email

3. **Alertas Push**:
   - Notificaciones push cuando ocurran fallos críticos
   - Integración con Slack/Telegram

4. **Dashboard Multi-Team**:
   - Vista comparativa de múltiples teams
   - Rankings de rendimiento

5. **Drill-Down en Métricas**:
   - Clic en un punto del gráfico para ver detalles
   - Modal con información detallada del job/cache

## 📖 Referencias

- **Fase 3 Implementation**: `FASE3_IMPLEMENTATION.md`
- **Fase 2 Implementation**: `FASE2_IMPLEMENTATION.md`
- **Filament Documentation**: https://filamentphp.com/docs/3.x/panels/pages
- **Widgets Documentation**: https://filamentphp.com/docs/3.x/widgets/overview
- **Charts Documentation**: https://filamentphp.com/docs/3.x/widgets/charts

---

**Fecha de Implementación**: 2026-02-16
**Versión**: 1.0.0
**Estado**: ✅ Completamente Funcional
