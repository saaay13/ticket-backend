<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email|unique:users,email,' . $userId,
            'password' => 'nullable|string|min:8',
            'role' => 'sometimes|string|max:20',
            'department_id' => 'sometimes|exists:departments,id',
            'active' => 'sometimes|boolean',
            'metadata' => 'nullable|array',
        ];
    }
}
