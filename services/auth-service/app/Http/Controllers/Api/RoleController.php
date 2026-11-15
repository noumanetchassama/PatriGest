<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::with('permissions:id,name')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = mb_strtolower($request->string('search')->toString());
                $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $role->permissions->pluck('name'),
                'users_count' => $role->users()->count(),
            ]);

        return response()->json(['data' => $roles]);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create(['name' => $request->validated('name'), 'guard_name' => 'web']);
        $role->syncPermissions($request->validated('permissions', []));

        AuditLogger::log('role.created', $role, null, [
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->all(),
        ]);

        return response()->json([
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ],
        ], 201);
    }

    public function update(StoreRoleRequest $request, Role $role): JsonResponse
    {
        $old = ['name' => $role->name, 'permissions' => $role->permissions->pluck('name')->all()];

        $role->update(['name' => $request->validated('name')]);
        $role->syncPermissions($request->validated('permissions', []));

        AuditLogger::log('role.updated', $role, $old, [
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->all(),
        ]);

        return response()->json([
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ],
        ]);
    }

    public function destroy(Request $request, Role $role): JsonResponse
    {
        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer un rôle attribué à des utilisateurs.',
            ], 422);
        }

        AuditLogger::log('role.deleted', $role, ['name' => $role->name]);
        $role->delete();

        return response()->json(['message' => 'Rôle supprimé.']);
    }
}
