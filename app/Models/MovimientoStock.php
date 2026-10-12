<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasTenantRelations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoStock extends Model
{
    use BelongsToTenant, HasFactory;

    use HasTenantRelations;

    protected function tenantRelations(): array
    {
        return ['producto_id' => Producto::class, 'usuario_id' => User::class];
    }

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
        'cantidad' => 'decimal:3',
        'cantidad_anterior' => 'decimal:3',
        'cantidad_actual' => 'decimal:3',
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
