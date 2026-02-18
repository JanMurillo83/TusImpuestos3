# Sistema Híbrido de Descarga de CFDIs del SAT

## Descripción General

Se ha implementado un sistema híbrido inteligente que combina **dos métodos** de descarga de CFDIs del SAT con **fallback automático** para maximizar la confiabilidad y eficiencia.

## Métodos Disponibles

### 1. **Descarga Masiva (Web Service SOAP)**
- **Servicio**: `SatDescargaMasivaService`
- **Librería**: `phpcfdi/sat-ws-descarga-masiva` v0.5.4
- **Características**:
  - API oficial del SAT
  - Ideal para períodos largos (>30 días)
  - Descarga solo XMLs
  - Proceso asíncrono (solicitud → verificación → descarga)
  - Muy rápido para volúmenes grandes

### 2. **Scraper del Portal SAT**
- **Servicio**: `CfdiSatScraperService`
- **Librería**: `phpcfdi/cfdi-sat-scraper` v5.0
- **Características**:
  - Web scraping del portal SAT
  - Ideal para períodos cortos (≤30 días)
  - Descarga XML + PDF
  - Proceso síncrono directo
  - Mejor para consultas manuales

## Estrategia Híbrida con Fallback

### Lógica de Decisión

```
┌─────────────────────────────────────┐
│ Usuario selecciona fechas           │
└──────────────┬──────────────────────┘
               │
               ▼
    ¿Período > 30 días?
               │
       ┌───────┴───────┐
       │               │
      SÍ              NO
       │               │
       ▼               ▼
  DESCARGA         SCRAPER
   MASIVA          (método
  (método          principal)
  principal)          │
       │              │
       ▼              ▼
    ¿Éxito?       ¿Éxito?
       │              │
    ┌──┴──┐        ┌──┴──┐
    SÍ   NO        SÍ   NO
    │     │        │     │
    │     └────────┼─────┘
    │    FALLBACK  │
    │   al método  │
    │    alterno   │
    │              │
    └──────┬───────┘
           │
           ▼
    ✅ COMPLETADO
```

### Implementación

**Archivo**: `app/Filament/Clusters/Herramientas/Pages/DescargasSAT.php`

#### Action Individual (línea 366-492)
- Descarga para un solo Team
- Decide automáticamente el método según días
- Si falla, intenta con el método alterno
- Muestra notificaciones de progreso

#### Header Action Masivo (línea 535-632)
- Descarga para todos los Teams con `descarga_cfdi = 'SI'`
- Misma lógica híbrida por cada Team
- Cuenta exitosos y fallidos
- Logging en tabla `valida_descargas`

**Archivo**: `app/Filament/Resources/TempCfdisResource.php`

#### Action "Consultar" (línea 123-207)
- Consulta CFDIs del SAT para mostrar en tabla temporal
- Usa estrategia híbrida para consulta rápida
- **>30 días**: Descarga masiva (descarga XMLs y extrae metadata)
- **≤30 días**: Scraper (consulta directa sin descargar)
- Muestra método usado en notificación
- Permite marcar faltantes y descargar selectivamente

**Archivo**: `app/Http/Controllers/NewCFDI.php`

#### Método `Scraper()` (línea 47-200)
- Backend de la funcionalidad "Consultar"
- Estrategia híbrida con fallback automático
- Retorna metadata compatible con ambos métodos
- `consultarConScraper()`: Usa scraper tradicional (solo consulta)
- `consultarConDescargaMasiva()`: Descarga y extrae metadata de XMLs
- `extractMetadataFromXmls()`: Parsea XMLs para crear objetos compatibles

**Comando**: `app/Console/Commands/DescargaAutomaticaSAT.php`

#### Comando Artisan `sat:descargar-automatico` (línea 34-304)
- Descarga automática programable para tareas CRON
- Uso: `php artisan sat:descargar-automatico [--fecha-inicio=Y-m-d] [--fecha-fin=Y-m-d]`
- Estrategia híbrida con fallback automático
- Muestra progreso en consola con indicadores visuales
- Registra método usado en base de datos
- Por defecto descarga del primer día del mes hasta hoy

