# Guía de Uso - Página de Reportes NIF

## 📍 Ubicación en el Sistema

La nueva página de reportes NIF se encuentra en:

**Navegación:** `Reportes > Reportes NIF (Normas)`

---

## 🎨 Características de la Interfaz

### Banner Informativo
Al inicio de la página verás un banner azul con:
- Descripción general de los reportes NIF
- 4 botones numerados con cada norma (B-6, B-3, B-4, B-2)
- Información sobre CINIF

### Sección de Botones de Acción

La página incluye **5 botones principales**:

#### 1️⃣ Balance General (NIF B-6) 🟢
**Color:** Verde
**Ícono:** Balanza
**Función:** Genera el Estado de Situación Financiera

**Contenido:**
- Activo Circulante y No Circulante
- Pasivo Circulante y No Circulante
- Capital Contable
- Resultado del Ejercicio
- Comparativo con año anterior
- Verificación de cuadre automática

**Uso:**
1. Clic en "Balance General (NIF B-6)"
2. Confirmar en modal "Generar Reporte"
3. Esperar notificación de éxito
4. El reporte se abre automáticamente para vista previa

---

#### 2️⃣ Estado de Resultados (NIF B-3) 🔵
**Color:** Azul
**Ícono:** Gráfica de barras
**Función:** Genera el Estado de Resultados Integral

**Contenido:**
- Ingresos Netos
- Costo de Ventas
- Utilidad Bruta (con % de margen)
- Gastos de Operación
- Utilidad de Operación
- Resultado Integral de Financiamiento
- Utilidad Antes de Impuestos
- Impuestos
- Utilidad Neta

**Columnas:**
- Periodo actual
- Acumulado del ejercicio

**Uso:**
1. Clic en "Estado de Resultados (NIF B-3)"
2. Confirmar generación
3. Vista previa con indicadores porcentuales

---

#### 3️⃣ Estado de Cambios en Capital (NIF B-4) 🟡
**Color:** Amarillo/Warning
**Ícono:** Flecha tendencia
**Función:** Genera el Estado de Cambios en el Capital Contable

**Contenido:**
- Saldo Inicial (ejercicio anterior)
- Movimientos del periodo:
  - Aportaciones de capital
  - Capitalización de utilidades
  - Traspaso a reserva legal
  - Dividendos decretados
  - Resultado del ejercicio
- Saldo Final

**Columnas:**
- Capital Social
- Aportaciones Futuras
- Prima en Acciones
- Utilidades Retenidas
- Reserva Legal
- Resultado del Ejercicio
- Total Capital

**Uso:**
1. Clic en "Estado de Cambios en Capital (NIF B-4)"
2. Confirmar generación
3. Ver movimientos tabulados

---

#### 4️⃣ Estado de Flujos de Efectivo (NIF B-2) 🟣
**Color:** Info/Morado
**Ícono:** Billetes
**Función:** Genera el Estado de Flujos de Efectivo (Método Indirecto)

**Contenido:**
- **Actividades de Operación**
  - Utilidad neta
  - Ajustes (depreciación, provisiones)
  - Cambios en activos/pasivos operativos

- **Actividades de Inversión**
  - Adquisición de activo fijo
  - Venta de activos

- **Actividades de Financiamiento**
  - Aportaciones de capital
  - Préstamos obtenidos/pagados
  - Dividendos pagados

- **Conciliación**
  - Efectivo inicial
  - Incremento/disminución neto
  - Efectivo final

**Uso:**
1. Clic en "Estado de Flujos de Efectivo (NIF B-2)"
2. Confirmar generación
3. Ver flujos clasificados

---

#### 5️⃣ Generar Todos los Reportes ⚪
**Color:** Gris
**Ícono:** Documentos múltiples
**Función:** Genera los 4 estados financieros de una sola vez

**Ventajas:**
- Ahorra tiempo
- Garantiza consistencia
- Genera paquete completo

**Uso:**
1. Clic en "Generar Todos los Reportes"
2. Confirmar en modal "Generar Estados Financieros Completos"
3. Esperar unos segundos
4. Notificación con resultado de los 4 reportes

**Nota:** Si algún reporte falla, se muestra notificación con detalles específicos.

