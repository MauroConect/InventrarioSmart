<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionFiscal extends Model
{
    use \App\Models\Concerns\BelongsToTenant;
    use HasFactory;

    protected $hidden = ['passphrase_certificado', 'certificado_path', 'clave_privada_path'];
    protected $casts = ['passphrase_certificado' => 'encrypted'];

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
