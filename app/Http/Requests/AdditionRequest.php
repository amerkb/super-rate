<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdditionRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        if ($this->isMethod('post')) {
            return [
                'name' => 'required|string',
                'active' => 'sometimes|boolean',
            ];
        } else {
            return [
                'name' => 'sometimes|string',
                'active' => 'sometimes|boolean',
            ];
        }
    }

    public function validationData(): array
    {
        $data = $this->all();

        if ($this->isMethod('post') && ! isset($data['active'])) {
            $data['active'] = true;
        }

        return $data;
    }
}
