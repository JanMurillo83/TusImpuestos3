# Prevención de Doble Envío de Formularios

## Descripción

Este sistema implementa una solución completa para evitar que los usuarios hagan doble clic en botones y dupliquen registros en la base de datos. La solución incluye protección tanto en **frontend** como en **backend**.

---

## Implementación

### 1. Frontend (JavaScript)

**Archivo:** `public/js/prevent-double-submit.js`

Este script realiza las siguientes acciones automáticamente:

#### Características:
- ✅ **Deshabilita botones** de envío al hacer clic
- ✅ **Cambia el texto** del botón a "Procesando..." con spinner
- ✅ **Previene múltiples envíos** del mismo formulario
- ✅ **Feedback visual** para el usuario
- ✅ **Timeout de seguridad** de 30 segundos (re-habilita automáticamente)
- ✅ **Compatible con AJAX** y formularios estándar

#### Uso automático:
El script se aplica **automáticamente** a:
- Todos los formularios HTML con `<form>`
- Botones tipo `submit`
- Botones con atributo `data-prevent-double`

#### Ejemplo de uso manual (opcional):
```html
<!-- Botón individual que no está en un formulario -->
<button data-prevent-double onclick="miFuncion()">
    Guardar
</button>
```

---

### 2. Backend (Laravel Middleware)

**Archivo:** `app/Http/Middleware/PreventDuplicateSubmissions.php`

Este middleware protege contra envíos duplicados verificando:
- IP del usuario
- Ruta de la petición
- Datos del formulario

#### Características:
- ✅ **Bloquea peticiones duplicadas** por 5-10 segundos
- ✅ **Usa caché** para detectar duplicados
- ✅ **Respuestas personalizadas** (JSON para API, redirección para web)
- ✅ **Se aplica globalmente** a todas las rutas web

#### Configuración:
El middleware está registrado en `bootstrap/app.php` y se aplica **automáticamente** a todas las rutas web.

Para **desactivar** la aplicación global, comenta estas líneas en `bootstrap/app.php`:
```php
// Comentar para desactivar globalmente
$middleware->web(append: [
    \App\Http\Middleware\PreventDuplicateSubmissions::class,
]);
```

Para aplicarlo **solo en rutas específicas**, usa el alias `prevent.duplicate`:
```php
Route::post('/guardar', [Controller::class, 'store'])
    ->middleware('prevent.duplicate');
```

---

## Archivos Modificados

### Nuevos archivos creados:
1. `public/js/prevent-double-submit.js` - Script de prevención frontend
2. `app/Http/Middleware/PreventDuplicateSubmissions.php` - Middleware backend
3. `PREVENCION_DOBLE_ENVIO.md` - Esta documentación

### Archivos modificados:
1. `app/Providers/AppServiceProvider.php` - Registro del script en Filament
2. `bootstrap/app.php` - Registro del middleware
3. `resources/views/MainPage.blade.php` - Inclusión del script

---

## Cómo Funciona

### Flujo de Prevención:

1. **Usuario hace clic en "Guardar"**

2. **Frontend (JavaScript):**
   - Deshabilita el botón inmediatamente
   - Cambia texto a "Procesando..."
   - Marca el formulario como "submitting"
   - Envía la petición al servidor

3. **Backend (Middleware):**
   - Calcula un hash único de la petición
   - Verifica si ya existe en caché
   - Si existe: rechaza con mensaje de error
   - Si no existe: marca en caché y procesa

4. **Después de procesar:**
   - Si fue exitoso: bloquea por 10 segundos más
   - Si falló: permite reintento inmediato

5. **Re-habilitación:**
   - Timeout automático después de 30 segundos (frontend)
   - Expiración de caché después de 10 segundos (backend)

---

## Configuración Adicional

### Ajustar tiempos de bloqueo:

**Frontend** (`public/js/prevent-double-submit.js`):
```javascript
// Cambiar el timeout de 30 segundos (línea 51)
setTimeout(function() {
    // ...
}, 30000); // 30000ms = 30 segundos
```

**Backend** (`app/Http/Middleware/PreventDuplicateSubmissions.php`):
```php
// Bloqueo inicial de 5 segundos (línea 52)
Cache::put($cacheKey, true, 5);

// Bloqueo extendido de 10 segundos (línea 60)
Cache::put($cacheKey, true, 10);
```

### Personalizar mensajes:

**JavaScript** (línea 31):
```javascript
$btn.html('<i class="fa fa-spinner fa-spin"></i> Procesando...');
```

**Middleware** (líneas 42-48):
```php
return response()->json([
    'message' => 'Tu mensaje personalizado aquí',
    'error' => 'duplicate_submission'
], 429);
```

---

## Verificación

Para verificar que la solución está funcionando:

1. **Abrir las herramientas de desarrollador** del navegador (F12)
2. **Ir a la pestaña Console**
3. **Llenar un formulario** y hacer clic en "Guardar"
4. **Verificar que:**
   - El botón se deshabilita inmediatamente
   - El texto cambia a "Procesando..."
   - El botón tiene la clase `btn-processing`

5. **Intentar hacer doble clic:**
   - El segundo clic debe ser ignorado
   - Si se envía manualmente otra petición, el backend la rechazará

---

## Notas Importantes

- ⚠️ **El middleware usa caché**: Asegúrate de tener un driver de caché configurado (file, redis, memcached)
- ⚠️ **Compatible con Filament**: El script está registrado globalmente en todas las páginas de Filament
- ⚠️ **Iconos Font Awesome**: Si no usas Font Awesome, reemplaza `<i class="fa fa-spinner fa-spin"></i>` con tu propio spinner
- ⚠️ **CSRF Token**: El middleware excluye automáticamente el token CSRF de la verificación de duplicados

---

## Soporte

Si tienes problemas:

1. Verifica que jQuery esté cargado antes del script
2. Revisa la consola del navegador por errores
3. Verifica los logs de Laravel en `storage/logs/laravel.log`
4. Asegúrate de que el driver de caché esté configurado en `.env`

---

## Mejoras Futuras (Opcional)

- [ ] Agregar sonido de notificación al bloquear
- [ ] Mostrar notificación toast en lugar de alert
- [ ] Implementar cola de peticiones
- [ ] Dashboard de monitoreo de peticiones duplicadas
- [ ] Reportes de intentos de doble envío por usuario
