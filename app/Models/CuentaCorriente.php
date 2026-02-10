<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuentaCorriente extends Model
{
    use HasFactory;

    protected $table = 'cuentas_corrientes';

    protected $fillable = [
        'cliente_id',
        'saldo',
        'limite_credito',
        'activa',
    ];

    protected $casts = [
        'saldo' => 'decimal:2',
        'limite_credito' => 'decimal:2',
        'activa' => 'boolean',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoCuentaCorriente::class);
    }
}
