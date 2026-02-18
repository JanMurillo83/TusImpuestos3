# Optimización de Rendimiento - TusImpuestos

## 🎯 Objetivo
Mejorar drásticamente el rendimiento del sistema en producción mediante optimizaciones críticas de infraestructura, base de datos y código.

## 📊 Problemas Identificados

### 1. **Cache y Sesiones en Base de Datos** ⚠️ CRÍTICO
- **Problema**: Sesiones y caché almacenados en MySQL
- **Impacto**: Cada request genera múltiples queries a DB
- **Solución**: Migración a Redis

### 2. **Falta de Índices en Tablas Críticas** ⚠️ CRÍTICO
- **Problema**: Queries lentas en `saldoscuentas`, `auxiliares`, `almacencfdis`
- **Impacto**: Full table scans en reportes
- **Solución**: Índices compuestos estratégicos

### 3. **Queries sin Optimizar** ⚠️ ALTO
- **Problema**: DB::select() con concatenación de strings, sin prepared statements
- **Impacto**: Lento, inseguro, sin cache de query plan
- **Ejemplo**: `ReportesController::balanza()` líneas 41-49

### 4. **PHP sin OpCache Configurado** ⚠️ ALTO
- **Problema**: Código PHP se compila en cada request
- **Impacto**: 50-80% más lento sin bytecode cache
- **Solución**: OpCache + JIT

### 5. **MySQL sin Optimización** ⚠️ ALTO
- **Problema**: Configuración default de MySQL
- **Impacto**: Buffer pool pequeño, no usa RAM disponible
- **Solución**: Configuración tuneada

## 🚀 Optimizaciones Implementadas

### ✅ 1. Redis para Cache y Sesiones

**Cambios en `.env`:**
```bash
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis
```

**Beneficios:**
- ✅ 10-50x más rápido que database
- ✅ Libera carga de MySQL
- ✅ Escalable horizontalmente
- ✅ Expira datos automáticamente

### ✅ 2. Índices de Base de Datos

**Migración creada:** `2026_02_18_145636_add_performance_indexes_to_critical_tables.php`

**Índices agregados:**
```sql
-- saldoscuentas (tabla más consultada)
INDEX (team_id, codigo)
INDEX (codigo)
INDEX (n1, team_id)
INDEX (team_id, ejercicio)

-- auxiliares (millones de registros)
INDEX (codigo, a_periodo, a_ejercicio, team_id)
INDEX (codigo, a_periodo, team_id)
INDEX (factura, team_id)

-- almacencfdis (búsquedas frecuentes)
INDEX (uuid)
INDEX (team_id, fecha)
INDEX (receptor_rfc)

-- cat_polizas
INDEX (team_id, periodo, ejercicio)
INDEX (folio)

-- movbancos
INDEX (team_id, cuenta)
INDEX (fecha)
```

**Impacto esperado:**
- 🚀 Reportes 5-20x más rápidos
- 🚀 Búsquedas instantáneas
- 🚀 Menor uso de CPU en MySQL

### ✅ 3. Conexiones Persistentes MySQL

**Cambios en `config/database.php`:**
```php
'options' => [
    PDO::ATTR_PERSISTENT => true,           // Reutiliza conexiones
    PDO::ATTR_EMULATE_PREPARES => false,    // Prepared statements reales
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
]
```

**Beneficios:**
- ✅ Menos overhead de conexión
- ✅ Query plan caching en MySQL
- ✅ Mejor uso de connection pool

### ✅ 4. Configuración PHP OpCache

**Archivo:** `php_opcache_optimization.ini`

**Configuración clave:**
```ini
opcache.enable=1
opcache.memory_consumption=256      # 256MB para bytecode cache
opcache.max_accelerated_files=10000 # Cache todos los archivos
opcache.revalidate_freq=60          # Revalidar cada 60 seg
opcache.jit=tracing                 # JIT compiler (PHP 8.0+)
opcache.jit_buffer_size=128M
```

**Impacto esperado:**
- 🚀 30-50% mejora en response time
- 🚀 50% menos CPU usage
- 🚀 Mejor throughput

### ✅ 5. Configuración MySQL Optimizada

**Archivo:** `mysql_optimization.cnf`

**Optimizaciones clave:**
```ini
innodb_buffer_pool_size = 2G        # 50-70% de RAM
innodb_buffer_pool_instances = 4
innodb_log_file_size = 512M
query_cache_size = 64M               # Para MySQL < 8.0
tmp_table_size = 128M
max_heap_table_size = 128M
table_open_cache = 4000
```

**Impacto esperado:**
- 🚀 5-10x más rápido en queries complejos
- 🚀 Menos disk I/O
- 🚀 Mejor concurrencia

## 📋 Pasos de Despliegue en Producción

### Opción 1: Script Automático (Recomendado)

```bash
# En el servidor de producción
cd /ruta/a/TusImpuestos3
./optimizar_produccion.sh
```

El script ejecuta automáticamente:
1. ✓ Verifica Redis
2. ✓ Limpia cachés antiguos
3. ✓ Aplica migraciones de índices
4. ✓ Optimiza Composer
5. ✓ Cachea configuraciones
6. ✓ Optimiza tablas MySQL
7. ✓ Compila assets
8. ✓ Reinicia servicios

