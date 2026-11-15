<?php

namespace App\Http\Requests;

use App\Models\AssetIncident;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission vérifiée par le middleware auth.patri:manage-incidents
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(AssetIncident::TYPES)],
            'description' => ['required', 'string', 'max:5000'],
            'occurred_at' => ['nullable', 'date'],
            'severity' => ['nullable', 'string', Rule::in(AssetIncident::SEVERITIES)],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