## Flujo de Descarga Masiva

### 1. Solicitud
```php
$masivaService = new SatDescargaMasivaService($record);
$result = $masivaService->solicitarDescarga($fecha_inicial, $fecha_final, 'emitidos');
// Retorna: ['success' => true, 'request_id' => '...']
```

### 2. Verificación y Descarga
```php
$result = $masivaService->verificarYDescargar($requestId, 'emitidos', 3, 10);
// Intenta 3 veces, esperando 10 segundos entre cada intento
// Retorna: ['success' => true, 'paquetes' => [...]]
```

### 3. Proceso Completo
```php
$result = $masivaService->descargarCompleto($fecha_inicial, $fecha_final, 'emitidos');
// Ejecuta solicitud + verificación + descarga + extracción automática
```

## Archivos Creados/Modificados

### Nuevos Archivos
1. **`app/Services/SatDescargaMasivaService.php`**
   - Servicio encapsulado para descarga masiva
   - Métodos: solicitar, verificar, descargar, extraer ZIP
   - Validación de FIEL
   - Logging estructurado

### Archivos Modificados
1. **`app/Filament/Clusters/Herramientas/Pages/DescargasSAT.php`**
   - Import de `SatDescargaMasivaService` (línea 73)
   - Método helper `descargarConScraper()` (línea 708-746)
   - Action 'Descargas' individual con estrategia híbrida (línea 366-492)
   - Header Action 'Descarga' masivo con estrategia híbrida (línea 535-632)

2. **`app/Http/Controllers/NewCFDI.php`**
   - Import de `SatDescargaMasivaService` (línea 30)
   - Método `Scraper()` con estrategia híbrida (línea 47-75)
   - Método helper `consultarConScraper()` (línea 80-115)
   - Método helper `consultarConDescargaMasiva()` (línea 120-148)
   - Método helper `extractMetadataFromXmls()` (línea 153-200)

3. **`app/Filament/Resources/TempCfdisResource.php`**
   - Action 'Consultar' actualizado para mostrar método usado
   - Compatible con ambos formatos de metadata (línea 140-207)

4. **`app/Console/Commands/DescargaAutomaticaSAT.php`**
   - Import de `SatDescargaMasivaService` (línea 25)
   - Método `procesarTeam()` con estrategia híbrida (línea 135-253)
   - Método helper `descargarConScraper()` (línea 258-302)
   - Muestra método usado en console output
   - Registra método en ValidaDescargas

## Logging y Monitoreo

### Tabla `valida_descargas`
El campo `estado` ahora incluye el método usado:
- `"Completado - Descarga Masiva"`
- `"Completado - Scraper"`
- `"Completado - Descarga Masiva (Fallback)"`
- `"Completado - Scraper (Fallback)"`
- `"Error: [mensaje de error]"`

### Logs de Laravel
Todos los servicios usan `Log::info()` y `Log::error()` con contexto:
```php
Log::info('Solicitud de descarga masiva aceptada', [
    'team_id' => $this->team->id,
    'rfc' => $this->config['rfc'],
    'request_id' => $requestId,
    'tipo' => $tipo
]);
```

## Configuración

### Umbral de Decisión
Actualmente configurado en **30 días**:

```php
$dias = SatDescargaMasivaService::calcularDias($fecha_inicial, $fecha_final);
$usarDescargaMasiva = $dias > 30;
```

Para cambiar el umbral, modificar la línea 373 y 542 de `DescargasSAT.php`.

### Reintentos de Verificación
La descarga masiva intenta verificar el estado **3 veces** con **10 segundos** de espera:

```php
$descarga = $this->verificarYDescargar($requestId, $tipo, 3, 10);
```

Para ajustar, modificar los parámetros en `SatDescargaMasivaService.php` línea 281.

## Uso del Comando Artisan

### Descarga Automática Programada

El comando `sat:descargar-automatico` permite ejecutar descargas desde la terminal o CRON:

