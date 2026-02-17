# Instrucciones para Configurar Cron de Descargas SAT en Plesk

## 📋 Resumen
Este documento explica cómo configurar la tarea programada (cron job) en Plesk para ejecutar automáticamente las descargas diarias de CFDIs del SAT.

---

## 🔧 Configuración en Plesk

### **Paso 1: Acceder a Tareas Programadas**

1. Inicia sesión en **Plesk**
2. Selecciona el dominio/sitio web correspondiente
3. Ve a la sección **"Tareas programadas" (Scheduled Tasks)** o **"Cron Jobs"**

### **Paso 2: Crear Nueva Tarea Programada**

Haz clic en **"Agregar Tarea" / "Add Task"**

### **Paso 3: Configurar la Tarea**

#### **A) Comando a ejecutar:**

```bash
/usr/bin/php /ruta/completa/al/proyecto/artisan schedule:run >> /dev/null 2>&1
```

**⚠️ IMPORTANTE:** Reemplaza `/ruta/completa/al/proyecto/` con la ruta real de tu proyecto en el servidor.

**Ejemplo:**
```bash
/usr/bin/php /var/www/vhosts/tusimpuestos.com/httpdocs/artisan schedule:run >> /dev/null 2>&1
```

#### **B) Frecuencia de Ejecución:**

Configura la tarea para que se ejecute **cada minuto**:

**Opción 1: Configuración Personalizada (Custom)**
- **Minuto:** `*`
- **Hora:** `*`
- **Día:** `*`
- **Mes:** `*`
- **Día de la semana:** `*`

**Opción 2: Si Plesk lo permite, selecciona:**
- "Cada minuto" / "Every minute"

#### **C) Configuración Adicional:**

- **Descripción:** `Laravel Scheduler - Descargas SAT`
- **Estado:** Activo ✅
- **Notificaciones por email:** Opcional (recomendado desactivar para evitar spam)

---

## 🕐 Horario de Ejecución

El comando configurado (`schedule:run`) ejecuta todas las tareas programadas en Laravel.

En tu aplicación, la descarga automática está configurada para ejecutarse:

- **Hora:** 07:00 AM (Hora de Ciudad de México)
- **Frecuencia:** Diaria
- **Zona Horaria:** America/Mexico_City

---

## ✅ Verificación de la Configuración

### **1. Verificar que el cron esté activo**

Después de guardar la tarea en Plesk, verifica que aparezca en la lista de tareas programadas con estado **"Activo"**.

### **2. Verificar rutas del servidor**

Para asegurarte de que las rutas son correctas, puedes conectarte por SSH al servidor y ejecutar:

```bash
# Verificar ubicación de PHP
which php
# Debería retornar algo como: /usr/bin/php o /opt/plesk/php/8.2/bin/php

# Verificar ruta del proyecto
cd /var/www/vhosts/tu-dominio.com/httpdocs
ls -la artisan
```

### **3. Probar el comando manualmente**

Antes de dejar el cron funcionando automáticamente, prueba ejecutarlo manualmente:

```bash
cd /ruta/completa/al/proyecto
php artisan schedule:run
```

Deberías ver algo como:
```
No scheduled commands are ready to run.
```
(Esto es normal si no es la hora de ejecución)

Para ver todas las tareas programadas:
```bash
php artisan schedule:list
```

### **4. Ejecutar la descarga manualmente (prueba)**

```bash
php artisan sat:descargar-automatico
```

Esto ejecutará el proceso inmediatamente y te mostrará los resultados en pantalla.

---

## 📊 Monitoreo de Resultados

### **Consultar Historial desde la Aplicación**

1. Inicia sesión en el sistema
2. Ve a **Herramientas → Historial de Descargas SAT**
3. Verás una tabla con:
   - Fecha y hora de cada ejecución
   - RFC procesado
   - Período de descarga
   - Cantidad de CFDIs emitidos y recibidos
   - Estado (Completado/Error)

### **Consultar Logs del Servidor**

Si necesitas revisar logs técnicos:

```bash
# Logs de Laravel
tail -f /ruta/proyecto/storage/logs/laravel.log

# Logs de cron (si Plesk los genera)
# La ubicación varía según la configuración de Plesk
```

---

## 🔍 Troubleshooting (Solución de Problemas)

### **Problema: El cron no se ejecuta**

**Verificar:**
1. Que la tarea esté **activa** en Plesk
2. Que la ruta del proyecto sea **correcta**
3. Que la versión de PHP sea la correcta (mínimo PHP 8.1)
4. Que los permisos del archivo `artisan` sean ejecutables:
   ```bash
   chmod +x /ruta/proyecto/artisan
   ```

### **Problema: Error de permisos**

```bash
# Asegurar permisos correctos en directorios críticos
cd /ruta/proyecto
chmod -R 775 storage bootstrap/cache
chown -R usuario:grupo storage bootstrap/cache
```

Donde `usuario:grupo` es el usuario web del servidor (típicamente `apache`, `www-data`, o el usuario de Plesk).

### **Problema: No se descargan los CFDIs**

**Verificar:**
1. En **Herramientas → Historial de Descargas SAT** el estado de las ejecuciones
2. Que los Teams tengan `descarga_cfdi = 'SI'` en la base de datos
3. Que los archivos FIEL sean válidos y no estén expirados
4. Que la contraseña FIEL sea correcta

### **Problema: Timezone incorrecto**

Verificar en el archivo `.env` del servidor productivo:
```
APP_TIMEZONE=America/Mexico_City
```

---

## 📝 Notas Importantes

1. **Un solo cron es suficiente:** Solo necesitas configurar el cron `schedule:run` cada minuto. Laravel se encarga de ejecutar las tareas a la hora correcta.

2. **No duplicar tareas:** No crees múltiples crons para la misma tarea. El Scheduler de Laravel maneja todo automáticamente.

3. **Rutas absolutas:** Siempre usa rutas absolutas en Plesk para evitar problemas.

4. **Backup:** Antes de hacer cambios en producción, asegúrate de tener un backup de la base de datos.

5. **Monitoreo:** Revisa el "Historial de Descargas SAT" regularmente durante los primeros días para asegurar que todo funcione correctamente.

---

## 📞 Comando de Ayuda

Para ver las opciones disponibles del comando:
```bash
php artisan sat:descargar-automatico --help
```

Para ejecutar con fechas específicas:
```bash
php artisan sat:descargar-automatico --fecha-inicio=2026-02-01 --fecha-fin=2026-02-17
```

---

## ✅ Checklist Final

Antes de considerar la configuración completa, verifica:

- [ ] Cron job creado y activo en Plesk
- [ ] Frecuencia configurada a "cada minuto" (`* * * * *`)
- [ ] Ruta del proyecto correcta
- [ ] Comando probado manualmente con éxito
- [ ] `schedule:list` muestra la tarea de descargas SAT
- [ ] `.env` tiene `APP_TIMEZONE=America/Mexico_City`
- [ ] Historial de Descargas SAT accesible desde el sistema
- [ ] Primera ejecución automática verificada al día siguiente

---

**Fecha de creación:** 17/02/2026
**Versión:** 1.0
