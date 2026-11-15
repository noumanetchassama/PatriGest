<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission vérifiée par le middleware auth.patri:approve-transfer
    }

    public function rules(): array
    {
        return [
            // Le rejet EXIGE un motif — traçabilité obligatoire
            'motif' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }
}
