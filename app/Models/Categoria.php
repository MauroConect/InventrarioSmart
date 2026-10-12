<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use BelongsToTenant, HasFactory;

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
