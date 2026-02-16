# Herramienta de Recontabilización de Saldos

## 🎯 Propósito

Esta herramienta permite **recalcular todos los saldos contables desde cero** a partir de los movimientos registrados en la tabla `auxiliares`. Es útil para:

- ✅ Garantizar la integridad de saldos en Indicadores y Reportes NIF
- ✅ Corregir inconsistencias después de migraciones o importaciones masivas
- ✅ Recalcular saldos después de correcciones manuales en auxiliares
- ✅ Validar la exactitud de los saldos actuales

---

## 📍 Acceso

### Ruta en Filament
```
/admin/recontabilizar-saldos
```

### Navegación
```
Panel Admin → Herramientas → Recontabilizar Saldos
```

---

## 🔧 Funcionalidades

### 1. Recontabilización Selectiva

Puedes elegir qué recontabilizar:

| Opción | Ejercicio | Periodo | Resultado |
|--------|-----------|---------|-----------|
| **Todo** | Vacío | Vacío | Recontabiliza TODOS los ejercicios y periodos |
| **Ejercicio completo** | Seleccionado | Vacío | Recontabiliza todos los periodos del ejercicio |
| **Periodo específico** | Seleccionado | Seleccionado | Recontabiliza solo ese periodo |

### 2. Opciones Adicionales

#### ✅ Recalcular jerarquía de cuentas padre
- **Recomendado:** ✅ Activado
- Actualiza los saldos de cuentas de mayor nivel (ej: si actualizas 1.1.01, también actualiza 1.1 y 1)
- Garantiza coherencia en reportes jerárquicos

#### 🗑️ Limpiar cache después del recálculo
- **Recomendado:** ✅ Activado
- Elimina el cache de saldos para forzar recarga
- Asegura que los reportes muestren datos recalculados inmediatamente

#### ✔️ Validar integridad después del recálculo
- **Recomendado:** ✅ Activado
- Ejecuta validaciones para detectar inconsistencias
- Muestra cantidad de problemas encontrados (si existen)

---

## 🚀 Casos de Uso

### Caso 1: Recontabilizar Todo el Sistema

**Cuándo usarlo:**
- Después de una migración de datos
- Después de correcciones masivas en auxiliares
- Para validar integridad completa del sistema

**Pasos:**
1. Acceder a `/admin/recontabilizar-saldos`
2. Dejar **Ejercicio** y **Periodo** vacíos
3. Mantener todas las opciones marcadas
4. Clic en "Recontabilizar Saldos"
5. Esperar confirmación (puede tomar varios minutos)

**Tiempo estimado:** 5-15 minutos (depende del volumen de datos)

---

### Caso 2: Recontabilizar un Ejercicio Completo

**Cuándo usarlo:**
- Después de correcciones en un ejercicio específico
- Para validar un ejercicio cerrado
- Antes de generar reportes anuales

**Pasos:**
1. Acceder a `/admin/recontabilizar-saldos`
2. Seleccionar **Ejercicio** (ej: 2026)
3. Dejar **Periodo** vacío
4. Mantener todas las opciones marcadas
5. Clic en "Recontabilizar Saldos"

**Tiempo estimado:** 2-5 minutos

---

### Caso 3: Recontabilizar un Periodo Específico

**Cuándo usarlo:**
- Después de corregir movimientos de un periodo
- Para validar cierre mensual
- Antes de generar reportes mensuales

**Pasos:**
1. Acceder a `/admin/recontabilizar-saldos`
2. Seleccionar **Ejercicio** (ej: 2026)
3. Seleccionar **Periodo** (ej: 01)
4. Mantener todas las opciones marcadas
5. Clic en "Recontabilizar Saldos"

**Tiempo estimado:** 30 segundos - 2 minutos

---

### Caso 4: Solo Limpiar Cache

**Cuándo usarlo:**
- Cuando los saldos son correctos pero el cache muestra datos viejos
- Después de cambios en configuración
- Para forzar recarga de reportes

**Pasos:**
1. Acceder a `/admin/recontabilizar-saldos`
2. Clic en "Limpiar Cache"
3. Confirmación inmediata

**Tiempo estimado:** Instantáneo

---

## 📊 Estadísticas Mostradas