---

## 🎯 Características de la Página

### 📊 Paneles Informativos

La página incluye 3 paneles horizontales:

#### Panel 1: Características ✅
- Formato profesional
- Comparativos anuales
- Verificación de cuadre
- Indicadores automáticos

#### Panel 2: Formatos 📄
- PDF de alta calidad
- Listo para imprimir
- Vista previa integrada
- Descarga directa

#### Panel 3: Normatividad 📖
- NIF 2025 vigentes
- Estándar CINIF
- Cumplimiento SAT
- Auditoría compatible

### ⚠️ Panel de Recomendaciones

Banner amarillo con tips:
- Verificar periodo cerrado
- Actualización automática al cargar
- Usar "Generar Todos" para paquete completo
- Ubicación de archivos: `public/TMPCFDI/`

---

## 🔄 Flujo de Trabajo

### Proceso Estándar

```
1. Usuario entra a la página
   ↓
2. Sistema actualiza saldos automáticamente
   ↓
3. Notificación: "Datos actualizados"
   ↓
4. Usuario selecciona reporte
   ↓
5. Confirma generación en modal
   ↓
6. Sistema genera PDF
   ↓
7. Vista previa automática
   ↓
8. Opciones: Imprimir, Descargar, Cerrar
```

---

## 🔔 Notificaciones

La página usa notificaciones Filament en el centro de la pantalla:

### ✅ Notificación de Éxito
**Verde**
- "Datos actualizados" (al cargar)
- "Balance General generado"
- "Estado de Resultados generado"
- etc.

### ❌ Notificación de Error
**Roja**
- "Error al actualizar"
- "Error al generar reporte"
- Con mensaje de error específico

### ⚠️ Notificación de Advertencia
**Amarilla**
- "Generación con errores"
- Lista de reportes que fallaron

---

## 📁 Archivos Generados

Todos los PDFs se guardan en:
```
public/TMPCFDI/
```

**Nombres de archivo:**
- `BalanceGeneralNIF_{team_id}.pdf`
- `EstadoResultadosNIF_{team_id}.pdf`
- `EstadoCambiosCapitalNIF_{team_id}.pdf`
- `EstadoFlujoEfectivoNIF_{team_id}.pdf`

**Nota:** Los archivos se sobrescriben cada vez que se generan.

---

## 🖼️ Vista Previa

Al generar cualquier reporte:

1. Se abre modal con vista previa del PDF
2. Ancho del modal: `7xl` (muy amplio)
3. Opciones disponibles:
   - 🖨️ **Imprimir:** Envía directamente a impresora
   - 💾 **Descargar:** Guarda el PDF
   - ❌ **Cerrar:** Cierra la vista previa

---

## 🎨 Diseño Responsivo

La página se adapta a diferentes pantallas:

### Desktop (> 1024px)
- Paneles en 3 columnas
- Banner con iconos grandes
- Botones en fila

### Tablet (768px - 1024px)
- Paneles en 2 columnas
- Banner compacto

### Mobile (< 768px)
- Todo en columna única
- Botones apilados
- Banner simplificado

---

## 🔐 Permisos y Seguridad

### Control de Acceso
- ✅ Requiere autenticación
- ✅ Scope por tenant (empresa)
- ✅ Solo ve datos de su empresa

### Datos Mostrados
- Periodo: Tomado de `Filament::getTenant()->periodo`
- Ejercicio: Tomado de `Filament::getTenant()->ejercicio`
- Empresa: Tomado de `Filament::getTenant()->id`

**No es posible ver datos de otras empresas.**

---

## 🐛 Solución de Problemas

### Problema: "Error al actualizar datos"

**Causa:** Fallo en `ContabilizaReporte()`

**Solución:**
```bash
php artisan tinker
>>> $controller = new \App\Http\Controllers\ReportesController();
>>> $controller->ContabilizaReporte(2025, 12, {team_id});
```

---

### Problema: "Error al generar reporte"

**Causa:** Datos faltantes en `saldos_reportes`

**Solución:**
```sql
SELECT COUNT(*) FROM saldos_reportes WHERE team_id = {tu_empresa};
```

