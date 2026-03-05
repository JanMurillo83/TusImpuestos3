<?php

namespace App\Services\Herramientas;

use App\Models\Clientes;
use App\Models\Inventario;
use App\Models\Proveedores;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CatalogosImportService
{
    /**
     * Importa los catálogos indicados.
     *
     * @param array{inventario?:string|null,clientes?:string|null,proveedores?:string|null} $paths
     * @return array<string,mixed>
     */
    public function import(int $teamId, array $paths): array
    {
        $result = [
            'inventario' => null,
            'clientes' => null,
            'proveedores' => null,
        ];

        if (!empty($paths['inventario'])) {
            $result['inventario'] = $this->importInventario($teamId, $paths['inventario']);
        }
        if (!empty($paths['clientes'])) {
            $result['clientes'] = $this->importClientes($teamId, $paths['clientes']);
        }
        if (!empty($paths['proveedores'])) {
            $result['proveedores'] = $this->importProveedores($teamId, $paths['proveedores']);
        }

        return $result;
    }

    /** @return array{created:int,updated:int,skipped:int} */
    public function importInventario(int $teamId, string $absolutePath): array
    {
        $rows = $this->readRows($absolutePath);
        $created = 0;
        $updated = 0;
        $skipped = 0;

        $nextClave = $this->nextNumericClave(Inventario::class, $teamId);

        foreach ($rows as $row) {
            $descripcion = trim((string) ($row['descripcion'] ?? ''));
            $clave = trim((string) ($row['clave'] ?? ''));

            if ($descripcion === '' && $clave === '') {
                $skipped++;
                continue;
            }

            if ($clave === '') {
                $clave = (string) $nextClave;
                $nextClave++;
            }

            $payload = $this->onlyKeys($row, [
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
            ]);
            $payload['clave'] = $clave;
            $payload['team_id'] = $teamId;

            $model = Inventario::query()->where('team_id', $teamId)->where('clave', $clave)->first();
            if ($model) {
                $model->fill($payload)->save();
                $updated++;
            } else {
                Inventario::create($payload);
                $created++;
            }
        }

        return compact('created', 'updated', 'skipped');
    }

    /** @return array{created:int,updated:int,skipped:int} */
    public function importClientes(int $teamId, string $absolutePath): array
    {
        $rows = $this->readRows($absolutePath);
        $created = 0;
        $updated = 0;
        $skipped = 0;

        $nextClave = $this->nextNumericClave(Clientes::class, $teamId);

        foreach ($rows as $row) {
            $nombre = trim((string) ($row['nombre'] ?? ''));
            $clave = trim((string) ($row['clave'] ?? ''));

            if ($nombre === '' && $clave === '') {
                $skipped++;
                continue;
            }

            if ($clave === '') {
                $clave = (string) $nextClave;
                $nextClave++;
            }

            $payload = $this->onlyKeys($row, [
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
            ]);

            $payload['clave'] = $clave;
            $payload['team_id'] = $teamId;

            if (isset($payload['rfc'])) {
                $payload['rfc'] = strtoupper(trim((string) $payload['rfc']));
            }

            $model = Clientes::query()->where('team_id', $teamId)->where('clave', $clave)->first();
            if ($model) {
                $model->fill($payload)->save();
                $updated++;
            } else {
                Clientes::create($payload);
                $created++;
            }
        }

        return compact('created', 'updated', 'skipped');
    }

    /** @return array{created:int,updated:int,skipped:int} */
    public function importProveedores(int $teamId, string $absolutePath): array
    {
        $rows = $this->readRows($absolutePath);
        $created = 0;
        $updated = 0;
        $skipped = 0;

        $nextClave = $this->nextNumericClave(Proveedores::class, $teamId);

        foreach ($rows as $row) {
            $nombre = trim((string) ($row['nombre'] ?? ''));
            $clave = trim((string) ($row['clave'] ?? ''));

            if ($nombre === '' && $clave === '') {
                $skipped++;
                continue;
            }

            if ($clave === '') {
                $clave = (string) $nextClave;
                $nextClave++;
            }

            $payload = $this->onlyKeys($row, [
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
            ]);

            $payload['clave'] = $clave;
            $payload['team_id'] = $teamId;

            if (isset($payload['rfc'])) {
                $payload['rfc'] = strtoupper(trim((string) $payload['rfc']));
            }

            $model = Proveedores::query()->where('team_id', $teamId)->where('clave', $clave)->first();
            if ($model) {
                $model->fill($payload)->save();
                $updated++;
            } else {
                Proveedores::create($payload);
                $created++;
            }
        }

        return compact('created', 'updated', 'skipped');
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function readRows(string $absolutePath): array
    {
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        return match ($ext) {
            'csv' => $this->readCsvRows($absolutePath),
            'xlsx', 'xls' => $this->readExcelRows($absolutePath),
            default => throw new \InvalidArgumentException('Formato no soportado: ' . $ext),
        };
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function readCsvRows(string $absolutePath): array
    {
        $handle = fopen($absolutePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('No se pudo abrir el archivo CSV.');
        }

        $headers = null;
        $rows = [];

        try {
            while (($data = fgetcsv($handle)) !== false) {
                if ($headers === null) {
                    $headers = array_map(fn ($h) => $this->normalizeHeader($h), $data);
                    continue;
                }

                if ($headers === []) {
                    continue;
                }

                $row = [];
                foreach ($headers as $i => $key) {
                    if ($key === '') {
                        continue;
                    }
                    $row[$key] = $data[$i] ?? null;
                }
                $rows[] = $row;
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function readExcelRows(string $absolutePath): array
    {
        $spreadsheet = IOFactory::load($absolutePath);
        $sheet = $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        $headers = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $val = (string) $sheet->getCell([$col, 1])->getValue();
            $headers[$col] = $this->normalizeHeader($val);
        }

        $rows = [];
        for ($row = 2; $row <= $highestRow; $row++) {
            $assoc = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $key = $headers[$col] ?? '';
                if ($key === '') {
                    continue;
                }
                $assoc[$key] = $sheet->getCell([$col, $row])->getCalculatedValue();
            }
            $rows[] = $assoc;
        }

        return $rows;
    }

    private function normalizeHeader(mixed $header): string
    {
        $header = trim((string) $header);
        // Remove UTF-8 BOM if present (common in CSV exports).
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
        if ($header === '') {
            return '';
        }

        $header = Str::of($header)
            ->lower()
            ->replace([' ', '-', '.'], '_')
            ->replace('__', '_')
            ->toString();

        return $header;
    }

    /**
     * @param array<string,mixed> $row
     * @param array<int,string> $keys
     * @return array<string,mixed>
     */
    private function onlyKeys(array $row, array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== '') {
                $out[$key] = $row[$key];
            }
        }

        return $out;
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model> $modelClass
     */
    private function nextNumericClave(string $modelClass, int $teamId): int
    {
        $max = $modelClass::query()
            ->where('team_id', $teamId)
            ->pluck('clave')
            ->map(fn ($v) => (int) $v)
            ->max();

        return ((int) $max) + 1;
    }
}
