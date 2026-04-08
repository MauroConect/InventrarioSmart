<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->orderBy('name');

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

        return response()->json($user, 201);
    }

    public function update(Request $request, User $usuario)
    {
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
            if (! $this->canDemoteAdmin($usuario, $newRole)) {
                return response()->json([
                    'message' => 'Debe existir al menos otro usuario administrador antes de cambiar este rol.',
                ], 400);
            }
            $usuario->role = $newRole;
        }

        $usuario->save();

        return response()->json($usuario);
    }

    public function destroy(Request $request, User $usuario)
    {
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

        $usuario->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente.']);
    }

    /**
     * @return array<int, string>
     */
    private function allowedRoles(): array
    {
        $extra = config('permissions.vendedor_role_names', []);
        $extra = is_array($extra) ? $extra : [];

        $roles = array_merge([User::ROLE_ADMIN, User::ROLE_VENDEDOR], $extra);

        return array_values(array_unique(array_map(
            fn ($r) => strtolower(trim((string) $r)),
            $roles
        )));
    }

    private function adminUsersCount(): int
    {
        return User::whereRaw('LOWER(TRIM(role)) = ?', [User::ROLE_ADMIN])->count();
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
}
