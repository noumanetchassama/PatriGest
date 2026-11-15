<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetInventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_inventory_id' => $this->asset_inventory_id,
            'asset_id' => $this->asset_id,
            'asset_reference' => $this->whenLoaded('asset', fn () => $this->asset?->reference),
            'asset_designation' => $this->whenLoaded('asset', fn () => $this->asset?->designation),
            'status' => $this->status,
            'notes' => $this->notes,
            'checked_by' => $this->checked_by,
            'checked_at' => $this->checked_at?->toIso8601String(),
        ];
    }
}