Si retorna 0:
```bash
php artisan contabilizar --team-id={tu_empresa}
```

---

### Problema: "PDF vacío o sin datos"

**Causa:** Cuentas sin movimientos

**Solución:**
1. Verificar que existan auxiliares:
```sql
SELECT COUNT(*) FROM auxiliares
WHERE team_id = {tu_empresa}
AND a_ejercicio = {ejercicio};
```

2. Verificar catálogo de cuentas:
```sql
SELECT COUNT(*) FROM cat_cuentas
WHERE team_id = {tu_empresa};
```

---

### Problema: "No aparece en el menú"

**Causa:** Navegación oculta

**Solución:**
Verificar en `app/Filament/Pages/ReportesNIF.php` línea 18:
```php
protected static bool $shouldRegisterNavigation = true; // Debe ser true (por defecto)
```

Si está en `false`, cambiar a `true` o eliminar la línea.

---

### Problema: "Balance descuadrado"

**Causa:** Pólizas con diferencias

**Solución:**
```bash
php artisan balance:diagnosticar --team-id={tu_empresa}
```

O ejecutar reporte "Pólizas Descuadradas" desde "Reportes Contables".

---

## 📊 Datos que Necesitas Antes de Generar

### Para Balance General:
- ✅ Catálogo de cuentas completo (100-399)
- ✅ Saldos del ejercicio actual
- ✅ Saldos del ejercicio anterior (comparativo)

### Para Estado de Resultados:
- ✅ Cuentas de ingresos (400-499)
- ✅ Cuentas de costos (500-599)
- ✅ Cuentas de gastos (600-699)
- ✅ Cuentas de otros resultados (700-899)

### Para Cambios en Capital:
- ✅ Cuentas de capital (300-399)
- ✅ Movimientos de capital en auxiliares
- ✅ Resultado del ejercicio anterior

### Para Flujos de Efectivo:
- ✅ Cuentas de bancos (101-102)
- ⚠️ **Nota:** Algunos métodos requieren implementación específica

---

## 🎓 Capacitación Recomendada

### Usuario Final
1. Entender qué es cada reporte NIF
2. Saber cuándo generar cada uno
3. Interpretar los indicadores
4. Verificar el cuadre

### Administrador
1. Configurar catálogo de cuentas correctamente
2. Conocer cómo funciona `ContabilizaReporte()`
3. Diagnosticar problemas de balance
4. Mantener pólizas cuadradas

---

## 📞 Soporte Técnico

### Archivos Clave
```
app/Filament/Pages/ReportesNIF.php
app/Http/Controllers/ReportesNIFController.php
resources/views/filament/pages/reportes-nif.blade.php
resources/views/Reportes/BalanceGeneralNIF.blade.php
resources/views/Reportes/EstadoResultadosNIF.blade.php
resources/views/Reportes/EstadoCambiosCapitalNIF.blade.php
resources/views/Reportes/EstadoFlujoEfectivoNIF.blade.php
```

### Logs
```bash
# Ver errores de Laravel
tail -f storage/logs/laravel.log

# Ver errores de generación PDF
ls -lah public/TMPCFDI/
```

---

## ✨ Personalización

### Cambiar Colores
Editar `app/Filament/Pages/ReportesNIF.php`:

```php
Actions\Action::make('balance_general_nif')
    ->color('success') // Cambiar aquí: success, primary, warning, danger, info
```

### Cambiar Iconos
```php
->icon('heroicon-o-scale') // Cambiar por cualquier icono Heroicon
```

### Agregar Más Acciones
Agregar nuevo botón en el array de `Actions::make([...])`:

```php
Actions\Action::make('mi_nuevo_reporte')
    ->label('Mi Nuevo Reporte')
    ->icon('heroicon-o-document')
    ->color('info')
    ->action(function () {
        // Tu lógica aquí
    }),
```

---

## 📈 Métricas de Uso

### Información Útil para Seguimiento
- Reporte más generado
- Tiempo promedio de generación
- Errores frecuentes
- Empresas que más lo usan

*Implementar sistema de logging si es necesario.*

---

**Fecha de creación:** 09/02/2026
**Versión:** 1.0
**Autor:** Sistema TusImpuestos3
