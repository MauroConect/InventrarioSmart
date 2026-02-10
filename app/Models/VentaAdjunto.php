<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class VentaAdjunto extends Model
{
    use HasFactory;

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
        return Storage::disk('public')->url($this->ruta);
    }
}

