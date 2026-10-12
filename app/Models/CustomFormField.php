<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomFormField extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'form_key',
        'name',
        'label',
        'type',
        'required',
        'sort_order',
        'config',
    ];

    protected $casts = [
        'required' => 'boolean',
        'config' => 'array',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(CustomFormValue::class);
    }
}
