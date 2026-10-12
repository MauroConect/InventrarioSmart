<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomFormValue extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'custom_form_field_id',
        'entity_type',
        'entity_id',
        'value',
    ];

    public function field(): BelongsTo
    {
        return $this->belongsTo(CustomFormField::class, 'custom_form_field_id');
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }
}
