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
            'code' => 'required|exists:trees,code',
            'harvest_date' => 'required|date',
            'harvest_weight_kg' => 'required|numeric|min:0',
            'quality' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ];
    }
}