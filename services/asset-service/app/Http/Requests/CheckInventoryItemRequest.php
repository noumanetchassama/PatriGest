<?php

namespace App\Http\Requests;

use App\Models\AssetInventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // permission vérifiée par le middleware auth.patri:manage-inventory
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in([
                AssetInventoryItem::STATUS_TROUVE,
                AssetInventoryItem::STATUS_ABSENT,
                AssetInventoryItem::STATUS_NON_CONCERNE,
            ])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
