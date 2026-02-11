# Consolidación de Deudores/Acreedores Duplicados

**Fecha:** 09 de Febrero de 2026
**Estado:** LISTO PARA EJECUTAR

---

## 📊 ANÁLISIS ENFOCADO

### Duplicados Encontrados: **343 grupos**

Después de filtrar **SOLO** las cuentas de:
- **10501\*** = Deudores Diversos (Clientes)
- **20101\*** = Acreedores Diversos (Proveedores)

Se encontraron **343 grupos de duplicados** - estos SÍ son probablemente **ERRORES REALES**.

---

## 🎯 ¿Por qué estos duplicados SÍ son un problema?

A diferencia de los duplicados generales por nombre (3,618), estos duplicados están **limitados a cuentas de clientes/proveedores** donde:

1. **Mismo nombre** = Misma empresa/persona
2. **Diferentes códigos** = Registros duplicados
3. **Problema contable** = Dispersión de saldos y auxiliares

### Ejemplos Claros de Errores:

| Nombre | Códigos | Problema |
|--------|---------|----------|
| ITALCAFE | 20101474, 20101475 | Mismo proveedor registrado 2 veces |
| ELECTRIC COIL DE MEXICO | 20101022, 10501098 | Registrado como acreedor Y deudor |
| FG INDUSTRIAL SUPPORT | 10501072, 20101009 | Registrado como deudor Y acreedor |
| OFFICE JOBS | Múltiples en varios teams | Proveedor duplicado en varios teams |

---

## 📈 DISTRIBUCIÓN POR TEAM

Los teams más afectados:

| Team | Duplicados | Ejemplos |
|------|------------|----------|
| 3 | 26 | ITALCAFE, ELECTRIC COIL, FG INDUSTRIAL |
| 11 | 48 | ALARMAS PROTEKTOR, FRANZ HAIMER, KARCHER |
| 21 | 27 | AMERICAN EAGLE, FARMACIA GUADALAJARA, OFFICE DEPOT |
| 65 | 52 | FARMACIAS BENAVIDES, HOTELES JURICA, OFFICE JOBS |
| 70 | 81 | BANCO NACIONAL, ESTAFETA, varios más |

---

## 🔍 TIPOS DE DUPLICADOS IDENTIFICADOS

### Tipo 1: Mismo Tercero, Diferentes Códigos (Mayoría)
```
Team 11:
- 20101382: KARCHER MEXICO
- 20101383: KARCHER MEXICO
→ Mismo proveedor, códigos consecutivos
```

### Tipo 2: Deudor Y Acreedor (Más Complejo)
```
Team 3:
- 10501072: FG INDUSTRIAL SUPPORT (Deudor)
- 20101009: FG INDUSTRIAL SUPPORT (Acreedor)
→ Mismo tercero registrado en ambos catálogos
```
**Nota:** Estos podrían ser válidos SI el tercero es cliente Y proveedor.

### Tipo 3: Códigos Muy Diferentes
```
Team 13:
- 20101016: BANCO BBVA
- 20101034: BANCO BBVA
→ Códigos no consecutivos, probablemente error de captura
```

---

## ⚠️ CONSIDERACIONES ANTES DE CONSOLIDAR

### ¿Cuándo SÍ consolidar?

✅ **Consolidar si:**
- Mismo nombre
- Misma naturaleza (ambos deudores O ambos acreedores)
- Códigos en mismo rango (10501\* o 20101\*)
- Sin justificación de negocio

### ¿Cuándo NO consolidar?

❌ **NO consolidar si:**
- Uno es deudor (10501\*) y otro acreedor (20101\*)
  - Podría ser cliente Y proveedor legítimo
- Nombres similares pero NO idénticos
- Hay auxiliares en ambas cuentas que indican uso activo diferenciado

---

## 🛠️ COMANDO ACTUALIZADO

El comando ahora está **optimizado** para deudores/acreedores:

```bash
# Ver SOLO deudores/acreedores duplicados
php artisan cuentas:consolidar-duplicadas-nombre --dry-run

# Ver un team específico
php artisan cuentas:consolidar-duplicadas-nombre --dry-run --team-id=3

# Consolidar (después de revisar)
php artisan cuentas:consolidar-duplicadas-nombre --team-id=3
```

---

## 📋 PROCESO RECOMENDADO

### Opción 1: Revisión Manual por Team (Conservadora)

1. **Revisar team por team:**
   ```bash
   php artisan cuentas:consolidar-duplicadas-nombre --dry-run --team-id=3
   ```

2. **Analizar cada caso:**
   - ¿Es el mismo tercero?
   - ¿Hay auxiliares en ambas cuentas?
   - ¿Uno es deudor y otro acreedor?

