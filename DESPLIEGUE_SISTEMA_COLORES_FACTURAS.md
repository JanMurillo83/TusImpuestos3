# 🚀 Despliegue en Producción - Sistema de Colores y Mejoras en Facturas

## 📋 Resumen de Cambios

Esta actualización incluye dos mejoras importantes:

1. **✅ Grabado automático en Almacén CFDI al timbrar** - Elimina paso manual de descarga
2. **🎨 Sistema de identificación visual por colores** - Colores tenues para identificar estado de facturas

---

## ⚠️ IMPORTANTE - Leer antes de comenzar

### Requisitos previos:
- ✅ Acceso SSH al servidor
- ✅ Permisos para ejecutar comandos artisan
- ✅ Acceso a Git (o método de transferencia de archivos)
- ✅ Backup completo de la base de datos

### Información técnica:
- ⏱️ **Tiempo estimado:** 5-10 minutos
- 🔄 **Requiere downtime:** NO (solo durante 30 segundos al compilar CSS)
- 🗄️ **Cambios en BD:** NO (solo cambios de código)
- 📦 **Archivos modificados:** 3 archivos principales

---

## 📦 Pasos de Despliegue

### **Paso 1: Hacer Respaldo (OBLIGATORIO)**

Aunque no hay cambios en base de datos, es buena práctica:

```bash
# Opción A: Backup de código actual
cd /ruta/del/proyecto
tar -czf backup_codigo_$(date +%Y%m%d_%H%M%S).tar.gz app/ resources/

# Opción B: Backup completo
php artisan backup:run

# Opción C: Solo BD (si prefieres)
mysqldump -u usuario -p nombre_bd > backup_bd_$(date +%Y%m%d_%H%M%S).sql
```

---

### **Paso 2: Actualizar Código en Servidor**

Elige el método que uses habitualmente:

#### **Opción A: Con Git (Recomendado)**

```bash
cd /ruta/del/proyecto

# Verificar rama actual
git branch

# Hacer pull de los cambios
git pull origin main

# O si usas otra rama
git pull origin nombre-de-tu-rama
```

#### **Opción B: Sin Git (Transferencia manual)**

Transfiere estos 3 archivos al servidor:

1. `app/Http/Controllers/TimbradoController.php`
2. `app/Filament/Clusters/tiadmin/Resources/FacturasResource.php`
3. `resources/css/app.css`

Y opcionalmente:
4. `app/Filament/Clusters/Herramientas/Pages/DescargasSAT.php`

```bash
# Ejemplo con SCP
scp app/Http/Controllers/TimbradoController.php usuario@servidor:/ruta/proyecto/app/Http/Controllers/
scp app/Filament/Clusters/tiadmin/Resources/FacturasResource.php usuario@servidor:/ruta/proyecto/app/Filament/Clusters/tiadmin/Resources/
scp resources/css/app.css usuario@servidor:/ruta/proyecto/resources/css/
```

---

### **Paso 3: Instalar Dependencias (si usaste Git)**

```bash
cd /ruta/del/proyecto

# Actualizar autoload de Composer
composer dump-autoload

# Verificar que no falten dependencias
composer install --no-dev --optimize-autoloader
```

---

### **Paso 4: Compilar Assets CSS (IMPORTANTE)**

```bash
cd /ruta/del/proyecto

# Compilar CSS con Vite
npm run build
```

**Salida esperada:**
```
> build
> vite build

vite v5.4.11 building for production...
✓ 54 modules transformed.
✓ built in 2-3s
```

Si no tienes `npm` instalado o falla, puedes omitir este paso pero los colores no se verán correctamente.

---

### **Paso 5: Limpiar Caché de Laravel**

```bash
cd /ruta/del/proyecto

# Limpiar todo el caché
php artisan optimize:clear

# O limpiar individualmente
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

---

### **Paso 6: Verificar que todo funcione**

1. **Accede al módulo de facturas:**
   ```
   https://tu-dominio.com/tu-tenant/tiadmin/facturas
   ```

2. **Verifica que veas:**
   - ✅ Colores tenues en las filas según estado
   - ✅ Columna "Póliza" con badges
   - ✅ Columna "Estado Cobro" con badges
   - ✅ Columna "Comp. Pago" con texto (Aplicado/Pendiente/PUE)

3. **Prueba timbrar una factura:**
   - Crea una factura de prueba
   - Timbra la factura
   - Verifica que aparezca inmediatamente en `/emitcfdi/cfdiei` sin necesidad de importar XML

---

## ✅ Verificación del Despliegue

### **Checklist de Verificación:**

- [ ] Los colores se ven en la tabla de facturas
- [ ] La columna "Póliza" muestra el folio correctamente
- [ ] La columna "Estado Cobro" muestra el estado correcto
- [ ] La columna "Comp. Pago" muestra texto en lugar de íconos
- [ ] Al timbrar una factura, se graba automáticamente en almacencfdis
- [ ] La factura aparece inmediatamente en `/emitcfdi/cfdiei`

### **Colores que debes ver:**

| Color | Aspecto | Significado |
|-------|---------|-------------|
| 🟢 Verde tenue | Fondo muy claro | Factura con póliza y cobro |
| 🟡 Amarillo tenue | Fondo muy claro | Factura con póliza sin cobro |
| 🔵 Azul tenue | Fondo muy claro | Factura timbrada sin póliza |
| 🔴 Rojo tenue | Fondo muy claro | PPD sin complemento |
| ⚪ Blanco | Normal | Factura no timbrada |

---

## 🆘 Solución de Problemas

### **Problema 1: Los colores no se ven**

**Causa:** CSS no compilado o caché del navegador

**Solución:**
```bash
# Recompilar CSS
npm run build

