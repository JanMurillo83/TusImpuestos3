# 🚀 Guía de Despliegue - Corrección de Folios Duplicados

## 📋 Resumen
Esta guía detalla los pasos para corregir el problema de folios duplicados en el servidor de producción.

---

## ⚠️ IMPORTANTE - Leer antes de comenzar

1. **Hacer respaldo completo de la base de datos** antes de ejecutar cualquier comando
2. Los pasos deben ejecutarse en el orden indicado
3. Estimar **5-10 minutos** de tiempo de ejecución total
4. Puede hacerse sin detener el servicio, pero es recomendable hacerlo en horario de baja actividad

---

## 📦 Archivos Modificados

Los siguientes archivos contienen las correcciones:

### Nuevos:
- `database/migrations/2026_01_23_094039_add_unique_folio_constraint_to_facturas.php`
- `app/Console/Commands/CorregirFoliosDuplicados.php`

### Modificados:
- `app/Models/SeriesFacturas.php`
- `app/Filament/Clusters/tiadmin/Resources/FacturasResource.php`
- `app/Filament/Clusters/tiadmin/Resources/FacturasResource/Pages/ListFacturas.php`
- `app/Filament/Clusters/tiadmin/Resources/NotasdeCreditoResource.php`
- `app/Filament/Clusters/tiadmin/Resources/PedidosResource/Pages/ListPedidos.php`

---

## 🔧 Pasos de Despliegue en Producción

### Paso 1: Hacer Respaldo
```bash
# En el servidor de producción
php artisan backup:run
# O hacer dump manual de la base de datos
mysqldump -u usuario -p nombre_base_datos > backup_antes_folios_$(date +%Y%m%d_%H%M%S).sql
```

### Paso 2: Actualizar Código
```bash
cd /ruta/del/proyecto
git pull origin main
```

### Paso 3: Verificar Duplicados Existentes
```bash
# Revisar cuántos duplicados existen (modo seguro - solo consulta)
php artisan app:corregir-folios-duplicados --dry-run
```

Este comando mostrará:
- Cuántos grupos de folios duplicados existen
- Qué cambios se realizarán
- **NO modifica la base de datos**

### Paso 4: Corregir Duplicados
```bash
# Ejecutar la corrección (MODIFICA la base de datos)
php artisan app:corregir-folios-duplicados
```

**Resultado esperado:**
```
Encontrados X grupos de folios duplicados
---
Serie: A, Folio: 123, Team: 1
Manteniendo ID: 456
Corrigiendo IDs: 457
  - ID 457: Cambiar folio 123 -> 124
---
✓ Corrección completada: X registros corregidos
```

### Paso 5: Verificar que No Quedan Duplicados
```bash
php artisan tinker --execute="
\$duplicados = DB::table('facturas')
    ->select('serie', 'folio', 'team_id', DB::raw('COUNT(*) as count'))
    ->groupBy('serie', 'folio', 'team_id')
    ->having('count', '>', 1)
    ->count();
echo 'Duplicados encontrados: ' . \$duplicados . PHP_EOL;
"
```

**Resultado esperado:** `Duplicados encontrados: 0`

### Paso 6: Ejecutar Migración
```bash
# Agregar índice único para prevenir futuros duplicados
php artisan migrate
```

**Resultado esperado:**
```
INFO  Running migrations.
2026_01_23_094039_add_unique_folio_constraint_to_facturas ..... DONE
```

### Paso 7: Verificar Índice Único
```bash
php artisan tinker --execute="
try {
    DB::table('facturas')->insert([
        'serie' => 'TEST_' . time(),
        'folio' => 1,
        'docto' => 'TEST1',
        'fecha' => now(),
        'clie' => 1,
        'esquema' => 1,
        'estado' => 'Activa',
        'team_id' => 999999,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    DB::table('facturas')->insert([
        'serie' => 'TEST_' . time(),
        'folio' => 1,
        'docto' => 'TEST1',
        'fecha' => now(),
        'clie' => 1,
        'esquema' => 1,
        'estado' => 'Activa',
        'team_id' => 999999,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    echo '❌ ERROR: Se permitió crear duplicado' . PHP_EOL;
} catch (\Exception \$e) {
    echo '✓ ÉXITO: Índice único funcionando correctamente' . PHP_EOL;
}
DB::table('facturas')->where('team_id', 999999)->delete();
"
```

**Resultado esperado:** `✓ ÉXITO: Índice único funcionando correctamente`

### Paso 8: Limpiar Caché
```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## ✅ Verificación Final

### Prueba de Creación de Factura
1. Acceder al sistema
2. Crear una factura nueva
3. Verificar que el folio se asigna correctamente
4. Verificar que se puede timbrar sin problemas

### Verificar Logs
```bash
tail -f storage/logs/laravel.log
```
No deben aparecer errores relacionados con folios o constraints.

---

## 🆘 Solución de Problemas

### Error: "Duplicate entry" al ejecutar migrate

**Causa:** Aún existen duplicados en la base de datos

**Solución:**
```bash
# Repetir pasos 3 y 4
php artisan app:corregir-folios-duplicados --dry-run
php artisan app:corregir-folios-duplicados
```

### Error: "Class 'SeriesFacturas' not found"

**Causa:** Caché de autoload desactualizado

**Solución:**
```bash
composer dump-autoload
php artisan optimize:clear
```

### Folios con "huecos" después de la corrección

**Causa:** Normal - los duplicados se renumeraron

**Solución:** No requiere acción. Los huecos en la numeración son aceptables y no afectan el funcionamiento.

---

## 📊 Resultados Esperados

Después del despliegue:
- ✅ Cero folios duplicados en la base de datos
- ✅ Índice único activo previene futuros duplicados
- ✅ Método centralizado `obtenerSiguienteFolio()` en uso
- ✅ Transacciones con locks previenen condiciones de carrera
- ✅ Sistema funcionando normalmente

---

## 🔄 Rollback (En caso de emergencia)

Si algo sale mal y necesitas revertir:

```bash
# 1. Restaurar respaldo
mysql -u usuario -p nombre_base_datos < backup_antes_folios_YYYYMMDD_HHMMSS.sql

# 2. Revertir migración
php artisan migrate:rollback --step=1

# 3. Revertir código
git reset --hard HEAD~1
composer dump-autoload
php artisan optimize:clear
```

---

## 📞 Contacto

Si tienes dudas durante el despliegue, contactar al equipo de desarrollo.

**Fecha de creación:** 23 de Enero 2026
**Versión:** 1.0
