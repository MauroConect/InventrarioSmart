<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionFiscal extends Model
{
    use HasFactory;

    protected $table = 'configuraciones_fiscales';

    protected $fillable = [
        'razon_social',
        'cuit_emisor',
        'condicion_iva',
        'punto_venta',
        'ambiente',
        'comprobante_tipo_default',
        'certificado_path',
        'clave_privada_path',
        'passphrase_certificado',
    ];
}
