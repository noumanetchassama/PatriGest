<?php

namespace App\Http\Requests;

use App\Models\Structure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-structures') ?? false;
    }

    public function rules(): array
    {
        $structureId = $this->route('structure')?->id;

        return [
            'parent_id' => ['nullable', 'integer', Rule::exists('structures', 'id'), Rule::notIn([$structureId])],
            'type' => ['sometimes', 'string', Rule::in(Structure::TYPES)],
            'name' => ['sometimes', 'string', 'max:190'],
            'code' => ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique('structures', 'code')->ignore($structureId)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
