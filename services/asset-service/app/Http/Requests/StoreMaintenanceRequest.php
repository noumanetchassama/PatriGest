<?php

namespace App\Http\Requests;

use App\Models\AssetMaintenance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission vérifiée par le middleware auth.patri:manage-maintenance
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(AssetMaintenance::TYPES)],
            'description' => ['required', 'string', 'max:2000'],
            'scheduled_date' => ['nullable', 'date'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'provider' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