La página muestra información en tiempo real:

| Métrica | Descripción |
|---------|-------------|
| **Total Auxiliares** | Cantidad total de movimientos registrados |
| **Cuentas en Saldos** | Cantidad de cuentas con saldos calculados |
| **Ejercicios** | Cantidad de ejercicios con movimientos |
| **Periodos (Actual)** | Cantidad de periodos en el ejercicio actual |
| **Última Actualización** | Tiempo desde la última actualización de saldos |

---

## ⚙️ Cómo Funciona (Técnico)

### Proceso de Recontabilización

1. **Identificación de periodos a procesar**
   - Consulta tabla `auxiliares` para obtener ejercicios/periodos con movimientos

2. **Para cada periodo:**
   - Obtiene todas las cuentas afectadas
   - Para cada cuenta:
     a. Elimina saldo existente en `saldos_reportes`
     b. Calcula saldo anterior (acumulado de periodos previos)
     c. Suma cargos y abonos del periodo actual
     d. Calcula saldo final: `anterior + cargos - abonos`
     e. Inserta nuevo registro en `saldos_reportes`

3. **Recalcular jerarquía (opcional)**
   - Para cada cuenta actualizada:
     - Identifica cuenta padre (ej: 1.1 es padre de 1.1.01)
     - Actualiza saldo de cuenta padre sumando hijos
     - Repite recursivamente hasta llegar a raíz

4. **Limpieza de cache (opcional)**
   - Elimina cache de saldos del team
   - Elimina cache tag 'saldos'

5. **Validación (opcional)**
   - Compara saldos en `saldos_reportes` vs calculados desde `auxiliares`
   - Reporta inconsistencias encontradas (si existen)

### Transacciones

Todo el proceso se ejecuta dentro de una **transacción de base de datos**:
- Si ocurre un error, se hace rollback completo
- Garantiza consistencia: todo o nada

### Logging

El proceso genera logs detallados en `storage/logs/laravel.log`:
- Inicio de proceso con parámetros
- Errores por cuenta/periodo (si existen)
- Resumen final con estadísticas

---

## ⚠️ Precauciones

### Antes de Ejecutar

1. **Backup de base de datos**
   ```bash
   mysqldump -u root -p TI130226 > backup_$(date +%Y%m%d_%H%M).sql
   ```

2. **Verificar horario**
   - Ejecutar fuera de horarios pico
   - Evitar mientras hay usuarios activos generando reportes

3. **Estimar tiempo**
   - Prueba primero con un solo periodo
   - Estima tiempo total antes de recontabilizar todo

### Durante la Ejecución

1. **No cerrar la ventana**
   - El proceso puede tomar varios minutos
   - Esperar hasta ver la notificación de completado

2. **Monitorear logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

### Después de Ejecutar

1. **Verificar notificación**
   - Cantidad de cuentas actualizadas
   - Errores encontrados (idealmente 0)
   - Inconsistencias detectadas (idealmente 0)

2. **Validar reportes**
   - Generar reporte de saldos
   - Verificar que los números sean correctos
   - Comparar con reportes anteriores (si aplica)

3. **Revisar logs**
   - Buscar errores en `storage/logs/laravel.log`
   - Investigar cualquier inconsistencia reportada

---

## 🔍 Troubleshooting

### Error: Timeout en el proceso

**Causa:** Demasiados datos para procesar de una vez

**Solución:**
1. Recontabilizar por ejercicio en lugar de todo
2. O recontabilizar por periodo específico
3. Aumentar timeout en `php.ini`: `max_execution_time = 600`

### Error: Memory limit exceeded

**Causa:** Insuficiente memoria PHP

**Solución:**
1. Aumentar en `php.ini`: `memory_limit = 512M`
2. Recontabilizar en lotes más pequeños

### Inconsistencias detectadas después de recontabilizar

**Posibles causas:**
1. Movimientos duplicados en `auxiliares`
2. Movimientos con ejercicio/periodo incorrecto
3. Problemas en la jerarquía de cuentas

**Solución:**
1. Ejecutar: `php artisan saldos:maintenance report`
2. Revisar sección de "Salud del Sistema"
3. Ejecutar: `php artisan saldos:maintenance auto-correct` (Fase 4)

