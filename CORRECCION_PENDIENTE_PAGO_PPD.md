# Corrección Automática de `pendiente_pago` para Facturas PPD

## 📋 Problema Identificado

El campo `pendiente_pago` en la tabla `facturas` es crítico para el funcionamiento de los complementos de pago, pero **no se estaba llenando automáticamente** al timbrar facturas con método de pago PPD.

### Impacto:
- ❌ Facturas PPD no aparecen en el selector de complementos de pago
- ❌ No se pueden generar complementos de pago para facturas sin `pendiente_pago`
- ❌ El flujo de cobranza se interrumpe

### ¿Por qué es importante?

El archivo `PagosResource.php` línea 153 filtra facturas disponibles para complemento con:

```php
->where('pendiente_pago', '>', 0)
```

Si `pendiente_pago` es `NULL` o `0`, **la factura no aparece** aunque sea PPD y esté timbrada.

---

## ✅ Solución Implementada

### **1. Llenado Automático al Timbrar**

Ahora, al timbrar una factura PPD, automáticamente se llena `pendiente_pago` con el total de la factura (considerando tipo de cambio).

**Ubicación:** `app/Filament/Clusters/tiadmin/Resources/FacturasResource.php`

**Código agregado (3 ubicaciones):**

```php
// Si es PPD, llenar pendiente_pago con el total
if ($facturamodel->forma === 'PPD') {
    $facturamodel->pendiente_pago = $facturamodel->total * ($facturamodel->tcambio ?? 1);
}
```

**Se aplica en:**
- Timbrado desde acción "Timbrar"
- Timbrado desde acción de tabla
- Timbrado masivo

---

### **2. Acción Masiva de Corrección**

Nueva acción en la tabla de facturas: **"Corregir Pendiente Pago PPD"**

**Ubicación:** Tabla de facturas → Botón de acciones (header)

**Funcionalidad:**
1. ✅ Busca todas las facturas PPD timbradas del tenant actual
2. ✅ Verifica si tienen complemento de pago aplicado
3. ✅ Para las que NO tienen complemento:
   - Compara `pendiente_pago` vs `total * tcambio`
   - Si no coinciden (tolerancia 0.10), actualiza el campo
4. ✅ Muestra reporte de cuántas se corrigieron

**Características:**
- 🔒 Requiere confirmación antes de ejecutar
- 📊 Muestra estadísticas al finalizar
- ⚡ Solo afecta facturas del tenant actual
- 🎯 Ignora facturas que ya tienen complemento de pago

---

## 📊 Cómo Funciona

### **Flujo Actual (Mejorado):**

```
1. Usuario crea factura PPD
   ↓
2. Usuario timbra factura
   ↓
3. ✨ AUTOMÁTICO: Se llena pendiente_pago = total * tcambio
   ↓
4. Factura aparece en selector de complementos de pago
   ↓
5. Usuario genera complemento de pago
   ↓
6. Sistema decrementa pendiente_pago al aplicar pago
```

### **Para Facturas Existentes:**

```
1. Ir a /tiadmin/facturas
   ↓
2. Click en acción "Corregir Pendiente Pago PPD"
   ↓
3. Confirmar
   ↓
4. ✅ Todas las facturas PPD se corrigen automáticamente
```

---

## 🔧 Detalles Técnicos

### **Campo:** `facturas.pendiente_pago`
- **Tipo:** DECIMAL
- **Propósito:** Controlar saldo pendiente de cobro para PPD
- **Se llena:** Al timbrar (si es PPD)
- **Se decrementa:** Al aplicar complementos de pago (líneas 328, 400, 475, 611 de PagosResource.php)
- **Se usa en:** Filtro de facturas disponibles para complemento (línea 153 de PagosResource.php)

### **Lógica de Corrección:**

```php
// Para cada factura PPD timbrada
$tieneComplemento = ParPagos::where('uuidrel', $factura->uuid)->exists();

if (!$tieneComplemento) {
    $totalFactura = $factura->total * ($factura->tcambio ?? 1);
    $pendienteActual = $factura->pendiente_pago ?? 0;

    // Si no coinciden (con tolerancia)
    if (abs($pendienteActual - $totalFactura) > 0.10) {
        $factura->pendiente_pago = $totalFactura;
        $factura->save();
    }
}
```

---

## 📝 Casos de Uso

### **Caso 1: Nueva Factura PPD**

