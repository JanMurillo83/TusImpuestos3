# Reportes Contables NIF - TusImpuestos3

## 📋 Descripción General

Sistema de reportes contables conforme a las **Normas de Información Financiera (NIF)** vigentes en México para 2025.

---

## 🎯 Reportes Implementados

### 1. **Balance General (Estado de Situación Financiera) - NIF B-6**
**Ruta:** `/reportes/nif/balance-general?month={periodo}&year={ejercicio}`

**Características:**
- ✅ Clasificación circulante vs no circulante
- ✅ Comparativo con ejercicio anterior
- ✅ Verificación automática de cuadre contable
- ✅ Activo = Pasivo + Capital

**Estructura:**
```
ACTIVO
├── Activo Circulante (100-149)
├── Activo No Circulante (150-199)
└── TOTAL ACTIVO

PASIVO Y CAPITAL
├── Pasivo Circulante (200-249)
├── Pasivo No Circulante (250-299)
├── TOTAL PASIVO
├── Capital Contable (300-399)
│   └── Resultado del Ejercicio
└── TOTAL PASIVO + CAPITAL
```

**Ejemplo de uso:**
```php
GET /reportes/nif/balance-general?month=12&year=2025
```

---

### 2. **Estado de Resultados Integral - NIF B-3**
**Ruta:** `/reportes/nif/estado-resultados?month={periodo}&year={ejercicio}`

**Características:**
- ✅ Formato con márgenes de utilidad
- ✅ Columnas: Periodo y Acumulado
- ✅ Resultado integral de financiamiento
- ✅ Indicadores porcentuales

**Estructura:**
```
INGRESOS NETOS (400-499)
- COSTO DE VENTAS (500-599)
= UTILIDAD BRUTA
- GASTOS DE OPERACIÓN (600-699)
= UTILIDAD DE OPERACIÓN
+/- RESULTADO INTEGRAL DE FINANCIAMIENTO (702-703)
+/- OTROS INGRESOS Y GASTOS (700-701)
= UTILIDAD ANTES DE IMPUESTOS
- IMPUESTOS (800-899)
= UTILIDAD NETA
```

**Ejemplo de uso:**
```php
GET /reportes/nif/estado-resultados?month=6&year=2025
```

---

### 3. **Estado de Cambios en el Capital Contable - NIF B-4**
**Ruta:** `/reportes/nif/cambios-capital?month={periodo}&year={ejercicio}`

**Características:**
- ✅ Movimientos del periodo
- ✅ Saldo inicial vs saldo final
- ✅ Columnas por componente del capital
- ✅ Incluye reserva legal

**Columnas:**
- Capital Social (30001000)
- Aportaciones para Futuros Aumentos (30002000)
- Prima en Emisión de Acciones (30003000)
- Utilidades Retenidas (30004000)
- Reserva Legal (30005000)
- Resultado del Ejercicio
- Total Capital Contable

**Movimientos:**
- Aportaciones de capital
- Capitalización de utilidades
- Traspaso a reserva legal
- Dividendos decretados
- Resultado del ejercicio

**Ejemplo de uso:**
```php
GET /reportes/nif/cambios-capital?month=12&year=2025
```

---

### 4. **Estado de Flujos de Efectivo - NIF B-2**
**Ruta:** `/reportes/nif/flujo-efectivo?month={periodo}&year={ejercicio}`

**Características:**
- ✅ Método indirecto
- ✅ Tres secciones principales
- ✅ Conciliación de efectivo
- ✅ Ajustes por partidas no monetarias

**Estructura:**
```
ACTIVIDADES DE OPERACIÓN
├── Utilidad neta del ejercicio
├── (+) Ajustes que no requieren efectivo
│   ├── Depreciación y amortización
│   └── Provisión cuentas incobrables
└── (+/-) Cambios en activos y pasivos
    ├── Clientes
    ├── Inventarios
    ├── Proveedores
    └── Impuestos por pagar

ACTIVIDADES DE INVERSIÓN
├── Adquisición de activo fijo
└── Venta de activo fijo

ACTIVIDADES DE FINANCIAMIENTO
├── Aportaciones de capital
├── Obtención de préstamos
├── Pago de préstamos
└── Pago de dividendos

= INCREMENTO/DISMINUCIÓN NETO
+ Efectivo inicial
= EFECTIVO FINAL
```

**Ejemplo de uso:**
```php
GET /reportes/nif/flujo-efectivo?month=12&year=2025
```

---

## 🔧 Instalación y Configuración

### 1. Verificar rutas registradas
```bash
php artisan route:list | grep nif
```

### 2. Verificar tabla saldos_reportes
```sql
SELECT * FROM saldos_reportes WHERE team_id = {tu_empresa_id} LIMIT 10;
```

### 3. Probar generación de reporte
```bash
# Balance General
curl "http://localhost/reportes/nif/balance-general?month=12&year=2025"

# Estado de Resultados
curl "http://localhost/reportes/nif/estado-resultados?month=12&year=2025"
```

---

## 📊 Catálogo de Cuentas

### Clasificación por Rango:

