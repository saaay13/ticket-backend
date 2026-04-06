<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $departmentId = $this->route('department') ? $this->route('department')->id : null;

        return [
            'name' => 'required|string|max:100|unique:departments,name,' . $departmentId,
            'description' => 'nullable|string',
            'active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del departamento es obligatorio.',
            'name.unique' => 'Ya existe un departamento con este nombre.',
            'active.boolean' => 'El estado debe ser verdadero o falso.',
        ];
    }
}
