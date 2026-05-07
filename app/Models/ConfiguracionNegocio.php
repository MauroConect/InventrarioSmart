<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionNegocio extends Model
{
    use HasFactory;

    protected $table = 'configuraciones_negocio';

    protected $fillable = [
        'nombre_negocio',
        'slogan',
        'logo_url',
        'color_primario',
        'color_fondo',
        'color_texto',
        'color_tarjeta',
        'color_header',
        'color_chip',
        'color_precio',
        'color_categoria',
        'descripcion_corta',
        'moneda',
        'telefono_whatsapp',
        'instagram_url',
        'direccion',
        'horario_atencion',
        'mensaje_bienvenida',
        'mostrar_precios',
        'mostrar_descripciones',
    ];
}
