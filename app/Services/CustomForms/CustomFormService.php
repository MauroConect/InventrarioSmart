<?php

namespace App\Services\CustomForms;

use App\Models\CustomFormField;
use App\Models\CustomFormValue;
use App\Services\Modules\TenantFeatureService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomFormService
{
    public function __construct(private readonly TenantFeatureService $features)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function formConfig(string $formKey): array
    {
        $config = config('custom_forms.forms.'.$formKey);
        if (! is_array($config)) {
            throw new NotFoundHttpException('Ese formulario no admite campos extra.');
        }

        $module = $config['module'] ?? null;
        if (is_string($module) && $module !== '' && ! $this->features->enabled($module)) {
            throw new NotFoundHttpException('El módulo de este formulario no está habilitado.');
        }

        return $config;
    }

    public function fields(string $formKey): Collection
    {
        $this->formConfig($formKey);

        return CustomFormField::query()
            ->where('form_key', $formKey)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array{label: string, type: string, required?: bool}  $payload
     */
    public function createField(string $formKey, array $payload): CustomFormField
    {
        $this->formConfig($formKey);

        $max = (int) config('custom_forms.max_fields_per_form', 25);
        if ($this->fields($formKey)->count() >= $max) {
            throw ValidationException::withMessages([
                'label' => "Este formulario admite como máximo {$max} campos extra.",
            ]);
        }

        $label = trim($payload['label']);
        $name = $this->uniqueName($formKey, Str::slug($label, '_') ?: 'campo');

        return CustomFormField::query()->create([
            'form_key' => $formKey,
            'name' => $name,
            'label' => $label,
            'type' => $payload['type'],
            'required' => (bool) ($payload['required'] ?? false),
            'sort_order' => (int) ($this->fields($formKey)->max('sort_order') ?? 0) + 10,
        ]);
    }

    public function findField(string $formKey, int $id): CustomFormField
    {
        $this->formConfig($formKey);

        $field = CustomFormField::query()
            ->where('form_key', $formKey)
            ->find($id);

        if (! $field) {
            throw new NotFoundHttpException('Ese campo no existe.');
        }

        return $field;
    }

    public function deleteField(string $formKey, CustomFormField $field): void
    {
        $this->formConfig($formKey);
        abort_unless($field->form_key === $formKey, 404);
        $field->delete();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function syncValues(Model $entity, string $formKey, array $payload): void
    {
        $config = $this->formConfig($formKey);
        abort_unless($entity instanceof $config['entity'], 422, 'El registro no corresponde a este formulario.');

        foreach ($this->fields($formKey) as $field) {
            $raw = $payload[(string) $field->id] ?? $payload[$field->name] ?? null;
            $value = $this->normalizeValue($field, $raw);

            if ($field->required && ($value === null || $value === '')) {
                throw ValidationException::withMessages([
                    'custom_fields' => "Completá el campo {$field->label}.",
                ]);
            }

            CustomFormValue::query()->updateOrCreate(
                [
                    'custom_form_field_id' => $field->id,
                    'entity_type' => $entity::class,
                    'entity_id' => $entity->getKey(),
                ],
                ['value' => $value]
            );
        }
    }

    /**
     * @param  iterable<int, Model>  $entities
     */
    public function attachToMany(iterable $entities, string $formKey): void
    {
        $entities = collect($entities);
        if ($entities->isEmpty()) {
            return;
        }

        $fields = $this->fields($formKey);
        $values = CustomFormValue::query()
            ->where('entity_type', $entities->first()::class)
            ->whereIn('entity_id', $entities->map->getKey()->all())
            ->get()
            ->groupBy('entity_id');

        foreach ($entities as $entity) {
            $entity->setAttribute('custom_fields', $this->present($fields, $values->get($entity->getKey(), collect())));
        }
    }

    public function attachTo(Model $entity, string $formKey): void
    {
        $this->attachToMany([$entity], $formKey);
    }

    /**
     * @return list<array{id: int, name: string, label: string, type: string, required: bool, value: mixed}>
     */
    private function present(Collection $fields, Collection $values): array
    {
        $byField = $values->keyBy('custom_form_field_id');

        return $fields->map(function (CustomFormField $field) use ($byField): array {
            $stored = $byField->get($field->id)?->value;

            return [
                'id' => $field->id,
                'name' => $field->name,
                'label' => $field->label,
                'type' => $field->type,
                'required' => $field->required,
                'value' => $this->castStored($field, $stored),
            ];
        })->values()->all();
    }

    private function uniqueName(string $formKey, string $base): string
    {
        $name = substr($base, 0, 70);
        $i = 2;
        while (CustomFormField::query()->where('form_key', $formKey)->where('name', $name)->exists()) {
            $name = substr($base, 0, 60).'_'.$i;
            $i++;
        }

        return $name;
    }

    private function normalizeValue(CustomFormField $field, mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return $field->type === 'checkbox' ? '0' : null;
        }

        return match ($field->type) {
            'number' => is_numeric($raw) ? (string) $raw : throw ValidationException::withMessages([
                'custom_fields' => "{$field->label} debe ser numérico.",
            ]),
            'checkbox' => filter_var($raw, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            'date' => (string) $raw,
            'textarea', 'text' => Str::limit(trim((string) $raw), 2000, ''),
            default => (string) $raw,
        };
    }

    private function castStored(CustomFormField $field, ?string $stored): mixed
    {
        if ($stored === null) {
            return $field->type === 'checkbox' ? false : '';
        }

        return match ($field->type) {
            'number' => $stored === '' ? '' : $stored + 0,
            'checkbox' => $stored === '1',
            default => $stored,
        };
    }
}
