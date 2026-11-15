<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetIncidentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_id' => $this->asset_id,
            'asset_reference' => $this->whenLoaded('asset', fn () => $this->asset?->reference),
            'type' => $this->type,
            'description' => $this->description,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'severity' => $this->severity,
            'status' => $this->status,
            'estimated_cost' => $this->estimated_cost,
            'resolution_notes' => $this->resolution_notes,
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'reported_by' => $this->reported_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
