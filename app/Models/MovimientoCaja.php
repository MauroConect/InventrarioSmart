<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasTenantRelations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoCaja extends Model
{
    use BelongsToTenant, HasFactory, HasTenantRelations;

    protected function tenantRelations(): array
    {
        return ['caja_id' => Caja::class];
    }

    protected $table = 'movimientos_caja';

    protected $fillable = [
        'caja_id',
        'tipo',
        'monto',
        'concepto',
        'observaciones',
        'usuario_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }
}
