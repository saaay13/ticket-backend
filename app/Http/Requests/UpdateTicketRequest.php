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
            'status' => 'sometimes|string|in:open,in_progress,pending,resolved,closed,deleted',
            'title' => 'sometimes|string|max:200',
            'requester_id' => 'sometimes|exists:users,id',
            'assigned_to_id' => 'sometimes|nullable|exists:users,id',
            'category_id' => 'sometimes|exists:categories,id',
            'details' => 'sometimes|array',
            'total_time_minutes' => 'sometimes|integer',
            'closed_at' => 'sometimes|nullable|date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'status.in' => 'The selected status is invalid.',
        ];
    }
}
