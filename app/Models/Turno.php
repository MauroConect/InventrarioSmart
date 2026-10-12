<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasCustomFormValues;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Turno extends Model
{
    use BelongsToTenant, HasCustomFormValues;

    public const STATUSES = ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'];

    protected $fillable = [
        'cliente_id',
        'user_id',
        'nombre',
        'telefono',
        'email',
        'prestacion',
        'starts_at',
        'ends_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
