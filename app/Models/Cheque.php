<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Cheque extends Model
{
    use HasFactory;

    protected $table = 'cheques';

    protected $fillable = [
        'numero_cheque',
        'banco',
        'fecha_ingreso',
        'fecha_vencimiento',
        'monto',
        'tipo',
        'estado',
        'cliente_id',
        'proveedor_id',
        'caja_id',
        'fecha_cobro',
        'observaciones',
        'usuario_id',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_cobro' => 'date',
        'monto' => 'decimal:2',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    // Scope para cheques próximos a vencer
    public function scopeProximosAVencer($query, $dias = 30)
    {
        $fecha = Carbon::now()->addDays($dias);
        return $query->where('estado', 'pendiente')
            ->where('fecha_vencimiento', '<=', $fecha)
            ->where('fecha_vencimiento', '>=', Carbon::now());
    }

    // Scope para cheques vencidos
    public function scopeVencidos($query)
    {
        return $query->where('estado', 'pendiente')
            ->where('fecha_vencimiento', '<', Carbon::now());
    }

    // Scope para cheques por fecha
    public function scopePorFecha($query, $fecha)
    {
        return $query->whereDate('fecha_vencimiento', $fecha);
    }

    // Scope para cheques por mes
    public function scopePorMes($query, $mes, $ano)
    {
        return $query->whereYear('fecha_vencimiento', $ano)
            ->whereMonth('fecha_vencimiento', $mes);
    }

    // Método para verificar si está próximo a vencer
    public function estaProximoAVencer($dias = 30)
    {
        $hoy = Carbon::now();
        $vencimiento = Carbon::parse($this->fecha_vencimiento);
        $diferencia = $hoy->diffInDays($vencimiento, false);
        
        return $this->estado === 'pendiente' && 
               $diferencia >= 0 && 
               $diferencia <= $dias;
    }

    // Método para verificar si está vencido
    public function estaVencido()
    {
        return $this->estado === 'pendiente' && 
               Carbon::parse($this->fecha_vencimiento)->isPast();
    }

    // Método para obtener días hasta vencimiento
    public function diasHastaVencimiento()
    {
        $hoy = Carbon::now();
        $vencimiento = Carbon::parse($this->fecha_vencimiento);
        return $hoy->diffInDays($vencimiento, false);
    }
}
