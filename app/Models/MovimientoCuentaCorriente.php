<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoCuentaCorriente extends Model
{
    use HasFactory;

    protected $table = 'movimientos_cuenta_corriente';

    protected $fillable = [
        'cuenta_corriente_id',
        'tipo',
        'monto',
        'concepto',
        'observaciones',
        'venta_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function cuentaCorriente()
    {
        return $this->belongsTo(CuentaCorriente::class);
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }
}
