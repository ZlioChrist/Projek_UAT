<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExploreFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'facilities' => ['nullable', 'array'],
            'facilities.*' => ['integer', 'exists:facilities,id'],
            'open_now' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'string', 'in:nearest,latest'],
            'lat' => ['nullable', 'required_if:sort,nearest', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'required_if:sort,nearest', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lat.between' => 'Latitude must be between -90 and 90.',
            'lng.between' => 'Longitude must be between -180 and 180.',
            'lat.required_if' => 'Latitude is required when sorting by nearest.',
            'lng.required_if' => 'Longitude is required when sorting by nearest.',
        ];
    }
}
