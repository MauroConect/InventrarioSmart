<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasTenantRelations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoCuentaCorriente extends Model
{
    use BelongsToTenant, HasFactory, HasTenantRelations;

    protected function tenantRelations(): array
    {
        return ['cuenta_corriente_id' => CuentaCorriente::class, 'venta_id' => Venta::class];
    }

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
