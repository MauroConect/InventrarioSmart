<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'apellido',
        'dni',
        'telefono',
        'email',
        'direccion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function cuentaCorriente()
    {
        return $this->hasOne(CuentaCorriente::class);
    }

    public function deudas()
    {
        return $this->hasMany(DeudaCliente::class);
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }
}