# Limpiar caché de Laravel
php artisan view:clear
php artisan optimize:clear

# Limpiar caché del navegador (Ctrl + Shift + R)
```

---

### **Problema 2: Error "ONLY_FULL_GROUP_BY"**

**Causa:** Configuración estricta de MySQL

**Solución:** Este error ya está corregido en el código actual. Si persiste:

```bash
# Verificar que tienes la última versión
git log -1 --oneline

# Debe mostrar commits recientes sobre GROUP BY
```

---

### **Problema 3: Las facturas timbradas no aparecen en almacencfdis**

**Causa:** Código no actualizado correctamente

**Solución:**
```bash
# Verificar que el archivo TimbradoController.php esté actualizado
grep -n "grabar_almacen_cfdi" app/Http/Controllers/TimbradoController.php

# Debe mostrar línea con la función
```

---

### **Problema 4: Error 500 al cargar facturas**

**Causa:** Caché corrupto o autoload desactualizado

**Solución:**
```bash
composer dump-autoload
php artisan optimize:clear
php artisan config:clear

# Revisar logs
tail -f storage/logs/laravel.log
```

---

## 🔄 Rollback (En caso de emergencia)

### **Rollback Simple (Solo código):**

```bash
cd /ruta/del/proyecto

# Opción A: Con Git
git reset --hard HEAD~1
composer dump-autoload
npm run build
php artisan optimize:clear

# Opción B: Restaurar backup de código
tar -xzf backup_codigo_YYYYMMDD_HHMMSS.tar.gz
composer dump-autoload
npm run build
php artisan optimize:clear
```

### **Rollback de archivos individuales:**

```bash
# Restaurar TimbradoController
git checkout HEAD~1 -- app/Http/Controllers/TimbradoController.php

# Restaurar FacturasResource
git checkout HEAD~1 -- app/Filament/Clusters/tiadmin/Resources/FacturasResource.php

# Restaurar CSS
git checkout HEAD~1 -- resources/css/app.css

# Compilar y limpiar
npm run build
php artisan optimize:clear
```

---

## 📊 Archivos Modificados en este Despliegue

### **Archivos principales:**

1. **app/Http/Controllers/TimbradoController.php**
   - ➕ Método `grabar_almacen_cfdi()` agregado
   - ➕ Imports agregados: `Almacencfdis`, `Log`

2. **app/Filament/Clusters/tiadmin/Resources/FacturasResource.php**
   - ➕ Método `getRecordColorClass()` agregado
   - ➕ Métodos helper: `tienePoliza()`, `estaCobrada()`, `tieneComplemento()`
   - ➕ Columnas agregadas: `poliza`, `estado_cobro`
   - 🔄 Columna modificada: `complemento_pago` (antes `tiene_complemento`)
   - ➕ JOINs optimizados en `modifyQueryUsing()`
   - ✏️ 3 ubicaciones donde se llama a `actualiza_fac_tim()` reemplazadas por `grabar_almacen_cfdi()`

3. **resources/css/app.css**
   - ➕ Estilos CSS para colores tenues

4. **app/Filament/Clusters/Herramientas/Pages/DescargasSAT.php** (Opcional)
   - ➕ Comentario agregado en validación de duplicados

### **Archivos de documentación creados:**

- `SISTEMA_COLORES_FACTURAS.md` - Explicación del sistema
- `DESPLIEGUE_SISTEMA_COLORES_FACTURAS.md` - Este archivo

---

## 💡 Ventajas de estos Cambios

### **Grabado automático en almacencfdis:**
- ✅ Elimina paso manual de importar XML
- ✅ Facturas disponibles inmediatamente para contabilizar
- ✅ Reduce errores humanos
- ✅ Flujo más rápido y eficiente

### **Sistema de colores:**
- ✅ Identificación visual rápida del estado
- ✅ Colores tenues que no saturan la vista
- ✅ Nuevas columnas informativas
- ✅ Mejor experiencia de usuario

---

## 📝 Notas Adicionales

### **Compatibilidad:**
- ✅ Compatible con versión actual de Laravel
- ✅ No requiere cambios en base de datos
- ✅ Retrocompatible con facturas existentes
- ✅ Descarga del SAT sigue funcionando (no interfiere)

### **Rendimiento:**
- ✅ JOINs optimizados reducen queries N+1
- ✅ Datos precargados en una sola consulta
- ✅ Fallback a queries directas si es necesario

### **Seguridad:**
- ✅ Validación de UUID antes de insertar
- ✅ Verificación de duplicados
- ✅ Logging de errores completo
- ✅ Transacciones DB implícitas

---

## 🎯 Resumen Ejecutivo

**Qué hace este despliegue:**
1. Al timbrar una factura, se guarda automáticamente en almacencfdis
2. La tabla de facturas muestra colores tenues según el estado de procesamiento
3. Nuevas columnas muestran si tiene póliza, estado de cobro y complemento de pago

**Qué NO hace:**
- No modifica estructura de base de datos
- No afecta funcionalidad existente
- No requiere detener el servicio

**Tiempo total estimado:**
- Con Git: 5-7 minutos
- Sin Git: 10-15 minutos

---

**Fecha de creación:** 12 de Febrero de 2026
**Versión:** 1.0
**Autor:** Sistema TusImpuestos3
**Requiere:** PHP 8.1+, Laravel 10+, Node.js (para compilar CSS)
