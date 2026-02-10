<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoStock extends Model
{
    use HasFactory;

    protected $table = 'movimientos_stock';

    protected $fillable = [
        'producto_id',
        'tipo',
        'cantidad',
        'cantidad_anterior',
        'cantidad_actual',
        'motivo',
        'usuario_id',
        'observaciones',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'cantidad_anterior' => 'integer',
        'cantidad_actual' => 'integer',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
}
