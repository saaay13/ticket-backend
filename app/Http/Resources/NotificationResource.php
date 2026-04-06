<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'ticket_id' => $this->ticket_id,
            'type' => $this->type,
            'title' => $this->title,
            'content' => $this->content,
            'read' => (boolean) $this->read,
            'metadata' => $this->metadata,
            'ticket' => new TicketResource($this->whenLoaded('ticket')),
            'created_at' => $this->created_at,
        ];
    }
}
