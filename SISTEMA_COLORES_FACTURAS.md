# Sistema de Identificación Visual de Facturas

## Descripción General

El módulo de facturas (`/tiadmin/facturas`) utiliza un sistema de colores tenues para identificar rápidamente el estado de procesamiento de cada factura sin crear saturación visual.

---

## Esquema de Colores

### 🟢 **Verde Tenue** (`#f0fdf4`)
**Estado:** Factura completamente procesada
- ✅ Tiene póliza contable asociada
- ✅ Tiene cobro registrado en movimientos bancarios
- **Significado:** La factura está completamente procesada en el sistema contable

### 🟡 **Amarillo Tenue** (`#fffbeb`)
**Estado:** Factura con póliza pendiente de cobro
- ✅ Tiene póliza contable asociada
- ❌ No tiene cobro registrado (o está parcialmente pagada)
- **Significado:** La factura está contabilizada pero pendiente de cobro

### 🔵 **Azul Tenue** (`#eff6ff`)
**Estado:** Factura timbrada sin contabilizar
- ✅ Factura timbrada correctamente
- ❌ No tiene póliza contable asociada
- **Significado:** La factura debe ser contabilizada en `/emitcfdi/cfdiei`

### 🔴 **Rojo Tenue** (`#fef2f2`)
**Estado:** PPD sin complemento de pago
- ⚠️ Método de pago PPD (Pago en Parcialidades o Diferido)
- ❌ No tiene complemento de pago timbrado
- **Significado:** Se requiere generar complemento de pago para esta factura

### ⚪ **Blanco** (default)
**Estado:** Factura no timbrada
- Factura en estado "Activa"
- Aún no ha sido timbrada con el PAC

---

## Nuevas Columnas

### 📋 **Columna "Póliza"**
Muestra el folio de la póliza contable asociada a la factura:
- 🟢 **PV-XXX** (Verde): Tiene póliza asociada
- 🟡 **Sin póliza** (Amarillo): No tiene póliza
- ⚪ **N/A** (Gris): Factura no timbrada

### 💰 **Columna "Estado Cobro"**
Muestra el estado del cobro de la factura:
- 🟢 **Pagado**: Factura completamente cobrada
- 🟡 **Parcial**: Factura con pagos parciales
- 🔴 **Pendiente**: Factura sin cobros registrados
- ⚪ **Sin registro**: No hay registro en `ingresos_egresos`
- ⚪ **N/A**: Factura no timbrada

### ✅ **Columna "Comp. Pago"** (Ya existente, mejorada)
Indica si la factura PPD tiene complemento de pago:
- ✅ Tiene complemento(s) de pago
- ❌ Sin complemento de pago
- ➖ No aplica (método de pago PUE)

---

## Prioridad de Colores

Cuando una factura cumple múltiples condiciones, se aplica el siguiente orden de prioridad:

1. 🔴 **Rojo** - PPD sin complemento (máxima prioridad)
2. 🟢 **Verde** - Factura completa
3. 🟡 **Amarillo** - Con póliza sin cobro
4. 🔵 **Azul** - Timbrada sin póliza
5. ⚪ **Blanco** - No timbrada (default)

---

## Flujo de Procesamiento

```
📄 Factura Creada (Blanco)
    ↓
🔵 Timbrada sin póliza (Azul) → Ir a /emitcfdi/cfdiei
    ↓
🟡 Con póliza sin cobro (Amarillo) → Ir a /movbancos
    ↓
🟢 Completamente procesada (Verde) ✓
```

### Para facturas PPD:
```
📄 Factura PPD Timbrada
    ↓
🔴 Sin complemento (Rojo) → Generar complemento de pago
    ↓
✅ Con complemento → Seguir flujo normal
```

---

## Relaciones en Base de Datos

### Factura → Póliza
- `facturas.uuid` → `almacencfdis.UUID`
- `almacencfdis.id` → `cat_polizas.idcfdi`

### Factura → Cobro
- `facturas.uuid` → `almacencfdis.UUID`
- `almacencfdis.id` → `ingresos_egresos.xml_id`
- `ingresos_egresos.pendientemxn` = 0 → Pagado

### Factura → Complemento de Pago
- `facturas.uuid` → `par_pagos.uuidrel`

---

## Optimización de Rendimiento

El sistema utiliza JOINs precargados para minimizar consultas a la base de datos:

```sql
SELECT facturas.*,
       cat_polizas.tipo as poliza_tipo,
       cat_polizas.folio as poliza_folio,
       ingresos_egresos.pendientemxn,
       ingresos_egresos.totalmxn
FROM facturas
LEFT JOIN almacencfdis ON facturas.uuid = almacencfdis.UUID
LEFT JOIN cat_polizas ON almacencfdis.id = cat_polizas.idcfdi
LEFT JOIN ingresos_egresos ON almacencfdis.id = ingresos_egresos.xml_id
```

---

## Archivos Modificados

- `app/Filament/Clusters/tiadmin/Resources/FacturasResource.php`
  - Métodos agregados: `getRecordColorClass()`, `tienePoliza()`, `estaCobrada()`, `tieneComplemento()`
  - Columnas agregadas: `poliza`, `estado_cobro`
  - JOINs optimizados en `modifyQueryUsing()`

- `resources/css/app.css`
  - Estilos CSS para colores tenues personalizados

---

## Notas Técnicas

- Los colores son **tenues y sutiles** para evitar saturación visual
- Efecto hover ligeramente más oscuro para mejor UX
- Texto siempre legible con contraste adecuado
- Fallback a consultas directas si los JOINs no cargan datos
- Compatible con modo striped de Filament

---

## Fecha de Implementación
**12 de Febrero de 2026**

## Autor
Sistema implementado como parte de la mejora del flujo contable automatizado.
