<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;

    protected $table = 'ventas';

    protected $fillable = [
        'caja_id',
        'cliente_id',
        'numero_factura',
        'fecha',
        'total',
        'descuento',
        'total_final',
        'tipo_pago',
        'monto_tarjeta',
        'monto_efectivo',
        'monto_transferencia',
        'cuotas',
        'monto_cuota',
        'recargo_cuotas',
        'estado',
        'estado_facturacion',
        'comprobante_tipo',
        'comprobante_numero',
        'cae',
        'cae_vencimiento',
        'afip_observaciones',
        'facturada_at',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'total' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total_final' => 'decimal:2',
            'monto_tarjeta' => 'decimal:2',
            'monto_efectivo' => 'decimal:2',
            'monto_transferencia' => 'decimal:2',
        'monto_cuota' => 'decimal:2',
        'recargo_cuotas' => 'decimal:2',
        'cae_vencimiento' => 'date',
        'facturada_at' => 'datetime',
    ];

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function items()
    {
        return $this->hasMany(ItemVenta::class);
    }

    public function adjuntos()
    {
        return $this->hasMany(VentaAdjunto::class);
    }
}

