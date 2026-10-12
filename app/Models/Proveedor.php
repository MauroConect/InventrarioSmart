<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'nombre',
        'razon_social',
        'cuit',
        'telefono',
        'email',
        'direccion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
