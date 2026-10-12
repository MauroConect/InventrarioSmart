<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasTenantRelations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeudaCliente extends Model
{
    use BelongsToTenant, HasFactory, HasTenantRelations;

    protected function tenantRelations(): array
    {
        return ['cliente_id' => Cliente::class, 'venta_id' => Venta::class];
    }

    protected $table = 'deudas_clientes';

    protected $fillable = [
        'cliente_id',
        'venta_id',
        'monto_total',
        'monto_pagado',
        'monto_pendiente',
        'cuotas_originales',
        'cuotas_pagadas',
        'cuotas_restantes',
        'fecha_vencimiento',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'monto_total' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'monto_pendiente' => 'decimal:2',
        'fecha_vencimiento' => 'date',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }
}