| Código | Descripción | Reporte |
|--------|-------------|---------|
| 100-149 | Activo Circulante | Balance General |
| 150-199 | Activo No Circulante | Balance General |
| 200-249 | Pasivo Circulante | Balance General |
| 250-299 | Pasivo No Circulante | Balance General |
| 300-399 | Capital Contable | Balance General |
| 400-499 | Ingresos | Estado de Resultados |
| 500-599 | Costo de Ventas | Estado de Resultados |
| 600-699 | Gastos de Operación | Estado de Resultados |
| 700-701 | Otros Resultados | Estado de Resultados |
| 702-703 | Financiamiento | Estado de Resultados |
| 800-899 | Impuestos | Estado de Resultados |

---

## 🚀 Integración con Sistema Existente

### Método 1: Desde Filament Action
```php
use App\Http\Controllers\ReportesNIFController;

Html2MediaAction::make('Balance General NIF')
    ->label('Balance General NIF B-6')
    ->color('success')
    ->icon('heroicon-o-document-text')
    ->view('Reportes.BalanceGeneralNIF')
    ->filename('BalanceGeneralNIF')
    ->data(function () use ($team_id, $periodo, $ejercicio) {
        $controller = new ReportesNIFController();
        return $controller->balanceGeneralNIF(
            request()->merge(['month' => $periodo, 'year' => $ejercicio])
        );
    });
```

### Método 2: Desde Controlador
```php
public function generarReporteNIF(Request $request)
{
    $controller = new \App\Http\Controllers\ReportesNIFController();

    // Balance General
    $balance_url = $controller->balanceGeneralNIF($request);

    // Estado de Resultados
    $resultados_url = $controller->estadoResultadosNIF($request);

    return response()->json([
        'balance' => $balance_url,
        'resultados' => $resultados_url
    ]);
}
```

### Método 3: Descarga Directa
```php
use App\Http\Controllers\ReportesNIFController;

Route::get('/descargar-balance', function() {
    $controller = new ReportesNIFController();
    $pdf_url = $controller->balanceGeneralNIF(request());
    return redirect($pdf_url);
});
```

---

## 📝 Notas Importantes

### Preparación de Datos
Antes de generar cualquier reporte, el sistema ejecuta:
```php
app(ReportesController::class)->ContabilizaReporte($ejercicio, $periodo, $team_id);
```

Esto actualiza la tabla `saldos_reportes` con:
- Saldos anteriores
- Cargos del periodo
- Abonos del periodo
- Saldos finales
- Niveles de agrupación

### Verificación de Balance
El Balance General incluye verificación automática:
```php
$diferencia = abs($total_activo - $total_pasivo_capital);
$balance_cuadrado = $diferencia < 0.01; // Tolerancia de 1 centavo
```

Si el balance no cuadra, se muestra alerta roja con la diferencia.

---

## 🔍 Troubleshooting

### Problema: "No se generan datos"
**Solución:**
```bash
# Verificar que existan saldos
SELECT COUNT(*) FROM saldos_reportes WHERE team_id = {tu_empresa};

# Ejecutar contabilización manual
php artisan tinker
>>> app(ReportesController::class)->ContabilizaReporte(2025, 12, {team_id});
```

### Problema: "Balance descuadrado"
**Solución:**
```bash
# Diagnosticar balance
php artisan balance:diagnosticar --team-id={team_id}

# Verificar pólizas descuadradas
SELECT * FROM cat_polizas
WHERE ABS(total_cargo - total_abono) > 0.01
AND team_id = {team_id};
```

### Problema: "Error al generar PDF"
**Solución:**
```bash
# Verificar SnappyPDF
composer require barryvdh/laravel-snappy

# Instalar wkhtmltopdf
sudo apt-get install wkhtmltopdf

# Verificar permisos
chmod 755 public/TMPCFDI/
```

---

## 📈 Mejoras Futuras

### Estado de Flujos de Efectivo
Los siguientes métodos requieren implementación específica:

```php
// En ReportesNIFController.php - líneas 480-550
private function obtenerDepreciacion($team_id, $periodo)
private function cambioEnClientes($team_id, $periodo, $ejercicio)
private function cambioEnInventarios($team_id, $periodo, $ejercicio)
private function obtenerAdquisicionActivos($team_id, $periodo, $ejercicio)
// ... etc
```

**Implementar según tu lógica de negocio.**

### Personalización por Empresa
Agregar opciones de configuración:
- Logo personalizado
- Colores corporativos
- Pie de página con firmas
- Notas adicionales

---

## ✅ Cumplimiento Normativo

| NIF | Título | Estado |
|-----|--------|--------|
| **NIF B-6** | Estado de Situación Financiera | ✅ Implementado |
| **NIF B-3** | Estado de Resultados Integral | ✅ Implementado |
| **NIF B-4** | Estado de Cambios en Capital | ✅ Implementado |
| **NIF B-2** | Estado de Flujos de Efectivo | ⚠️ Parcial (requiere ajustes) |

---

## 📞 Soporte

Para dudas o mejoras:
1. Revisar documentación oficial CINIF: https://www.cinif.org.mx
2. Verificar archivos:
   - `app/Http/Controllers/ReportesNIFController.php`
   - `resources/views/Reportes/BalanceGeneralNIF.blade.php`
   - `resources/views/Reportes/EstadoResultadosNIF.blade.php`
   - `resources/views/Reportes/EstadoCambiosCapitalNIF.blade.php`
   - `resources/views/Reportes/EstadoFlujoEfectivoNIF.blade.php`

---

**Fecha de creación:** 09/02/2026
**Versión:** 1.0
**Compatible con:** Laravel 10+, NIF 2025
