<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
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
        return [
            'title' => 'sometimes|required|string|max:200',
            'requester_id' => 'sometimes|required|exists:users,id',
            'assigned_to_id' => 'nullable|exists:users,id',
            'category_id' => 'sometimes|required|exists:categories,id',
            'status' => 'sometimes|string|in:open,in_progress,pending,resolved,closed,deleted',
            'closed_at' => 'nullable|date',
            'details' => 'nullable|array',
            'total_time_minutes' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título del ticket es obligatorio.',
            'requester_id.required' => 'El solicitante es obligatorio.',
            'category_id.required' => 'La categoría es obligatoria.',
            'status.in' => 'El estado no es válido.',
        ];
    }
}
