<?php

namespace App\Models\Concerns;

use App\Models\CustomFormValue;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasCustomFormValues
{
    public function customFormValues(): MorphMany
    {
        return $this->morphMany(CustomFormValue::class, 'entity');
    }
}
