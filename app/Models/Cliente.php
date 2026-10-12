<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasCustomFormValues;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class Cliente extends Model
{
    use BelongsToTenant, HasCustomFormValues, HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'apellido',
        'dni',
        'cuit',
        'telefono',
        'email',
        'direccion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /** Validation queries do not apply Eloquent's global tenant scope. */
    public static function tenantExistsRule(): Exists
    {
        return Rule::exists('clientes', 'id')->where(function ($query): void {
            $context = app(TenantContext::class);

            if (! $context->hasTenant()) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->where('tenant_id', $context->id());
        });
    }

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