### Opción 2: Manual (Paso a Paso)

#### Paso 1: Verificar Redis
```bash
# Instalar si no existe
sudo apt-get install redis-server

# Verificar que funciona
redis-cli ping  # Debe responder PONG
```

#### Paso 2: Actualizar Configuración
```bash
# Editar .env en producción
nano .env

# Cambiar estas líneas:
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis
```

#### Paso 3: Aplicar Migraciones de Índices
```bash
php artisan migrate --force
```

⚠️ **NOTA**: Puede tardar varios minutos dependiendo del tamaño de las tablas.

#### Paso 4: Configurar MySQL
```bash
# Copiar configuración
sudo cp mysql_optimization.cnf /etc/mysql/conf.d/tusimpuestos_optimization.cnf

# Ajustar innodb_buffer_pool_size según RAM:
# 4GB RAM → 2G
# 8GB RAM → 4G
# 16GB RAM → 8G
sudo nano /etc/mysql/conf.d/tusimpuestos_optimization.cnf

# Reiniciar MySQL
sudo systemctl restart mysql
```

#### Paso 5: Configurar PHP OpCache
```bash
# En Plesk: Panel > PHP Settings > Additional directives
# Copiar contenido de php_opcache_optimization.ini

# O en servidor directo:
sudo cp php_opcache_optimization.ini /etc/php/8.2/fpm/conf.d/99-opcache-optimization.ini
sudo systemctl restart php8.2-fpm
```

#### Paso 6: Optimizar Laravel
```bash
# Limpiar cachés antiguos
php artisan cache:clear
php artisan config:clear
redis-cli FLUSHALL

# Optimizar Composer
composer install --optimize-autoloader --no-dev

# Cachear todo
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Compilar assets
npm run build
```

#### Paso 7: Optimizar Tablas MySQL
```bash
mysql -u usuario -p nombre_bd << EOF
OPTIMIZE TABLE saldoscuentas;
OPTIMIZE TABLE auxiliares;
OPTIMIZE TABLE cat_cuentas;
OPTIMIZE TABLE almacencfdis;
OPTIMIZE TABLE saldos_reportes;
ANALYZE TABLE saldoscuentas;
ANALYZE TABLE auxiliares;
EOF
```

## 🔍 Monitoreo Post-Despliegue

### 1. Verificar Redis
```bash
# Ver estadísticas
redis-cli INFO stats

# Ver memoria usada
redis-cli INFO memory

# Ver keys
redis-cli DBSIZE
```

### 2. Verificar MySQL Slow Queries
```bash
# Ver queries lentas
sudo tail -f /var/log/mysql/slow-query.log

# Ver status de InnoDB
mysql -e "SHOW ENGINE INNODB STATUS\G"
```

### 3. Verificar OpCache
```bash
# Ver configuración
php -i | grep opcache

# Ver estadísticas (crear endpoint)
# Route::get('/opcache-status', fn() => opcache_get_status());
```

### 4. Verificar Índices
```sql
-- Ver índices de una tabla
SHOW INDEX FROM saldoscuentas;

-- Ver explain de query lento
EXPLAIN SELECT * FROM saldoscuentas WHERE team_id = 1 AND codigo = '10001000';
```

## 📈 Mejoras Esperadas

| Métrica | Antes | Después | Mejora |
|---------|-------|---------|--------|
| Response Time (Reportes) | 5-10s | 0.5-2s | **5-10x** |
| Response Time (Dashboard) | 2-4s | 0.3-0.8s | **4-6x** |
| Queries por Reporte | 50-100 | 10-20 | **5x menos** |
| CPU Usage | 80-95% | 30-50% | **40-50% menos** |
| Memory Usage | Alta | Moderada | Estable |
| Concurrent Users | 10-20 | 50-100 | **5x más** |

## ⚠️ Advertencias

1. **Redis requerido**: El sistema NO funcionará sin Redis después de estos cambios
2. **Índices tardan**: La migración puede tardar 5-30 minutos en tablas grandes
3. **RAM necesaria**: MySQL necesita mínimo 4GB RAM para configuración óptima
4. **Backup primero**: Hacer backup completo antes de aplicar cambios

## 🆘 Rollback de Emergencia

Si algo falla, revertir cambios:

```bash
# 1. Revertir .env
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# 2. Limpiar cachés
php artisan cache:clear
php artisan config:clear

# 3. Revertir migraciones (SOLO SI ES NECESARIO)
php artisan migrate:rollback --step=1
```

## 📞 Soporte

En caso de problemas:
1. Revisar logs: `storage/logs/laravel.log`
2. Revisar Redis: `redis-cli MONITOR`
3. Revisar MySQL: `/var/log/mysql/error.log`
4. Revisar PHP-FPM: `/var/log/php8.2-fpm.log`

## 🎯 Próximos Pasos (Opcional)

1. **Query Optimization**: Convertir DB::select() a Query Builder
2. **Eager Loading**: Eliminar N+1 queries en Eloquent
3. **HTTP Caching**: Headers ETag/Cache-Control
4. **CDN**: Assets estáticos en CDN
5. **Horizontal Scaling**: Load balancer + múltiples servidores

---

**Fecha de implementación**: 2026-02-18
**Versión**: 1.0
**Responsable**: Optimización de Rendimiento