3. **Consolidar selectivamente:**
   ```bash
   php artisan cuentas:consolidar-duplicadas-nombre --team-id=3
   ```

### Opción 2: Consolidación Masiva (Más Rápida)

**⚠️ ADVERTENCIA:** Esto consolidará 343 grupos. Asegúrese de tener backup.

```bash
# Revisar TODO
php artisan cuentas:consolidar-duplicadas-nombre --dry-run

# Consolidar TODO (requiere confirmación)
php artisan cuentas:consolidar-duplicadas-nombre
```

---

## 🔄 QUÉ HACE LA CONSOLIDACIÓN

Para cada grupo de duplicados:

1. **Mantiene** la cuenta con ID más bajo (más antigua)
2. **Actualiza auxiliares** para que apunten al código mantenido
3. **Elimina** registros en `cat_cuentas_team`
4. **Elimina** cuentas duplicadas

### Ejemplo de Consolidación:

```
ANTES:
Team 11:
- ID 20101382, Código 20101382: KARCHER MEXICO (10 auxiliares)
- ID 20101383, Código 20101383: KARCHER MEXICO (5 auxiliares)

DESPUÉS:
Team 11:
- ID 20101382, Código 20101382: KARCHER MEXICO (15 auxiliares)
✗ ID 20101383 eliminado
✓ Los 5 auxiliares actualizados a código 20101382
```

---

## 📊 IMPACTO ESTIMADO

Si se consolidan todos los 343 grupos:

- **343 cuentas** serán eliminadas
- **Miles de auxiliares** serán actualizados (varía por team)
- **Saldos** se consolidarán en una sola cuenta por tercero
- **Reportes** mostrarán información más limpia

---

## ✅ BENEFICIOS DE LA CONSOLIDACIÓN

1. **Saldos unificados** por cliente/proveedor
2. **Reportes más limpios** (sin duplicados en listados)
3. **Menos confusión** al buscar terceros
4. **Base de datos optimizada**
5. **Prevención de errores futuros** en captura

---

## 🔒 PROTECCIÓN FUTURA

**Migración disponible (opcional):**
```php
database/migrations/2026_02_09_144534_add_optional_unique_constraint_nombre_cat_cuentas.php
```

**Está comentada por defecto** porque:
- Solo aplica a deudores/acreedores (10501\*, 20101\*)
- Hay que consolidar primero los duplicados existentes
- Solo descomentar si se desea impedir nombres duplicados

Para activarla:
1. Descomentar el código en la migración
2. Ejecutar: `php artisan migrate`

---

## 🎯 RECOMENDACIÓN FINAL

### Para Deudores/Acreedores (10501\*, 20101\*):
✅ **SÍ CONSOLIDAR** - Son errores reales
🔍 **Revisar casos especiales** - Deudor Y Acreedor
⚠️ **Hacer backup** antes de ejecutar
📝 **Ejecutar en horario de bajo uso**

### Estrategia Sugerida:

1. **Backup de BD** ✅
2. **Probar en team pequeño primero:**
   ```bash
   php artisan cuentas:consolidar-duplicadas-nombre --team-id=5
   ```
3. **Verificar resultados** ✅
4. **Consolidar resto de teams:**
   ```bash
   php artisan cuentas:consolidar-duplicadas-nombre
   ```
5. **Verificar que no queden duplicados:**
   ```bash
   php artisan cuentas:consolidar-duplicadas-nombre --dry-run
   ```

---

## 🆚 COMPARACIÓN FINAL

| Aspecto | Códigos | Nombres (General) | Deudores/Acreedores |
|---------|---------|-------------------|---------------------|
| **Cantidad** | 11 | 3,618 | 343 |
| **¿Es error?** | ✅ SÍ | ⚠️ Mayoría NO | ✅ SÍ |
| **Acción** | ✅ Consolidado | ❌ No consolidar | ✅ Consolidar |
| **Constraint** | ✅ Aplicado | ❌ No aplicar | ⚠️ Opcional |

---

## 📞 SOPORTE

### Verificar manualmente un duplicado específico:

```sql
-- Ver auxiliares de un tercero duplicado
SELECT codigo, COUNT(*) as total, SUM(cargo) as cargos, SUM(abono) as abonos
FROM auxiliares
WHERE team_id = X AND codigo IN ('codigo1', 'codigo2')
GROUP BY codigo;
```

### Si algo sale mal:

1. Revisar logs: `storage/logs/laravel.log`
2. Restaurar backup si es necesario
3. El comando usa transacciones - si falla, hace rollback automático

---

**Comando listo para ejecutar cuando esté preparado**
**343 duplicados esperando consolidación**
