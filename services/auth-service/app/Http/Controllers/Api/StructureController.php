<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStructureRequest;
use App\Http\Resources\StructureResource;
use App\Http\Requests\UpdateStructureRequest;
use App\Models\Structure;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StructureController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $structures = Structure::with('parent:id,name,code')
            ->withCount('users')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = mb_strtolower($request->string('search')->toString());
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
            })
            ->orderBy('name')
            ->paginate(min((int) $request->input('per_page', 50), 200));

        return StructureResource::collection($structures);
    }

    public function store(StoreStructureRequest $request): JsonResponse
    {
        $structure = Structure::create($request->validated());

        AuditLogger::log('structure.created', $structure, null, $structure->only([
            'type', 'name', 'code', 'parent_id',
        ]));

        return response()->json(['data' => $structure], 201);
    }

    public function show(Structure $structure)
    {
        return response()->json([
            'data' => $structure->load(['parent', 'children', 'users']),
        ]);
    }

    public function update(UpdateStructureRequest $request, Structure $structure): JsonResponse
    {
        $old = $structure->only(['type', 'name', 'code', 'parent_id', 'is_active']);
        $structure->update($request->validated());

        AuditLogger::log('structure.updated', $structure, $old,
            $structure->fresh()->only(array_keys($old)));

        return response()->json(['data' => $structure->fresh()]);
    }

    public function destroy(Request $request, Structure $structure): JsonResponse
    {
        if ($structure->children()->exists() || $structure->users()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer une structure ayant des enfants ou des utilisateurs.',
            ], 422);
        }

        AuditLogger::log('structure.deleted', $structure, $structure->only(['type', 'name', 'code']));
        $structure->delete();

        return response()->json(['message' => 'Structure supprimée.']);
    }

    /**
     * Arborescence complète des structures.
     */
    public function tree()
    {
        $structures = Structure::with('children.children')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $structures]);
    }
}
