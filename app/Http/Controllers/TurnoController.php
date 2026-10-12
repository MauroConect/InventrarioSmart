<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Turno;
use App\Services\CustomForms\CustomFormService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TurnoController extends Controller
{
    public function __construct(private readonly CustomFormService $customForms)
    {
    }

    public function index(Request $request)
    {
        $day = Carbon::parse($request->input('date', now()->toDateString()))->startOfDay();

        $turnos = Turno::query()
            ->with('cliente:id,nombre,apellido,telefono')
            ->whereBetween('starts_at', [$day, $day->copy()->endOfDay()])
            ->orderBy('starts_at')
            ->get();

        $this->customForms->attachToMany($turnos, 'turnos');

        return response()->json(['data' => $turnos, 'date' => $day->toDateString()]);
    }

    public function store(Request $request)
    {
        $turno = Turno::create($this->validated($request) + [
            'user_id' => $request->user()->id,
            'status' => $request->input('status', 'scheduled'),
        ]);
        $this->customForms->syncValues($turno, 'turnos', $request->input('custom_fields', []));
        $this->customForms->attachTo($turno, 'turnos');

        return response()->json($turno->load('cliente'), 201);
    }

    public function update(Request $request, Turno $turno)
    {
        $turno->update($this->validated($request, $turno));
        if ($request->exists('custom_fields')) {
            $this->customForms->syncValues($turno, 'turnos', $request->input('custom_fields', []));
        }
        $this->customForms->attachTo($turno, 'turnos');

        return response()->json($turno->fresh()->load('cliente'));
    }

    public function destroy(Turno $turno)
    {
        $turno->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Turno cancelado.', 'turno' => $turno->fresh()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Turno $turno = null): array
    {
        $data = $request->validate([
            'cliente_id' => ['nullable', 'integer', Cliente::tenantExistsRule()],
            'nombre' => 'required|string|max:120',
            'telefono' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:120',
            'prestacion' => 'nullable|string|max:120',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'status' => ['nullable', Rule::in(Turno::STATUSES)],
            'notes' => 'nullable|string|max:1000',
            'custom_fields' => 'nullable|array',
        ]);
        unset($data['custom_fields']);

        $start = Carbon::parse($data['starts_at']);
        $data['starts_at'] = $start;
        $data['ends_at'] = isset($data['ends_at'])
            ? Carbon::parse($data['ends_at'])
            : $start->copy()->addMinutes(30);
        $data['status'] = $data['status'] ?? $turno?->status ?? 'scheduled';

        $overlap = Turno::query()
            ->where('status', '!=', 'cancelled')
            ->where('id', '!=', $turno?->getKey() ?? 0)
            ->where('starts_at', '<', $data['ends_at'])
            ->where('ends_at', '>', $data['starts_at'])
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'starts_at' => 'Ya hay un turno en ese horario.',
            ]);
        }

        return $data;
    }
}
