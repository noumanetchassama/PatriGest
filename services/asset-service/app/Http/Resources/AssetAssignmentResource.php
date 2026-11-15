<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_id' => $this->asset_id,
            'asset_reference' => $this->whenLoaded('asset', fn () => $this->asset?->reference),
            'asset_designation' => $this->whenLoaded('asset', fn () => $this->asset?->designation),
            'custodian_user_id' => $this->custodian_user_id,
            'requested_by' => $this->requested_by,
            'reviewed_by' => $this->reviewed_by,
            'status' => $this->status,
            'motif' => $this->motif,
            'decision_motif' => $this->decision_motif,
            'decided_at' => $this->decided_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
