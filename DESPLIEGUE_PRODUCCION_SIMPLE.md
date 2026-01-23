# 🚀 Despliegue en Producción - Corrección de Folios Duplicados

## ⚠️ IMPORTANTE - Leer antes de comenzar

**¡TODO ESTÁ AUTOMATIZADO!** Solo necesitas ejecutar `php artisan migrate`

1. **Hacer respaldo completo de la base de datos** antes de ejecutar
2. Tiempo estimado: **2-5 minutos**
3. Puede hacerse **sin detener el servicio**

---

## 📦 Pasos de Despliegue

### Paso 1: Hacer Respaldo (OBLIGATORIO)
```bash
# Opción A: Usando artisan (recomendado)
php artisan backup:run

# Opción B: Dump manual
mysqldump -u usuario -p nombre_bd > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Paso 2: Actualizar Código
```bash
cd /ruta/del/proyecto
git pull origin main
composer dump-autoload
```

### Paso 3: Ejecutar Migración (hace TODO automáticamente)
```bash
php artisan migrate
```

**Esto hará automáticamente:**
1. ✅ Detectar todos los folios duplicados
2. ✅ Corregir automáticamente cada duplicado
3. ✅ Actualizar contadores de series
4. ✅ Agregar índice único para prevenir futuros duplicados

**Salida esperada:**
```
INFO  Running migrations.

2026_01_23_100128_corregir_folios_duplicados_y_agregar_indice_unico
Encontrados X grupos de folios duplicados
✓ Corrección completada: Y registros corregidos
DONE
```

Si no hay duplicados, verá:
```
✓ No se encontraron folios duplicados
DONE
```

### Paso 4: Limpiar Caché
```bash
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Paso 5: Verificar (Opcional)
```bash
# Crear una factura de prueba y verificar que funcione correctamente
```

---

## ✅ ¿Cómo saber que funcionó?

La migración mostrará:
- Cuántos grupos de duplicados encontró
- Cuántos registros corrigió
- Y finalizará con "DONE"

Si todo está bien, el sistema:
- ✅ No tiene folios duplicados
- ✅ Tiene índice único activo
- ✅ No puede crear duplicados nuevos
- ✅ Funciona normalmente

---

## 🆘 Solución de Problemas

### La migración falla con error de duplicados

**Causa:** Hay duplicados que no se pudieron corregir automáticamente

**Solución:** Contactar al equipo de desarrollo. El script tiene lógica de corrección automática que debería manejar todos los casos.

### Error: "Class 'SeriesFacturas' not found"

**Solución:**
```bash
composer dump-autoload
php artisan optimize:clear
```

---

## 🔄 Rollback (Emergencia)

Si algo sale mal:

```bash
# 1. Restaurar respaldo
mysql -u usuario -p nombre_bd < backup_YYYYMMDD_HHMMSS.sql

# 2. Revertir migración
php artisan migrate:rollback --step=1

# 3. Limpiar caché
php artisan optimize:clear
```

---

## 📊 Qué incluye esta migración

**Archivo:**
`database/migrations/2026_01_23_100128_corregir_folios_duplicados_y_agregar_indice_unico.php`

**Funcionalidad:**
1. Detecta automáticamente folios duplicados
2. Mantiene el registro más antiguo (menor ID)
3. Renumera los duplicados con folios consecutivos
4. Actualiza contadores en `series_facturas`
5. Agrega índice único `(serie, folio, team_id)`

**Código modificado (ya incluido en el repositorio):**
- `app/Models/SeriesFacturas.php` - Método `obtenerSiguienteFolio()`
- `app/Filament/Clusters/tiadmin/Resources/FacturasResource.php`
- `app/Filament/Clusters/tiadmin/Resources/FacturasResource/Pages/ListFacturas.php`
- `app/Filament/Clusters/tiadmin/Resources/NotasdeCreditoResource.php`
- `app/Filament/Clusters/tiadmin/Resources/PedidosResource/Pages/ListPedidos.php`

---

## 💡 Ventajas de esta Solución

- ✅ **Automática:** Solo ejecutas `php artisan migrate`
- ✅ **Segura:** Mantiene registros originales, solo renumera duplicados
- ✅ **Verificable:** Muestra exactamente qué está haciendo
- ✅ **Sin downtime:** Puede ejecutarse con el sistema en producción
- ✅ **Prevención:** Índice único evita futuros duplicados

---

**Fecha:** 23 de Enero 2026
**Versión:** 2.0 - Simplificada
**Requiere:** PHP artisan (acceso web al sistema)
