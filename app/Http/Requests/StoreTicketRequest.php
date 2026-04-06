<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Permitimos la validación por ahora
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:200',
            'requester_id' => 'required|exists:users,id',
            'assigned_to_id' => 'nullable|exists:users,id',
            'category_id' => 'required|exists:categories,id',
            'status' => 'sometimes|string|in:open,in_progress,pending,resolved,closed',
            'closed_at' => 'nullable|date',
            'details' => 'nullable|array',
            'total_time_minutes' => 'nullable|integer',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'El título del ticket es obligatorio.',
            'requester_id.required' => 'El usuario solicitante es obligatorio.',
            'requester_id.exists' => 'El usuario seleccionado no es válido.',
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'status.in' => 'El estado seleccionado no es válido.',
        ];
    }
}
