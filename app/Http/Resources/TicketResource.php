<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'ticket_number' => $this->ticket_number,
            'title' => $this->title,
            'status' => $this->status,
            'details' => $this->details,
            'requester_id' => $this->requester_id ? (string) $this->requester_id : null,
            'assigned_to_id' => $this->assigned_to_id ? (string) $this->assigned_to_id : null,
            'category_id' => $this->category_id ? (string) $this->category_id : null,
            'requester' => $this->whenLoaded('requester', function () {
                return $this->requester ? new UserResource($this->requester) : null;
            }),
            'assigned_to' => $this->whenLoaded('assignedTo', function () {
                return $this->assignedTo ? new UserResource($this->assignedTo) : null;
            }),
            'category' => $this->whenLoaded('category', function () {
                return $this->category ? [
                    'id' => (string) $this->category->id,
                    'name' => $this->category->name,
                ] : null;
            }),
            'total_time' => $this->total_time_minutes,
            'created_at' => $this->created_at ? $this->created_at->format('c') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('c') : null,
        ];
    }
}
