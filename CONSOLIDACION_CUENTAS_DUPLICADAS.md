# Consolidación de Cuentas Duplicadas

## Problema

Se detectaron cuentas contables duplicadas en la tabla `cat_cuentas` - varias cuentas con el mismo `codigo` dentro del mismo `team_id`. Esto causa inconsistencias porque los auxiliares pueden estar apuntando a cualquiera de las cuentas duplicadas.

## Solución

Se ha creado un comando Artisan que:

1. ✅ Identifica todas las cuentas duplicadas (mismo código + team_id)
2. ✅ Mantiene la cuenta más antigua (ID menor)
3. ✅ Verifica que todos los auxiliares apunten al código correcto
4. ✅ Elimina las cuentas duplicadas
5. ✅ Previene futuras duplicaciones con un constraint único

---

## Paso 1: Identificar Duplicados (Modo Dry-Run)

**Primero ejecute el comando en modo dry-run para ver qué se haría SIN hacer cambios:**

```bash
php artisan cuentas:consolidar-duplicadas --dry-run
```

Esto mostrará:
- Cuántos grupos de duplicados existen
- Los team_id afectados
- Los códigos de cuenta duplicados
- Los IDs que se eliminarían
- Cuántos auxiliares están relacionados

### Opciones disponibles:

```bash
# Ver solo duplicados de un team específico
php artisan cuentas:consolidar-duplicadas --dry-run --team-id=69

# Ver duplicados de todos los teams
php artisan cuentas:consolidar-duplicadas --dry-run
```

---

## Paso 2: Ejecutar Consolidación

**Una vez revisado el reporte, ejecute el comando para aplicar los cambios:**

```bash
php artisan cuentas:consolidar-duplicadas
```

El comando pedirá confirmación antes de proceder.

### Ejecutar para un team específico:

```bash
php artisan cuentas:consolidar-duplicadas --team-id=69
```

---

## Paso 3: Aplicar Constraint Único (Prevención)

**Después de consolidar los duplicados, ejecute la migración para prevenir futuras duplicaciones:**

```bash
php artisan migrate
```

Esto agregará un índice único compuesto en `cat_cuentas` para las columnas `codigo` y `team_id`, haciendo imposible crear duplicados en el futuro.

---

## Ejemplo de Salida

```
=== CONSOLIDACIÓN DE CUENTAS DUPLICADAS ===

⚠️  MODO DRY-RUN: No se realizarán cambios en la base de datos

Paso 1: Identificando cuentas duplicadas...
❌ Se encontraron 15 grupos de cuentas duplicadas

+---------+-----------+------------------+-----------------+-------+
| Team ID | Código    | Nombre           | IDs Duplicados  | Total |
+---------+-----------+------------------+-----------------+-------+
| 69      | 10100000  | Caja             | 123,456         | 2     |
| 69      | 11000000  | Bancos           | 234,567,890     | 3     |
| 72      | 40100000  | Ventas           | 345,678         | 2     |
+---------+-----------+------------------+-----------------+-------+

Paso 2: Procesando duplicados...

Procesando: Team 69 - Código 10100000 (Caja)
  → Manteniendo ID: 123
  → Eliminando IDs: 456
  → Auxiliares encontrados: 45
  ⊘ [DRY-RUN] Se eliminarían 1 cuentas duplicadas

Procesando: Team 69 - Código 11000000 (Bancos)
  → Manteniendo ID: 234
  → Eliminando IDs: 567, 890
  → Auxiliares encontrados: 128
  ⊘ [DRY-RUN] Se eliminarían 2 cuentas duplicadas

=== RESUMEN ===
Grupos de duplicados procesados: 15

Ejecute sin --dry-run para aplicar los cambios
```

---

## Notas Importantes

### ¿Qué cuenta se mantiene?

- Se mantiene la cuenta con **ID más bajo** (la más antigua)
- Las demás se eliminan

### ¿Qué pasa con los auxiliares?

- Los auxiliares usan el campo `codigo` (no `cat_cuentas_id`)
- Por lo tanto, **no necesitan actualización**
- El comando solo verifica que todos los auxiliares apunten al código correcto

### ¿Es seguro?

- ✅ Sí, el comando usa **transacciones de base de datos**
- ✅ Si algo falla, se hace rollback automático
- ✅ Puede ejecutar en modo `--dry-run` cuantas veces quiera
- ✅ Siempre pide confirmación antes de ejecutar

### ¿Qué hace el constraint único?

Una vez aplicado, intentar crear una cuenta duplicada resultará en un error:

```
SQLSTATE[23000]: Integrity constraint violation:
1062 Duplicate entry '10100000-69' for key 'unique_codigo_team'
```

Esto es **bueno** porque previene el problema en el futuro.

---

## Rollback (Si algo sale mal)

Si necesita revertir el constraint único:

```bash
php artisan migrate:rollback
```

**Nota:** Esto NO revierte la consolidación de cuentas (esos cambios son permanentes). Solo remueve el constraint único.

---

## Recomendaciones

1. **Hacer backup de la base de datos** antes de ejecutar la consolidación
2. Ejecutar primero en **ambiente de pruebas**
3. Usar `--dry-run` para revisar los cambios
4. Ejecutar team por team si prefiere un enfoque gradual:
   ```bash
   php artisan cuentas:consolidar-duplicadas --team-id=69
   php artisan cuentas:consolidar-duplicadas --team-id=72
   # etc...
   ```
5. Aplicar la migración del constraint único después de consolidar

---

## Soporte

Si encuentra algún problema:

1. Revise los logs en `storage/logs/laravel.log`
2. Ejecute el comando con `-v` para verbose:
   ```bash
   php artisan cuentas:consolidar-duplicadas -v
   ```
3. Verifique manualmente las cuentas duplicadas:
   ```sql
   SELECT team_id, codigo, COUNT(*) as count, GROUP_CONCAT(id) as ids
   FROM cat_cuentas
   GROUP BY team_id, codigo
   HAVING count > 1;
   ```
