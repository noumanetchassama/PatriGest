<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::with(['roles', 'structures'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = mb_strtolower($request->string('search')->toString());
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(username) LIKE ?', ["%{$search}%"]);
                });
            })
            ->when($request->filled('role'), function ($query) use ($request) {
                $query->role($request->string('role')->toString());
            })
            ->when($request->filled('is_active'), function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->orderBy('name')
            ->paginate(min((int) $request->input('per_page', 15), 100));

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['roles', 'structures']);
        $data['password'] = $request->validated('password');

        $user = User::create($data);

        if ($request->filled('roles')) {
            $user->syncRoles($request->validated('roles'));
        }

        $this->syncStructures($user, $request->validated('structures', []));

        AuditLogger::log('user.created', $user, null, [
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')->all(),
        ]);

        return (new UserResource($user->load(['roles', 'structures'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user->load(['roles', 'structures']));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $old = $user->only(['name', 'username', 'email', 'is_active']);
        $data = $request->safe()->except(['roles', 'structures', 'password']);

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        $user->update($data);

        if ($request->has('roles')) {
            $user->syncRoles($request->validated('roles', []));
        }

        if ($request->has('structures')) {
            $this->syncStructures($user, $request->validated('structures', []));
        }

        AuditLogger::log('user.updated', $user, $old, $user->fresh()->only(array_keys($old)));

        return new UserResource($user->fresh()->load(['roles', 'structures']));
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.',
            ], 422);
        }

        AuditLogger::log('user.deleted', $user, $user->only(['name', 'email']));
        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé.']);
    }

    /**
     * Synchronise les structures de l'utilisateur.
     *
     * @param  array<int, array{id: int, is_primary?: bool}>  $structures
     */
    private function syncStructures(User $user, array $structures): void
    {
        $sync = [];
        $hasPrimary = false;

        foreach ($structures as $entry) {
            $isPrimary = (bool) ($entry['is_primary'] ?? false);
            if ($isPrimary) {
                $hasPrimary = true;
            }
            $sync[$entry['id']] = ['is_primary' => $isPrimary];
        }

        if (! empty($sync) && ! $hasPrimary) {
            $first = array_key_first($sync);
            $sync[$first]['is_primary'] = true;
        }

        $user->structures()->sync($sync);
    }
}
