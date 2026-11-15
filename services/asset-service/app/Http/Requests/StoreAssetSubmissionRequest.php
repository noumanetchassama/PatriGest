<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission vérifiée par le middleware auth.patri:submit-assets
    }

    public function rules(): array
    {
        return [
            'motif' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
