#!/bin/bash

# ========================================================================
# Script de Optimización para Producción - TusImpuestos
# ========================================================================
# Este script aplica todas las optimizaciones críticas de rendimiento
# USAR SOLO EN SERVIDOR DE PRODUCCIÓN

set -e

echo "=========================================="
echo "Optimización de Rendimiento - TusImpuestos"
echo "=========================================="
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# ========================================
# 1. VERIFICAR REDIS
# ========================================
echo -e "${YELLOW}[1/8] Verificando Redis...${NC}"
if ! command -v redis-cli &> /dev/null; then
    echo -e "${RED}⚠️  Redis no está instalado${NC}"
    echo "Instalar con: sudo apt-get install redis-server"
    exit 1
fi

if ! redis-cli ping &> /dev/null; then
    echo -e "${RED}⚠️  Redis no está corriendo${NC}"
    echo "Iniciar con: sudo systemctl start redis-server"
    exit 1
fi
echo -e "${GREEN}✓ Redis funcionando correctamente${NC}"
echo ""

# ========================================
# 2. LIMPIAR CACHÉS ANTIGUOS
# ========================================
echo -e "${YELLOW}[2/8] Limpiando cachés antiguos...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
redis-cli FLUSHALL
echo -e "${GREEN}✓ Cachés limpiados${NC}"
echo ""

# ========================================
# 3. EJECUTAR MIGRACIONES DE ÍNDICES
# ========================================
echo -e "${YELLOW}[3/8] Aplicando índices de base de datos...${NC}"
php artisan migrate --force
echo -e "${GREEN}✓ Índices aplicados${NC}"
echo ""

# ========================================
# 4. OPTIMIZAR COMPOSER
# ========================================
echo -e "${YELLOW}[4/8] Optimizando autoloader de Composer...${NC}"
composer install --optimize-autoloader --no-dev
echo -e "${GREEN}✓ Composer optimizado${NC}"
echo ""

# ========================================
# 5. CACHEAR CONFIGURACIONES
# ========================================
echo -e "${YELLOW}[5/8] Cacheando configuraciones de Laravel...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
echo -e "${GREEN}✓ Configuraciones cacheadas${NC}"
echo ""

# ========================================
# 6. OPTIMIZAR TABLAS MYSQL
# ========================================
echo -e "${YELLOW}[6/8] Optimizando tablas MySQL...${NC}"
DB_DATABASE=$(php -r "require 'vendor/autoload.php'; echo env('DB_DATABASE');")
DB_USERNAME=$(php -r "require 'vendor/autoload.php'; echo env('DB_USERNAME');")
DB_PASSWORD=$(php -r "require 'vendor/autoload.php'; echo env('DB_PASSWORD');")

mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "
    OPTIMIZE TABLE saldoscuentas;
    OPTIMIZE TABLE auxiliares;
    OPTIMIZE TABLE cat_cuentas;
    OPTIMIZE TABLE almacencfdis;
    OPTIMIZE TABLE saldos_reportes;
    ANALYZE TABLE saldoscuentas;
    ANALYZE TABLE auxiliares;
    ANALYZE TABLE cat_cuentas;
"
echo -e "${GREEN}✓ Tablas optimizadas${NC}"
echo ""

# ========================================
# 7. COMPILAR ASSETS
# ========================================
echo -e "${YELLOW}[7/8] Compilando assets frontend...${NC}"
npm run build
echo -e "${GREEN}✓ Assets compilados${NC}"
echo ""

# ========================================
# 8. REINICIAR SERVICIOS
# ========================================
echo -e "${YELLOW}[8/8] Reiniciando servicios...${NC}"

# PHP-FPM
if command -v php-fpm &> /dev/null; then
    sudo systemctl restart php*-fpm
    echo -e "${GREEN}✓ PHP-FPM reiniciado${NC}"
fi

# Queue workers (si existen)
if [ -f "/etc/systemd/system/laravel-worker.service" ]; then
    sudo systemctl restart laravel-worker
    echo -e "${GREEN}✓ Queue workers reiniciados${NC}"
fi

# OpCache reset (crear endpoint temporal)
php artisan opcache:clear 2>/dev/null || echo "OpCache clear manual requerido"
echo ""

# ========================================
# RESUMEN
# ========================================
echo "=========================================="
echo -e "${GREEN}✓ Optimización completada exitosamente${NC}"
echo "=========================================="
echo ""
echo "Configuraciones aplicadas:"
echo "  ✓ Redis para caché y sesiones"
echo "  ✓ Índices de base de datos"
echo "  ✓ Composer optimizado"
echo "  ✓ Configuraciones cacheadas"
echo "  ✓ Tablas MySQL optimizadas"
echo "  ✓ Assets compilados"
echo ""
echo -e "${YELLOW}Configuraciones adicionales manuales:${NC}"
echo "  1. Aplicar mysql_optimization.cnf a MySQL"
echo "  2. Aplicar php_opcache_optimization.ini a PHP"
echo "  3. Reiniciar MySQL y PHP-FPM después de aplicar configs"
echo ""
echo -e "${GREEN}Monitorear rendimiento:${NC}"
echo "  - Logs lentos MySQL: /var/log/mysql/slow-query.log"
echo "  - Estado Redis: redis-cli INFO stats"
echo "  - OpCache: php -i | grep opcache"
echo ""
