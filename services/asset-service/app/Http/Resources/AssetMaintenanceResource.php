<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetMaintenanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_id' => $this->asset_id,
            'asset_reference' => $this->whenLoaded('asset', fn () => $this->asset?->reference),
            'type' => $this->type,
            'description' => $this->description,
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'realized_date' => $this->realized_date?->toDateString(),
            'cost' => $this->cost,
            'status' => $this->status,
            'provider' => $this->provider,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
