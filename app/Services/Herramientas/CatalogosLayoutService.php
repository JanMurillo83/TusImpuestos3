<?php

namespace App\Services\Herramientas;

class CatalogosLayoutService
{
    /**
     * @return array<int,string>
     */
    public function headers(string $catalogo): array
    {
        return match ($catalogo) {
            'inventario' => [
                'clave',
                'descripcion',
                'linea',
                'marca',
                'modelo',
                'u_costo',
                'p_costo',
                'precio1',
                'precio2',
                'precio3',
                'precio4',
                'precio5',
                'exist',
                'esquema',
                'servicio',
                'unidad',
                'cvesat',
            ],
            'clientes' => [
                'clave',
                'nombre',
                'rfc',
                'id_fiscal',
                'regimen',
                'codigo',
                'direccion',
                'telefono',
                'correo',
                'correo2',
                'descuento',
                'lista',
                'contacto',
                'dias_credito',
                'cuenta_contable',
                'calle',
                'no_exterior',
                'no_interior',
                'colonia',
                'municipio',
                'estado',
            ],
            'proveedores' => [
                'clave',
                'nombre',
                'rfc',
                'id_fiscal',
                'direccion',
                'telefono',
                'correo',
                'contacto',
                'dias_credito',
                'cuenta_contable',
                'tipo_tercero',
                'tipo_operacion',
                'pais',
            ],
            default => throw new \InvalidArgumentException('Catálogo no soportado: ' . $catalogo),
        };
    }

    public function filename(string $catalogo): string
    {
        return 'layout_' . $catalogo . '.csv';
    }

    /**
     * Genera un CSV con headers y una fila vacía para que sirva como layout.
     */
    public function toCsv(string $catalogo): string
    {
        $headers = $this->headers($catalogo);

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new \RuntimeException('No se pudo crear el CSV temporal.');
        }

        try {
            fputcsv($handle, $headers);
            fputcsv($handle, array_fill(0, count($headers), ''));

            rewind($handle);
            $csv = stream_get_contents($handle);
            if ($csv === false) {
                throw new \RuntimeException('No se pudo leer el CSV generado.');
            }

            return $csv;
        } finally {
            fclose($handle);
        }
    }
}
