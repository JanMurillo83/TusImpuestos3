<?php

namespace App\Services;

use App\Models\Inventario;
use App\Models\Clientes;
use App\Models\PrecioVolumen;
use App\Models\PrecioVolumenCliente;

class PrecioCalculator
{
    /**
     * Calcular precio unitario para un producto según cliente y cantidad
     *
     * @param int $productoId
     * @param int $clienteId
     * @param float $cantidad
     * @param int $teamId
     * @return float
     */
    public static function calcularPrecio($productoId, $clienteId, $cantidad, $teamId)
    {
        $producto = Inventario::find($productoId);
        $cliente = Clientes::find($clienteId);

        if (!$producto || !$cliente) {
            return 0;
        }

        // 1. Verificar precios específicos del cliente (máxima prioridad)
        $precioCliente = PrecioVolumenCliente::where('cliente_id', $clienteId)
            ->where('producto_id', $productoId)
            ->where('team_id', $teamId)
            ->activos()
            ->vigentes()
            ->paraCantidad($cantidad)
            ->orderBy('prioridad', 'desc')
            ->first();

        if ($precioCliente) {
            return floatval($precioCliente->precio_unitario);
        }

        // 2. Buscar precio por volumen según lista del cliente
        $precioVolumen = PrecioVolumen::where('producto_id', $productoId)
            ->where('lista_precio', $cliente->lista)
            ->where('team_id', $teamId)
            ->activos()
            ->paraCantidad($cantidad)
            ->orderBy('cantidad_desde', 'desc')
            ->first();

        if ($precioVolumen) {
            return floatval($precioVolumen->precio_unitario);
        }

        // 3. Precio base según lista del cliente (fallback)
        $precioBase = match ($cliente->lista) {
            1 => $producto->precio1,
            2 => $producto->precio2,
            3 => $producto->precio3,
            4 => $producto->precio4,
            5 => $producto->precio5,
            default => $producto->precio1,
        };

        return floatval($precioBase);
    }

    /**
     * Obtener todos los rangos de precios para un producto y lista
     *
     * @param int $productoId
     * @param int $listaPrecio
     * @param int $teamId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function obtenerEscalaPrecios($productoId, $listaPrecio, $teamId)
    {
        return PrecioVolumen::where('producto_id', $productoId)
            ->where('lista_precio', $listaPrecio)
            ->where('team_id', $teamId)
            ->activos()
            ->orderBy('cantidad_desde', 'asc')
            ->get();
    }

    /**
     * Obtener precios especiales de un cliente para un producto
     *
     * @param int $clienteId
     * @param int $productoId
     * @param int $teamId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function obtenerPreciosEspecialesCliente($clienteId, $productoId, $teamId)
    {
        return PrecioVolumenCliente::where('cliente_id', $clienteId)
            ->where('producto_id', $productoId)
            ->where('team_id', $teamId)
            ->activos()
            ->vigentes()
            ->orderBy('cantidad_desde', 'asc')
            ->get();
    }

    /**
     * Obtener información detallada del precio aplicado
     *
     * @param int $productoId
     * @param int $clienteId
     * @param float $cantidad
     * @param int $teamId
     * @return array
     */
    public static function obtenerInfoPrecio($productoId, $clienteId, $cantidad, $teamId)
    {
        $producto = Inventario::find($productoId);
        $cliente = Clientes::find($clienteId);

        if (!$producto || !$cliente) {
            return [
                'precio' => 0,
                'tipo' => 'error',
                'descripcion' => 'Producto o cliente no encontrado'
            ];
        }

        // Verificar precio específico del cliente
        $precioCliente = PrecioVolumenCliente::where('cliente_id', $clienteId)
            ->where('producto_id', $productoId)
            ->where('team_id', $teamId)
            ->activos()
            ->vigentes()
            ->paraCantidad($cantidad)
            ->orderBy('prioridad', 'desc')
            ->first();

        if ($precioCliente) {
            return [
                'precio' => floatval($precioCliente->precio_unitario),
                'tipo' => 'cliente_especial',
                'descripcion' => "Precio especial para cliente ({$precioCliente->cantidad_desde}" .
                                ($precioCliente->cantidad_hasta ? "-{$precioCliente->cantidad_hasta}" : '+') . " unidades)"
            ];
        }

        // Buscar precio por volumen
        $precioVolumen = PrecioVolumen::where('producto_id', $productoId)
            ->where('lista_precio', $cliente->lista)
            ->where('team_id', $teamId)
            ->activos()
            ->paraCantidad($cantidad)
            ->orderBy('cantidad_desde', 'desc')
            ->first();

        if ($precioVolumen) {
            return [
                'precio' => floatval($precioVolumen->precio_unitario),
                'tipo' => 'volumen',
                'descripcion' => "Precio por volumen ({$precioVolumen->cantidad_desde}" .
                                ($precioVolumen->cantidad_hasta ? "-{$precioVolumen->cantidad_hasta}" : '+') . " unidades)"
            ];
        }

        // Precio base
        $precioBase = match ($cliente->lista) {
            1 => $producto->precio1,
            2 => $producto->precio2,
            3 => $producto->precio3,
            4 => $producto->precio4,
            5 => $producto->precio5,
            default => $producto->precio1,
        };

        $nombreLista = match ($cliente->lista) {
            1 => 'Precio Público',
            2 => 'Lista de Precios 2',
            3 => 'Lista de Precios 3',
            4 => 'Lista de Precios 4',
            5 => 'Lista de Precios 5',
            default => 'Precio Público',
        };

        return [
            'precio' => floatval($precioBase),
            'tipo' => 'base',
            'descripcion' => "Precio base ($nombreLista)"
        ];
    }
}
