<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HarvestStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // Accept codes from tree_code table (case-insensitive)
            'code' => [
                'required',
                function ($attribute, $value, $fail) {
                    $normalized = trim((string) $value);
                    $exists = \App\TreeCode::whereRaw('LOWER(code) = ?', [mb_strtolower($normalized)])->exists();
                    if (! $exists) {
                        $fail('The selected tree code is invalid.');
                    }
                },
            ],
            'harvest_date' => 'required|date',
            'harvest_weight_kg' => 'required|numeric|min:0',
            'quality' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ];
    }
}