<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activa',
        'descuento_porcentaje',
    ];

    protected $casts = [
        'activa' => 'boolean',
        'descuento_porcentaje' => 'decimal:2',
    ];

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
