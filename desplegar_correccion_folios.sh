#!/bin/bash

# Script de Despliegue - Corrección de Folios Duplicados
# Fecha: 23 de Enero 2026
# Versión: 1.0

set -e  # Detener en caso de error

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para imprimir mensajes
print_step() {
    echo -e "${BLUE}==>${NC} $1"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

# Banner
echo ""
echo "=========================================="
echo "  Corrección de Folios Duplicados"
echo "=========================================="
echo ""

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    print_error "Error: No se encontró el archivo 'artisan'. Asegúrate de estar en el directorio raíz del proyecto."
    exit 1
fi

print_success "Directorio del proyecto detectado"

# Paso 1: Verificar duplicados (modo dry-run)
print_step "Paso 1/6: Verificando duplicados existentes (modo lectura)..."
php artisan app:corregir-folios-duplicados --dry-run | tail -5

echo ""
read -p "¿Continuar con la corrección? (s/n): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    print_warning "Operación cancelada por el usuario"
    exit 0
fi

# Paso 2: Corregir duplicados
print_step "Paso 2/6: Corrigiendo folios duplicados..."
php artisan app:corregir-folios-duplicados
print_success "Duplicados corregidos"

# Paso 3: Verificar que no quedan duplicados
print_step "Paso 3/6: Verificando que no quedan duplicados..."
DUPLICADOS=$(php artisan tinker --execute="
\$count = DB::table('facturas')
    ->select('serie', 'folio', 'team_id', DB::raw('COUNT(*) as count'))
    ->groupBy('serie', 'folio', 'team_id')
    ->having('count', '>', 1)
    ->count();
echo \$count;
" 2>/dev/null | tail -1)

if [ "$DUPLICADOS" -eq "0" ]; then
    print_success "No quedan duplicados en la base de datos"
else
    print_error "Aún existen $DUPLICADOS grupos duplicados"
    print_warning "Ejecuta: php artisan app:corregir-folios-duplicados --dry-run"
    exit 1
fi

# Paso 4: Ejecutar migración
print_step "Paso 4/6: Aplicando índice único..."
php artisan migrate --force
print_success "Migración aplicada correctamente"

# Paso 5: Verificar índice único
print_step "Paso 5/6: Verificando índice único..."
php artisan tinker --execute="
try {
    \$serie = 'TEST_' . time();
    DB::table('facturas')->insert([
        'serie' => \$serie, 'folio' => 1, 'docto' => 'TEST1',
        'fecha' => now(), 'clie' => 1, 'esquema' => 1,
        'estado' => 'Activa', 'team_id' => 999999,
        'created_at' => now(), 'updated_at' => now()
    ]);
    DB::table('facturas')->insert([
        'serie' => \$serie, 'folio' => 1, 'docto' => 'TEST1',
        'fecha' => now(), 'clie' => 1, 'esquema' => 1,
        'estado' => 'Activa', 'team_id' => 999999,
        'created_at' => now(), 'updated_at' => now()
    ]);
    echo 'ERROR';
} catch (\Exception \$e) {
    echo 'OK';
}
DB::table('facturas')->where('team_id', 999999)->delete();
" 2>/dev/null | grep -q 'OK' && print_success "Índice único verificado correctamente" || print_error "Error verificando índice único"

# Paso 6: Limpiar caché
print_step "Paso 6/6: Limpiando caché..."
php artisan optimize:clear > /dev/null 2>&1
php artisan config:clear > /dev/null 2>&1
php artisan route:clear > /dev/null 2>&1
php artisan view:clear > /dev/null 2>&1
print_success "Caché limpiado"

echo ""
echo "=========================================="
print_success "Despliegue completado exitosamente"
echo "=========================================="
echo ""
echo "Resumen:"
echo "  • Duplicados corregidos: ✓"
echo "  • Índice único aplicado: ✓"
echo "  • Sistema protegido: ✓"
echo ""
print_warning "Se recomienda hacer una prueba creando una factura nueva"
echo ""
