<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTreeStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'code' => 'required|string|max:255',
            'height' => 'nullable|numeric',
            'status' => 'required|in:alive,dead',
            'reason' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ];
    }
}
