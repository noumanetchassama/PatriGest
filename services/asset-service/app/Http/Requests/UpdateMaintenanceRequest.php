<?php

namespace App\Http\Requests;

use App\Models\AssetMaintenance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', Rule::in(AssetMaintenance::TYPES)],
            'description' => ['sometimes', 'string', 'max:2000'],
            'scheduled_date' => ['nullable', 'date'],
            'realized_date' => ['nullable', 'date'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'provider' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
