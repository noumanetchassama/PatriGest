<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'qr_token' => $this->qr_token,
            'type' => $this->type,
            'designation' => $this->designation,
            'description' => $this->description,
            'category' => $this->category,
            'acquisition_date' => $this->acquisition_date?->toDateString(),
            'origin' => $this->origin,
            'value' => $this->value,
            'value_type' => $this->value_type,
            'status' => $this->status,
            'etat' => $this->etat,
            'direction_id' => $this->direction_id,
            'service_id' => $this->service_id,
            'custodian_user_id' => $this->custodian_user_id,
            'details' => $this->when(
                $this->relationLoaded('realEstateDetail')
                || $this->relationLoaded('movableDetail')
                || $this->relationLoaded('vehicleDetail'),
                fn () => $this->details()
            ),
            'current_location' => $this->whenLoaded('currentLocation'),
            'locations_count' => $this->whenCounted('locations'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
