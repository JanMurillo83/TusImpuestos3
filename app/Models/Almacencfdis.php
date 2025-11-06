<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Almacencfdi
 *
 * @property int $id
 * @property string|null $Serie
 * @property string|null $Folio
 * @property string|null $Version
 * @property string|null $Fecha
 * @property string|null $Moneda
 * @property string|null $TipoDeComprobante
 * @property string|null $MetodoPago
 * @property string|null $Emisor_Rfc
 * @property string|null $Emisor_Nombre
 * @property string|null $Emisor_RegimenFiscal
 * @property string|null $Receptor_Rfc
 * @property string|null $Receptor_Nombre
 * @property string|null $Receptor_RegimenFiscal
 * @property string|null $UUID
 * @property float|null $Total
 * @property float|null $SubTotal
 * @property float|null $TipoCambio
 * @property float|null $TotalImpuestosTrasladados
 * @property float|null $TotalImpuestosRetenidos
 * @property string|null $content
 * @property string $user_tax
 * @property string $used
 * @property string $xml_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @package App\Models
 */
class Almacencfdis extends Model
{
	protected $table = 'almacencfdis';

	protected $casts = [
		'Total' => 'float',
		'SubTotal' => 'float',
        'Descuento' => 'float',
		'TipoCambio' => 'float',
		'TotalImpuestosTrasladados' => 'float',
		'TotalImpuestosRetenidos' => 'float'
	];

	protected $fillable = [
		'Serie',
		'Folio',
		'Version',
		'Fecha',
		'Moneda',
		'TipoDeComprobante',
		'MetodoPago',
		'Emisor_Rfc',
		'Emisor_Nombre',
		'Emisor_RegimenFiscal',
		'Receptor_Rfc',
		'Receptor_Nombre',
		'Receptor_RegimenFiscal',
		'UUID',
		'Total',
		'SubTotal',
        'Descuento',
		'TipoCambio',
		'TotalImpuestosTrasladados',
		'TotalImpuestosRetenidos',
        'ejercicio',
        'periodo',
		'content',
		'user_tax',
		'used',
		'xml_type',
        'notas',
        'team_id',
        'comp_used',
        'archivoxml',
        'archivopdf'
	];
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
