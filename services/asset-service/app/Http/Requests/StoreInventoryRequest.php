<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission vérifiée par le middleware auth.patri:manage-inventory
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            // Biens à inventorier dès la création (optionnel, ajouts possibles ensuite)
            'asset_ids' => ['nullable', 'array'],
            'asset_ids.*' => ['integer', 'exists:assets,id'],
        ];
    }
}
