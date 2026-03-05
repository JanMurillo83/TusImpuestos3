<?php

namespace Database\Seeders;

use App\Models\CatCuentas;
use App\Models\NominaConceptoCuenta;
use App\Models\Team;
use Illuminate\Database\Seeder;

class NominaConceptoCuentasSeeder extends Seeder
{
    private function normalizeCodigo(string $codigo): string
    {
        $codigo = trim($codigo);
        if ($codigo === '') {
            return $codigo;
        }

        if (ctype_digit($codigo)) {
            return str_pad($codigo, 3, '0', STR_PAD_LEFT);
        }

        return $codigo;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $catalogos = [
            'DEDUCCION' => [
                ['codigo' => '1', 'descripcion' => 'Seguridad social'],
                ['codigo' => '2', 'descripcion' => 'ISR'],
                ['codigo' => '3', 'descripcion' => 'Aportaciones a retiro, cesantía en edad avanzada y vejez.'],
                ['codigo' => '4', 'descripcion' => 'Otros'],
                ['codigo' => '5', 'descripcion' => 'Aportaciones a Fondo de vivienda'],
                ['codigo' => '6', 'descripcion' => 'Descuento por incapacidad'],
                ['codigo' => '7', 'descripcion' => 'Pensión alimenticia'],
                ['codigo' => '8', 'descripcion' => 'Renta'],
                ['codigo' => '9', 'descripcion' => 'Préstamos provenientes del Fondo Nacional de la Vivienda para los Trabajadores'],
                ['codigo' => '10', 'descripcion' => 'Pago por crédito de vivienda'],
                ['codigo' => '11', 'descripcion' => 'Pago de abonos INFONACOT'],
                ['codigo' => '12', 'descripcion' => 'Anticipo de salarios'],
                ['codigo' => '13', 'descripcion' => 'Pagos hechos con exceso al trabajador'],
                ['codigo' => '14', 'descripcion' => 'Errores'],
                ['codigo' => '15', 'descripcion' => 'Pérdidas'],
                ['codigo' => '16', 'descripcion' => 'Averías'],
                ['codigo' => '17', 'descripcion' => 'Adquisición de artículos producidos por la empresa o establecimiento'],
                ['codigo' => '18', 'descripcion' => 'Cuotas para la constitución y fomento de sociedades cooperativas y de cajas de ahorro'],
                ['codigo' => '19', 'descripcion' => 'Cuotas sindicales'],
                ['codigo' => '20', 'descripcion' => 'Ausencia (Ausentismo)'],
                ['codigo' => '21', 'descripcion' => 'Cuotas obrero patronales'],
                ['codigo' => '22', 'descripcion' => 'Impuestos Locales'],
                ['codigo' => '23', 'descripcion' => 'Aportaciones voluntarias'],
                ['codigo' => '24', 'descripcion' => 'Ajuste en Gratificación Anual (Aguinaldo) Exento'],
                ['codigo' => '25', 'descripcion' => 'Ajuste en Gratificación Anual (Aguinaldo) Gravado'],
                ['codigo' => '26', 'descripcion' => 'Ajuste en Participación de los Trabajadores en las Utilidades PTU Exento'],
                ['codigo' => '27', 'descripcion' => 'Ajuste en Participación de los Trabajadores en las Utilidades PTU Gravado'],
                ['codigo' => '28', 'descripcion' => 'Ajuste en Reembolso de Gastos Médicos Dentales y Hospitalarios Exento'],
                ['codigo' => '29', 'descripcion' => 'Ajuste en Fondo de ahorro Exento'],
                ['codigo' => '30', 'descripcion' => 'Ajuste en Caja de ahorro Exento'],
                ['codigo' => '31', 'descripcion' => 'Ajuste en Contribuciones a Cargo del Trabajador Pagadas por el Patrón Exento'],
                ['codigo' => '32', 'descripcion' => 'Ajuste en Premios por puntualidad Gravado'],
                ['codigo' => '33', 'descripcion' => 'Ajuste en Prima de Seguro de vida Exento'],
                ['codigo' => '34', 'descripcion' => 'Ajuste en Seguro de Gastos Médicos Mayores Exento'],
                ['codigo' => '35', 'descripcion' => 'Ajuste en Cuotas Sindicales Pagadas por el Patrón Exento'],
                ['codigo' => '36', 'descripcion' => 'Ajuste en Subsidios por incapacidad Exento'],
                ['codigo' => '37', 'descripcion' => 'Ajuste en Becas para trabajadores y/o hijos Exento'],
                ['codigo' => '38', 'descripcion' => 'Ajuste en Horas extra Exento'],
                ['codigo' => '39', 'descripcion' => 'Ajuste en Horas extra Gravado'],
                ['codigo' => '40', 'descripcion' => 'Ajuste en Prima dominical Exento'],
                ['codigo' => '41', 'descripcion' => 'Ajuste en Prima dominical Gravado'],
                ['codigo' => '42', 'descripcion' => 'Ajuste en Prima vacacional Exento'],
                ['codigo' => '43', 'descripcion' => 'Ajuste en Prima vacacional Gravado'],
                ['codigo' => '44', 'descripcion' => 'Ajuste en Prima por antigüedad Exento'],
                ['codigo' => '45', 'descripcion' => 'Ajuste en Prima por antigüedad Gravado'],
                ['codigo' => '46', 'descripcion' => 'Ajuste en Pagos por separación Exento'],
                ['codigo' => '47', 'descripcion' => 'Ajuste en Pagos por separación Gravado'],
                ['codigo' => '48', 'descripcion' => 'Ajuste en Seguro de retiro Exento'],
                ['codigo' => '49', 'descripcion' => 'Ajuste en Indemnizaciones Exento'],
                ['codigo' => '50', 'descripcion' => 'Ajuste en Indemnizaciones Gravado'],
                ['codigo' => '51', 'descripcion' => 'Ajuste en Reembolso por funeral Exento'],
                ['codigo' => '52', 'descripcion' => 'Ajuste en Cuotas de seguridad social pagadas por el patrón Exento'],
                ['codigo' => '53', 'descripcion' => 'Ajuste en Comisiones Gravado'],
                ['codigo' => '54', 'descripcion' => 'Ajuste en Vales de despensa Exento'],
                ['codigo' => '55', 'descripcion' => 'Ajuste en Vales de restaurante Exento'],
                ['codigo' => '56', 'descripcion' => 'Ajuste en Vales de gasolina Exento'],
                ['codigo' => '57', 'descripcion' => 'Ajuste en Vales de ropa Exento'],
                ['codigo' => '58', 'descripcion' => 'Ajuste en Ayuda para renta Exento'],
                ['codigo' => '59', 'descripcion' => 'Ajuste en Ayuda para artículos escolares Exento'],
                ['codigo' => '60', 'descripcion' => 'Ajuste en Ayuda para anteojos Exento'],
                ['codigo' => '61', 'descripcion' => 'Ajuste en Ayuda para transporte Exento'],
                ['codigo' => '62', 'descripcion' => 'Ajuste en Ayuda para gastos de funeral Exento'],
                ['codigo' => '63', 'descripcion' => 'Ajuste en Otros ingresos por salarios Exento'],
                ['codigo' => '64', 'descripcion' => 'Ajuste en Otros ingresos por salarios Gravado'],
                ['codigo' => '65', 'descripcion' => 'Ajuste en Jubilaciones, pensiones o haberes de retiro en una sola exhibición Exento'],
                ['codigo' => '66', 'descripcion' => 'Ajuste en Jubilaciones, pensiones o haberes de retiro en una sola exhibición Gravado'],
                ['codigo' => '67', 'descripcion' => 'Ajuste en Pagos por separación Acumulable'],
                ['codigo' => '68', 'descripcion' => 'Ajuste en Pagos por separación No acumulable'],
                ['codigo' => '69', 'descripcion' => 'Ajuste en Jubilaciones, pensiones o haberes de retiro en parcialidades Exento'],
                ['codigo' => '70', 'descripcion' => 'Ajuste en Jubilaciones, pensiones o haberes de retiro en parcialidades Gravado'],
                ['codigo' => '71', 'descripcion' => 'Ajuste en Subsidio para el empleo (efectivamente entregado al trabajador)'],
                ['codigo' => '72', 'descripcion' => 'Ajuste en Ingresos en acciones o títulos valor que representan bienes Exento'],
                ['codigo' => '73', 'descripcion' => 'Ajuste en Ingresos en acciones o títulos valor que representan bienes Gravado'],
                ['codigo' => '74', 'descripcion' => 'Ajuste en Alimentación Exento'],
                ['codigo' => '75', 'descripcion' => 'Ajuste en Alimentación Gravado'],
                ['codigo' => '76', 'descripcion' => 'Ajuste en Habitación Exento'],
                ['codigo' => '77', 'descripcion' => 'Ajuste en Habitación Gravado'],
                ['codigo' => '78', 'descripcion' => 'Ajuste en Premios por asistencia'],
                ['codigo' => '79', 'descripcion' => 'Ajuste en Pagos distintos a los listados y que no deben considerarse como ingreso por sueldos, salarios o ingresos asimilados.'],
                ['codigo' => '80', 'descripcion' => 'Ajuste en Viáticos gravados'],
                ['codigo' => '81', 'descripcion' => 'Ajuste en Viáticos (entregados al trabajador)'],
                ['codigo' => '082', 'descripcion' => 'Ajuste en Fondo de ahorro Gravado'],
                ['codigo' => '083', 'descripcion' => 'Ajuste en Caja de ahorro Gravado'],
                ['codigo' => '084', 'descripcion' => 'Ajuste en Prima de Seguro de vida Gravado'],
                ['codigo' => '085', 'descripcion' => 'Ajuste en Seguro de Gastos Médicos Mayores Gravado'],
                ['codigo' => '086', 'descripcion' => 'Ajuste en Subsidios por incapacidad Gravado'],
                ['codigo' => '087', 'descripcion' => 'Ajuste en Becas para trabajadores y/o hijos Gravado'],
                ['codigo' => '088', 'descripcion' => 'Ajuste en Seguro de retiro Gravado'],
                ['codigo' => '089', 'descripcion' => 'Ajuste en Vales de despensa Gravado'],
                ['codigo' => '090', 'descripcion' => 'Ajuste en Vales de restaurante Gravado'],
                ['codigo' => '091', 'descripcion' => 'Ajuste en Vales de gasolina Gravado'],
                ['codigo' => '092', 'descripcion' => 'Ajuste en Vales de ropa Gravado'],
                ['codigo' => '093', 'descripcion' => 'Ajuste en Ayuda para renta Gravado'],
                ['codigo' => '094', 'descripcion' => 'Ajuste en Ayuda para artículos escolares Gravado'],
                ['codigo' => '095', 'descripcion' => 'Ajuste en Ayuda para anteojos Gravado'],
                ['codigo' => '096', 'descripcion' => 'Ajuste en Ayuda para transporte Gravado'],
                ['codigo' => '097', 'descripcion' => 'Ajuste en Ayuda para gastos de funeral Gravado'],
                ['codigo' => '098', 'descripcion' => 'Ajuste a ingresos asimilados a salarios gravados'],
                ['codigo' => '099', 'descripcion' => 'Ajuste a ingresos por sueldos y salarios gravados'],
                ['codigo' => '100', 'descripcion' => 'Ajuste en Viáticos exentos'],
                ['codigo' => '101', 'descripcion' => 'ISR Retenido de ejercicio anterior'],
                ['codigo' => '102', 'descripcion' => 'Ajuste a pagos por gratificaciones, primas, compensaciones, recompensas u otros a extrabajadores derivados de jubilación en parcialidades, gravados'],
                ['codigo' => '103', 'descripcion' => 'Ajuste a pagos que se realicen a extrabajadores que obtengan una jubilación en parcialidades derivados de la ejecución de una resolución judicial o de un laudo gravados'],
                ['codigo' => '104', 'descripcion' => 'Ajuste a pagos que se realicen a extrabajadores que obtengan una jubilación en parcialidades derivados de la ejecución de una resolución judicial o de un laudo exentos'],
                ['codigo' => '105', 'descripcion' => 'Ajuste a pagos que se realicen a extrabajadores que obtengan una jubilación en una sola exhibición derivados de la ejecución de una resolución judicial o de un laudo gravados'],
                ['codigo' => '106', 'descripcion' => 'Ajuste a pagos que se realicen a extrabajadores que obtengan una jubilación en una sola exhibición derivados de la ejecución de una resolución judicial o de un laudo exentos'],
                ['codigo' => '107', 'descripcion' => 'Ajuste al Subsidio Causado'],
            ],
            'OTRO_PAGO' => [
                ['codigo' => '1', 'descripcion' => 'Reintegro de ISR pagado en exceso (siempre que no haya sido enterado al SAT).'],
                ['codigo' => '2', 'descripcion' => 'Subsidio para el empleo (efectivamente entregado al trabajador).'],
                ['codigo' => '3', 'descripcion' => 'Viáticos (entregados al trabajador).'],
                ['codigo' => '4', 'descripcion' => 'Aplicación de saldo a favor por compensación anual.'],
                ['codigo' => '5', 'descripcion' => 'Reintegro de ISR retenido en exceso de ejercicio anterior (siempre que no haya sido enterado al SAT).'],
                ['codigo' => '6', 'descripcion' => 'Alimentos en bienes (Servicios de comedor y comida) Art 94 último párrafo LISR.'],
                ['codigo' => '7', 'descripcion' => 'ISR ajustado por subsidio.'],
                ['codigo' => '8', 'descripcion' => 'Subsidio efectivamente entregado que no correspondía (Aplica sólo cuando haya ajuste al cierre de mes en relación con el Apéndice 7 de la guía de llenado de nómina).'],
                ['codigo' => '9', 'descripcion' => 'Reembolso de descuentos efectuados para el crédito de vivienda.'],
                ['codigo' => '999', 'descripcion' => 'Pagos distintos a los listados y que no deben considerarse como ingreso por sueldos, salarios o ingresos asimilados.'],
            ],
            'PERCEPCION' => [
                ['codigo' => '1', 'descripcion' => 'Sueldos, Salarios  Rayas y Jornales'],
                ['codigo' => '2', 'descripcion' => 'Gratificación Anual (Aguinaldo)'],
                ['codigo' => '3', 'descripcion' => 'Participación de los Trabajadores en las Utilidades PTU'],
                ['codigo' => '4', 'descripcion' => 'Reembolso de Gastos Médicos Dentales y Hospitalarios'],
                ['codigo' => '5', 'descripcion' => 'Fondo de Ahorro'],
                ['codigo' => '6', 'descripcion' => 'Caja de ahorro'],
                ['codigo' => '9', 'descripcion' => 'Contribuciones a Cargo del Trabajador Pagadas por el Patrón'],
                ['codigo' => '10', 'descripcion' => 'Premios por puntualidad'],
                ['codigo' => '11', 'descripcion' => 'Prima de Seguro de vida'],
                ['codigo' => '12', 'descripcion' => 'Seguro de Gastos Médicos Mayores'],
                ['codigo' => '13', 'descripcion' => 'Cuotas Sindicales Pagadas por el Patrón'],
                ['codigo' => '14', 'descripcion' => 'Subsidios por incapacidad'],
                ['codigo' => '15', 'descripcion' => 'Becas para trabajadores y/o hijos'],
                ['codigo' => '19', 'descripcion' => 'Horas extra'],
                ['codigo' => '20', 'descripcion' => 'Prima dominical'],
                ['codigo' => '21', 'descripcion' => 'Prima vacacional'],
                ['codigo' => '22', 'descripcion' => 'Prima por antigüedad'],
                ['codigo' => '23', 'descripcion' => 'Pagos por separación'],
                ['codigo' => '24', 'descripcion' => 'Seguro de retiro'],
                ['codigo' => '25', 'descripcion' => 'Indemnizaciones'],
                ['codigo' => '26', 'descripcion' => 'Reembolso por funeral'],
                ['codigo' => '27', 'descripcion' => 'Cuotas de seguridad social pagadas por el patrón'],
                ['codigo' => '28', 'descripcion' => 'Comisiones'],
                ['codigo' => '29', 'descripcion' => 'Vales de despensa'],
                ['codigo' => '30', 'descripcion' => 'Vales de restaurante'],
                ['codigo' => '31', 'descripcion' => 'Vales de gasolina'],
                ['codigo' => '32', 'descripcion' => 'Vales de ropa'],
                ['codigo' => '33', 'descripcion' => 'Ayuda para renta'],
                ['codigo' => '34', 'descripcion' => 'Ayuda para artículos escolares'],
                ['codigo' => '35', 'descripcion' => 'Ayuda para anteojos'],
                ['codigo' => '36', 'descripcion' => 'Ayuda para transporte'],
                ['codigo' => '37', 'descripcion' => 'Ayuda para gastos de funeral'],
                ['codigo' => '38', 'descripcion' => 'Otros ingresos por salarios'],
                ['codigo' => '39', 'descripcion' => 'Jubilaciones, pensiones o haberes de retiro'],
                ['codigo' => '44', 'descripcion' => 'Jubilaciones, pensiones o haberes de retiro en parcialidades'],
                ['codigo' => '45', 'descripcion' => 'Ingresos en acciones o títulos valor que representan bienes'],
                ['codigo' => '046', 'descripcion' => 'Ingresos asimilados a salarios'],
                ['codigo' => '047', 'descripcion' => 'Alimentación diferentes a los establecidos en el Art 94 último párrafo LISR'],
                ['codigo' => '48', 'descripcion' => 'Habitación'],
                ['codigo' => '49', 'descripcion' => 'Premios por asistencia'],
                ['codigo' => '50', 'descripcion' => 'Viáticos'],
                ['codigo' => '51', 'descripcion' => 'Pagos por gratificaciones, primas, compensaciones, recompensas u otros a extrabajadores derivados de jubilación en parcialidades'],
                ['codigo' => '52', 'descripcion' => 'Pagos que se realicen a extrabajadores que obtengan una jubilación en parcialidades derivados de la ejecución de resoluciones judicial o de un laudo'],
                ['codigo' => '53', 'descripcion' => 'Pagos que se realicen a extrabajadores que obtengan una jubilación en una sola exhibición derivados de la ejecución de resoluciones judicial o de un laudo'],
            ],
        ];

        $teamIds = Team::query()->pluck('id');

        foreach ($teamIds as $teamId) {
            $defaultCuentaId = CatCuentas::query()
                ->where('team_id', $teamId)
                ->where('codigo', '50102000')
                ->value('id');
            foreach ($catalogos as $tipo => $items) {
                foreach ($items as $item) {
                    $codigoRaw = (string) $item['codigo'];
                    $codigoNormalized = $this->normalizeCodigo($codigoRaw);
                    $codigoLegacy = ltrim($codigoRaw, '0');
                    if ($codigoLegacy === '') {
                        $codigoLegacy = '0';
                    }

                    $record = NominaConceptoCuenta::query()
                        ->where('team_id', $teamId)
                        ->where('tipo', $tipo)
                        ->where('codigo_sat', $codigoNormalized)
                        ->first();

                    if (! $record && $codigoLegacy !== $codigoNormalized) {
                        $record = NominaConceptoCuenta::query()
                            ->where('team_id', $teamId)
                            ->where('tipo', $tipo)
                            ->where('codigo_sat', $codigoLegacy)
                            ->first();
                    }

                    if (! $record) {
                        $record = NominaConceptoCuenta::create([
                            'team_id' => $teamId,
                            'tipo' => $tipo,
                            'codigo_sat' => $codigoNormalized,
                            'descripcion' => $item['descripcion'],
                            'clave' => $codigoNormalized,
                            'cat_cuentas_id' => $defaultCuentaId,
                            'naturaleza' => 'D',
                            'activo' => true,
                        ]);
                    }

                    $needsUpdate = false;
                    if ($record->codigo_sat !== $codigoNormalized) {
                        $record->codigo_sat = $codigoNormalized;
                        $needsUpdate = true;
                    }
                    if ($record->descripcion !== $item['descripcion']) {
                        $record->descripcion = $item['descripcion'];
                        $needsUpdate = true;
                    }
                    if ($record->clave !== $codigoNormalized) {
                        $record->clave = $codigoNormalized;
                        $needsUpdate = true;
                    }
                    if ($record->cat_cuentas_id === null && $defaultCuentaId !== null) {
                        $record->cat_cuentas_id = $defaultCuentaId;
                        $needsUpdate = true;
                    }
                    if ($record->activo !== true) {
                        $record->activo = true;
                        $needsUpdate = true;
                    }
                    if ($record->naturaleza === null) {
                        $record->naturaleza = 'D';
                        $needsUpdate = true;
                    }

                    if ($needsUpdate) {
                        $record->save();
                    }
                }
            }
        }
    }
}
