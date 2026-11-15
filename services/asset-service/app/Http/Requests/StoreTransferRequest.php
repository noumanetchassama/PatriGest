<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission vérifiée par le middleware auth.patri:create-transfer
    }

    public function rules(): array
    {
        return [
            'asset_id' => ['required', 'integer', 'min:1', 'exists:assets,id'],
            'to_direction_id' => ['nullable', 'integer', 'min:1'],
            'to_service_id' => ['nullable', 'integer', 'min:1'],
            'to_custodian_user_id' => ['nullable', 'integer', 'min:1'],
            'motif' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        // Au moins une cible de mutation est obligatoire
        $validator->after(function (Validator $v) {
            if (! $this->filled('to_direction_id')
                && ! $this->filled('to_service_id')
                && ! $this->filled('to_custodian_user_id')) {
                $v->errors()->add('to_custodian_user_id',
                    'Indiquez au moins une cible : direction, service ou dépositaire.');
            }
        });
    }
}
