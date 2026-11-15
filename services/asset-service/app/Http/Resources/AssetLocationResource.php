<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_id' => $this->asset_id,
            'region' => $this->region,
            'prefecture' => $this->prefecture,
            'commune' => $this->commune,
            'adresse' => $this->adresse,
            'site' => $this->site,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'effective_from' => $this->effective_from,
            'effective_to' => $this->effective_to,
            'is_current' => $this->is_current,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
