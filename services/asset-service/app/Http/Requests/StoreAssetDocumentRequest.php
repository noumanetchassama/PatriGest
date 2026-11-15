<?php

namespace App\Http\Requests;

use App\Models\AssetDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission vérifiée par le middleware auth.patri:manage-documents
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:20480'], // 20 Mo
            'type' => ['nullable', 'string', Rule::in(AssetDocument::TYPES)],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
