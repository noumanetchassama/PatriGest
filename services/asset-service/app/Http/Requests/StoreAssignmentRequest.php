<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission vérifiée par le middleware auth.patri:create-assignments
    }

    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', 'min:1', 'exists:assets,id'],
            'custodian_user_id' => ['required', 'integer', 'min:1'],
            'motif' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
