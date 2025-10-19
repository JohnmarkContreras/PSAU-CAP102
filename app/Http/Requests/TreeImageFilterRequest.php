<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TreeImageFilterRequest extends FormRequest
{
    public function authorize()
    {
        return true; // adjust if needed
    }

    public function rules()
    {
        return [
            'code' => ['nullable', 'string', 'max:50'],
        ];
    }
}
