<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'email_verified_at' => $this->email_verified_at,
            'last_login_at' => $this->last_login_at,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'permissions' => $this->whenLoaded('roles', fn () => $this->getAllPermissions()->pluck('name')->unique()->values()),
            'structures' => $this->whenLoaded('structures', fn () => $this->structures->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'type' => $s->type,
                'code' => $s->code,
                'is_primary' => (bool) $s->pivot->is_primary,
            ])),
            'created_at' => $this->created_at,
        ];
    }
}
