<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function index(Request $request)
    {
        $tenantId = $this->context->id();
        $query = User::query()
            ->whereHas('memberships', fn ($query) => $query->where('tenant_id', $tenantId)->where('status', 'active'))
            ->with(['memberships' => fn ($query) => $query->where('tenant_id', $tenantId)])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => ['required', 'string', Rule::in($this->allowedRoles())],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => strtolower(trim($validated['role'])),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->tenants()->attach($this->context->id(), [
            'role' => strtolower(trim($validated['role'])),
            'status' => 'active',
        ]);

        return response()->json($user->load('memberships'), 201);
    }

    public function update(Request $request, User $usuario)
    {
        $membership = $this->membershipOrFail($usuario);
        $input = $request->all();
        if (array_key_exists('password', $input) && $input['password'] === '') {
            unset($input['password']);
        }

        $validated = validator($input, [
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'password' => 'sometimes|required|string|min:8',
            'role' => ['sometimes', 'required', 'string', Rule::in($this->allowedRoles())],
        ])->validate();

        if (isset($validated['name'])) {
            $usuario->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $usuario->email = $validated['email'];
        }
        if (! empty($validated['password'] ?? null)) {
            $usuario->password = $validated['password'];
        }
        if (isset($validated['role'])) {
            $newRole = strtolower(trim($validated['role']));
            abort_if($membership->role === User::ROLE_OWNER, 403, 'El propietario no puede cambiarse desde esta pantalla.');
            if (! $this->canDemoteAdmin($usuario, $newRole)) {
                return response()->json([
                    'message' => 'Debe existir al menos otro usuario administrador antes de cambiar este rol.',
                ], 400);
            }
            $membership->role = $newRole;
            $membership->save();
        }

        $usuario->save();

        return response()->json($usuario);
    }

    public function destroy(Request $request, User $usuario)
    {
        $membership = $this->membershipOrFail($usuario);
        abort_if($membership->role === User::ROLE_OWNER, 403, 'El propietario no puede eliminarse del comercio.');
        if ($usuario->id === $request->user()->id) {
            return response()->json([
                'message' => 'No puede eliminar su propio usuario.',
            ], 400);
        }

        if ($usuario->isAdmin() && $this->adminUsersCount() <= 1) {
            return response()->json([
                'message' => 'Debe existir al menos un usuario administrador.',
            ], 400);
        }

        $membership->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente.']);
    }

    /**
     * @return array<int, string>
     */
    private function allowedRoles(): array
    {
        $extra = config('permissions.vendedor_role_names', []);
        $extra = is_array($extra) ? $extra : [];

        $roles = array_merge([User::ROLE_ADMIN, User::ROLE_VENDEDOR, 'stock', 'finanzas', 'auditor'], $extra);

        return array_values(array_unique(array_map(
            fn ($r) => strtolower(trim((string) $r)),
            $roles
        )));
    }

    private function adminUsersCount(): int
    {
        return \App\Models\TenantUser::query()
            ->where('tenant_id', $this->context->id())
            ->where('status', 'active')
            ->whereIn('role', [User::ROLE_OWNER, User::ROLE_ADMIN])
            ->count();
    }

    private function canDemoteAdmin(User $target, string $newRole): bool
    {
        if (! $target->isAdmin()) {
            return true;
        }
        if ($newRole === User::ROLE_ADMIN) {
            return true;
        }

        return $this->adminUsersCount() > 1;
    }

    private function membershipOrFail(User $user): \App\Models\TenantUser
    {
        $membership = $user->memberships()
            ->where('tenant_id', $this->context->id())
            ->where('status', 'active')
            ->first();

        abort_unless($membership, 404);

        return $membership;
    }
}
