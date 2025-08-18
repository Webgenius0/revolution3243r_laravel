<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChallengeRequest extends FormRequest
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
            'title'           => 'required|string|max:255',
            'challenge_type'  => 'required|string|max:255',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after_or_equal:start_date',
            'start_location'  => 'nullable|string|max:255',
            'start_latitude'  => 'nullable|numeric',
            'start_longitude' => 'nullable|numeric',
            'end_location'    => 'nullable|string|max:255',
            'end_latitude'    => 'nullable|numeric',
            'end_longitude'   => 'nullable|numeric',
            'description'     => 'nullable|string',

            // requirements (array of strings)
            'requirements'    => 'nullable|array',
            'requirements.*'  => 'string|max:255',

            // images (array of files)
            'images'          => 'nullable|array',
            'images.*'        => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Challenge title is required.',
            'end_date.after_or_equal' => 'End date must be after or equal to start date.',
            'images.*.image' => 'Each file must be an image.',
        ];
    }
}
