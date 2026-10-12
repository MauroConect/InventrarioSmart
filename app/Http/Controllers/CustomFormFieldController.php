<?php

namespace App\Http\Controllers;

use App\Services\CustomForms\CustomFormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomFormFieldController extends Controller
{
    public function __construct(private readonly CustomFormService $forms)
    {
    }

    public function index(string $form): JsonResponse
    {
        $config = $this->forms->formConfig($form);
        $this->authorizeForm($config['view_permission'] ?? 'admin');

        return response()->json([
            'form' => $form,
            'label' => $config['label'] ?? $form,
            'types' => config('custom_forms.types', []),
            'fields' => $this->forms->fields($form),
            'can_manage' => request()->user()?->hasPermission($config['manage_permission'] ?? 'admin') ?? false,
        ]);
    }

    public function store(Request $request, string $form): JsonResponse
    {
        $config = $this->forms->formConfig($form);
        $this->authorizeForm($config['manage_permission'] ?? 'admin');

        $validated = $request->validate([
            'label' => 'required|string|max:120',
            'type' => ['required', 'string', Rule::in(array_keys(config('custom_forms.types', [])))],
            'required' => 'nullable|boolean',
        ]);

        $field = $this->forms->createField($form, $validated);

        return response()->json($field, 201);
    }

    public function destroy(string $form, int $campo): JsonResponse
    {
        $config = $this->forms->formConfig($form);
        $this->authorizeForm($config['manage_permission'] ?? 'admin');
        $this->forms->deleteField($form, $this->forms->findField($form, $campo));

        return response()->json(['message' => 'Campo eliminado.']);
    }

    private function authorizeForm(string $permission): void
    {
        abort_unless(request()->user()?->hasPermission($permission), 403);
    }
}