#### Ejemplos de Uso

```bash
# Descargar del primer día del mes hasta hoy (por defecto)
php artisan sat:descargar-automatico

# Descargar un período específico
php artisan sat:descargar-automatico --fecha-inicio=2026-01-01 --fecha-fin=2026-01-31

# Descargar solo el día de ayer
php artisan sat:descargar-automatico --fecha-inicio=2026-02-17 --fecha-fin=2026-02-17
```

#### Salida del Comando

```
==============================================
Iniciando Descarga Automática de CFDIs del SAT
==============================================
Período: 2026-02-01 al 2026-02-17
Cookies limpiadas: 5 archivo(s) eliminado(s).
Teams a procesar: 3

Procesando: Empresa ABC SA de CV (RFC: ABC123456789)
  → Usando Scraper (17 días)
  ✓ Completado - Emitidos: 45, Recibidos: 32

Procesando: Servicios XYZ SA de CV (RFC: XYZ987654321)
  → Usando Descarga Masiva (60 días)
  ✓ Completado - Emitidos: 250, Recibidos: 180

Procesando: Comercial 123 SA de CV (RFC: COM111222333)
  → Usando Scraper (17 días)
  ⚠ Scraper falló, intentando con Descarga Masiva...
  ✓ Completado - Emitidos: 15, Recibidos: 8

==============================================
Proceso finalizado
Exitosos: 3 | Fallidos: 0
==============================================
```

#### Configurar en CRON

Para ejecutar automáticamente todos los días a las 2:00 AM:

```bash
# Editar crontab
crontab -e

# Agregar línea
0 2 * * * cd /ruta/proyecto && php artisan sat:descargar-automatico >> /var/log/sat-descarga.log 2>&1
```

## Ventajas del Sistema Híbrido

1. **Redundancia**: Si un servicio falla, el otro toma el control
2. **Eficiencia**: Usa el método más apropiado según el caso
3. **Transparencia**: Usuario no necesita conocer los detalles técnicos
4. **Logging**: Registro completo de qué método se usó y por qué
5. **Escalabilidad**: Funciona igual para 1 team o 100 teams
6. **Automatización**: Compatible con tareas programadas (CRON)

## Próximas Mejoras Potenciales

### 1. Descarga de PDFs en Descarga Masiva
Actualmente la descarga masiva solo obtiene XMLs. Se puede complementar:
```php
// Después de extraer XMLs
$uuids = // extraer UUIDs de los XMLs descargados
$scraperService = new CfdiSatScraperService($record);
$scraperService->downloadPdfsByUuids($uuids);
```

### 2. Comando Artisan para Descargas Programadas
```bash
php artisan sat:descargar-masivo --fecha-inicio=2025-01-01 --fecha-fin=2025-02-01
```

### 3. Cola de Procesamiento
Para descargas muy grandes, usar Laravel Queue:
```php
DescargaMasivaJob::dispatch($record, $fecha_inicial, $fecha_final);
```

### 4. Notificaciones por Email/Slack
Cuando se complete una descarga masiva o falle después del fallback.

## Soporte y Troubleshooting

### Error: "Solicitud aún en proceso"
El SAT puede tardar en procesar solicitudes grandes. Aumentar intentos o tiempo de espera.

### Error: "FIEL expirada"
Verificar vigencia con el action "Validacion FIEL" en la interfaz.

### Error: "No se pudo abrir el archivo ZIP"
Verificar permisos en `/storage/app/public/zipdescargas/`.

### Logs Detallados
```bash
tail -f storage/logs/laravel.log | grep "SatDescargaMasiva"
tail -f storage/logs/laravel.log | grep "SatScraper"
```

## Licencias y Dependencias

- **phpcfdi/sat-ws-descarga-masiva**: MIT License
- **phpcfdi/cfdi-sat-scraper**: MIT License
- **phpcfdi/credentials**: MIT License

---

**Fecha de Implementación**: 2026-02-18
**Versión**: 1.0
**Autor**: Sistema TusImpuestos3