### Reportes siguen mostrando datos viejos

**Causa:** Cache no se limpió correctamente

**Solución:**
```bash
# Vía web
Click en "Limpiar Cache" en la página de recontabilización

# O vía comando
php artisan cache:clear
php artisan config:clear
```

---

## 🔗 Integración con Otras Fases

### Fase 1: Caché Estratégico
- La recontabilización **limpia el cache automáticamente** (si la opción está activada)
- Después de recontabilizar, el primer acceso a reportes reconstruirá el cache

### Fase 2: Event-Driven
- La recontabilización es **manual e independiente** del sistema automático
- No interfiere con la actualización automática de saldos

### Fase 3: Monitoreo
- El dashboard `/admin/saldos-monitoring` muestra métricas actualizadas
- El audit log registra la recontabilización como evento

### Fase 4: Optimización Predictiva
- La recontabilización puede ejecutarse como parte del mantenimiento:
  ```bash
  # No disponible aún, pero puede integrarse
  php artisan saldos:maintenance recontabilizar --ejercicio=2026
  ```

---

## 📝 Recomendaciones

### Frecuencia de Uso

| Escenario | Frecuencia Recomendada |
|-----------|------------------------|
| **Sistema estable** | Trimestral o nunca (auto-actualización funciona bien) |
| **Después de migraciones** | Una vez |
| **Después de correcciones masivas** | Cada vez |
| **Validación de cierre anual** | Una vez al año |
| **Detección de problemas** | Según necesidad |

### Mejores Prácticas

1. **Prueba primero**
   - Recontabiliza un solo periodo de prueba
   - Verifica resultados antes de recontabilizar todo

2. **Documenta cambios**
   - Si haces correcciones en auxiliares, documenta qué y por qué
   - Facilita troubleshooting futuro

3. **Horarios off-peak**
   - Ejecuta durante madrugada o fines de semana
   - Minimiza impacto en usuarios

4. **Monitoreo post-recálculo**
   - Revisa reportes principales después de recontabilizar
   - Valida que números tengan sentido

---

## 🎓 Ejemplo Completo

### Escenario: Corrección de movimientos mal capturados en Enero 2026

**Situación:**
- Se detectaron movimientos con cuentas incorrectas en Enero 2026
- Se corrigieron manualmente los auxiliares
- Necesitas recalcular saldos para reflejar las correcciones

**Pasos:**

1. **Backup**
   ```bash
   mysqldump -u root -p TI130226 > backup_antes_recontabilizar.sql
   ```

2. **Acceder a herramienta**
   - Navegar a `/admin/recontabilizar-saldos`

3. **Configurar recontabilización**
   - Ejercicio: `2026`
   - Periodo: `01`
   - ✅ Recalcular jerarquía
   - ✅ Limpiar cache
   - ✅ Validar después

4. **Ejecutar**
   - Clic en "Recontabilizar Saldos"
   - Esperar notificación (aprox. 1 minuto)

5. **Verificar resultados**
   - Notificación muestra: "15 cuentas actualizadas, 0 errores, 0 inconsistencias"
   - Generar Balance de Comprobación de Enero 2026
   - Verificar que números reflejen las correcciones

6. **Validar con Fase 4**
   ```bash
   php artisan saldos:maintenance report
   ```
   - Revisar que no haya inconsistencias en "Salud del Sistema"

**Resultado:** Saldos de Enero 2026 actualizados correctamente ✅

---

## 📞 Soporte

### Documentación Relacionada

- `PRODUCTION_DEPLOYMENT.md` - Instalación completa del sistema
- `FASE4_IMPLEMENTATION.md` - Auto-corrección de inconsistencias
- `SALDOS_README.md` - Índice principal de documentación

### Comandos Relacionados

```bash
# Ver estado del sistema
php artisan saldos:phase status

# Generar reporte de salud
php artisan saldos:maintenance report

# Auto-corregir inconsistencias
php artisan saldos:maintenance auto-correct

# Validación de integridad
php artisan saldos:health-check
```

---

**Última actualización:** 16 de Febrero 2026
**Versión:** 1.0
**Ubicación:** `/admin/recontabilizar-saldos`
