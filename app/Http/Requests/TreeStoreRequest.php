<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TreeStoreRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'code' => 'required|string|max:50|unique:trees,code',
            'type' => 'required|in:sweet,sour,semi_sweet',
            'age' => 'required|integer|min:0',
            'height' => 'required|numeric|min:0',
            'stem_diameter' => 'required|numeric|min:0',
            'canopy_diameter' => 'required|numeric|min:0',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'code' => strtoupper($this->code),
        ]);
    }
}