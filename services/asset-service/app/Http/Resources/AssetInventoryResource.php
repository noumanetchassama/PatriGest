<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetInventoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'items_count' => $this->whenCounted('items'),
            'pending_items_count' => $this->when(
                $this->relationLoaded('items'),
                fn () => $this->items->where('status', 'en_attente')->count()
            ),
            'started_by' => $this->started_by,
            'closed_by' => $this->closed_by,
            'closed_at' => $this->closed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