```
✅ Se timbra factura PPD de $10,000.00 MXN
✅ pendiente_pago se llena automáticamente con 10000.00
✅ Aparece en selector de complementos de pago
```

### **Caso 2: Factura PPD en Dólares**

```
✅ Se timbra factura PPD de $1,000.00 USD (TC: 18.50)
✅ pendiente_pago = 1000.00 * 18.50 = 18,500.00
✅ Aparece en selector con monto en MXN
```

### **Caso 3: Facturas Existentes (Históricas)**

```
❌ Factura PPD timbrada hace 2 meses
❌ pendiente_pago = NULL o 0
❌ NO aparece en selector
   ↓
✅ Usuario ejecuta "Corregir Pendiente Pago PPD"
✅ pendiente_pago se actualiza
✅ Ahora SÍ aparece en selector
```

### **Caso 4: Factura PUE**

```
✅ Se timbra factura PUE
✅ pendiente_pago NO se llena (no aplica)
✅ No requiere complemento de pago
```

---

## 🎯 Beneficios

1. ✅ **Automatización completa** - No requiere acción manual
2. ✅ **Corrige historial** - Acción masiva para facturas existentes
3. ✅ **Previene errores** - No más facturas PPD "perdidas"
4. ✅ **Flujo sin interrupciones** - Complementos de pago funcionan correctamente
5. ✅ **Respeta lógica existente** - Solo agrega, no cambia funcionalidad

---

## 🔍 Verificación

### **Comprobar que funciona:**

#### **1. Nuevas Facturas:**
```
1. Crear factura con método PPD
2. Timbrar factura
3. Verificar en BD: SELECT pendiente_pago FROM facturas WHERE id = X
4. Debe mostrar el total de la factura
```

#### **2. Selector de Complementos:**
```
1. Ir a /tiadmin/pagos
2. Crear nuevo complemento de pago
3. Seleccionar cliente
4. Verificar que aparezcan todas las facturas PPD pendientes
```

#### **3. Corrección Masiva:**
```
1. Ir a /tiadmin/facturas
2. Click en "Corregir Pendiente Pago PPD"
3. Confirmar
4. Debe mostrar: "✅ X facturas corregidas"
```

---

## 📂 Archivos Modificados

### **1. FacturasResource.php**

**Cambios:**
- ➕ Lógica automática al timbrar (3 ubicaciones, líneas 1309-1313 aprox.)
- ➕ Nueva acción "Corregir Pendiente Pago PPD" (líneas 1971-2018 aprox.)

**Ubicación:**
```
app/Filament/Clusters/tiadmin/Resources/FacturasResource.php
```

---

## ⚠️ Consideraciones

### **Cuándo se llena `pendiente_pago`:**
- ✅ Al timbrar facturas PPD (automático)
- ✅ Al ejecutar acción masiva (manual)

### **Cuándo NO se llena:**
- ❌ Facturas con método PUE (no aplica)
- ❌ Facturas no timbradas (aún no tienen UUID)

### **Cuándo se modifica:**
- 🔄 Al aplicar complemento de pago (se decrementa)
- 🔄 Al cancelar complemento de pago (se incrementa, líneas 328 de PagosResource.php)

---

## 🚀 Despliegue en Producción

### **Pasos:**

```bash
# 1. Actualizar código
git pull origin main

# 2. Limpiar caché
php artisan optimize:clear

# 3. Ejecutar corrección masiva (opcional)
# Ir a /tiadmin/facturas → Click en "Corregir Pendiente Pago PPD"
```

**Tiempo estimado:** 2-3 minutos

**Requiere downtime:** NO

---

## 💡 Preguntas Frecuentes

### **¿Qué pasa con las facturas que ya tienen complemento parcial?**
No se modifican. La lógica respeta el `pendiente_pago` actual si ya hay complementos aplicados.

### **¿Funciona con facturas en dólares?**
Sí. Se considera el tipo de cambio (`tcambio`) para calcular el monto en MXN.

### **¿Qué pasa si ejecuto la corrección masiva varias veces?**
No hay problema. Solo actualiza las que realmente lo necesitan (tolerancia de 0.10).

### **¿Afecta a facturas PUE?**
No. La lógica solo aplica para facturas con `forma = 'PPD'`.

---

**Fecha de implementación:** 12 de Febrero de 2026
**Versión:** 1.0
**Autor:** Sistema TusImpuestos3
**Relacionado con:** Sistema de Colores y Automatización de Timbrado
