<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class VentaAdjunto extends Model
{
    use \App\Models\Concerns\BelongsToTenant, \App\Models\Concerns\HasTenantRelations;
    use HasFactory;

    protected $hidden = ['ruta'];

    protected function tenantRelations(): array { return ['venta_id' => Venta::class]; }

    protected $table = 'venta_adjuntos';

    protected $fillable = [
        'venta_id',
        'ruta',
        'nombre_original',
        'mime',
        'size',
    ];

    protected $appends = ['url'];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function getUrlAttribute()
    {
        return route('api.ventas.adjuntos.download', ['id' => $this->venta_id, 'adjunto' => $this->id]);
    }
}

