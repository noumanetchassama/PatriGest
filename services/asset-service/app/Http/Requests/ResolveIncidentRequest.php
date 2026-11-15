<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResolveIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission vérifiée par le middleware auth.patri:manage-incidents
    }

    public function rules(): array
    {
        return [
            'resolution_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
