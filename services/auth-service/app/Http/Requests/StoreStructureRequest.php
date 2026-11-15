<?php

namespace App\Http\Requests;

use App\Models\Structure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-structures') ?? false;
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', Rule::exists('structures', 'id')],
            'type' => ['required', 'string', Rule::in(Structure::TYPES)],
            'name' => ['required', 'string', 'max:190'],
            'code' => ['nullable', 'string', 'max:50', 'alpha_dash', 'unique:structures,code'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
