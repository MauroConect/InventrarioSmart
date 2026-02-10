<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    use HasFactory;

    protected $table = 'cajas';

    protected $fillable = [
        'usuario_id',
        'nombre',
        'fecha_apertura',
        'fecha_cierre',
        'monto_apertura',
        'monto_cierre',
        'monto_esperado',
        'monto_real',
        'diferencia',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
        'monto_apertura' => 'decimal:2',
        'monto_cierre' => 'decimal:2',
        'monto_esperado' => 'decimal:2',
        'monto_real' => 'decimal:2',
        'diferencia' => 'decimal:2',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoCaja::class);
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }
}
