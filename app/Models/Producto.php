<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasCustomFormValues;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use BelongsToTenant, HasCustomFormValues, HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'precio_compra',
        'precio_venta',
        'stock_minimo',
        'stock_actual',
        'categoria_id',
        'proveedor_id',
        'activo',
        'tipo_venta',
        'unidad_medida',
    ];

    protected $casts = [
        'precio_compra' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'stock_minimo' => 'integer',
        'stock_actual' => 'decimal:3',
        'activo' => 'boolean',
    ];

    public function esPesable(): bool
    {
        return $this->tipo_venta === 'peso';
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function movimientosStock()
    {
        return $this->hasMany(MovimientoStock::class);
    }
}
