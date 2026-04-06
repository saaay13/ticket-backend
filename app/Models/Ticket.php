<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $fillable = [
        'ticket_number',
        'title',
        'requester_id',
        'assigned_to_id',
        'category_id',
        'status',
        'closed_at',
        'details',
        'total_time_minutes',
    ];

    protected $casts = [
        'details' => 'array',
        'closed_at' => 'datetime',
    ];

    public static function booted()
    {
        static::creating(function (Ticket $ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = self::generateTicketNumber();
            }

            if (empty($ticket->details)) {
                $ticket->details = [
                    'description' => '',
                    'priority' => 'medium',
                    'department' => null,
                    'system' => null,
                    'source_ip' => null,
                    'resolution_time_minutes' => null,
                    'tags' => [],
                    'custom_fields' => [],
                ];
            }
        });
    }

    public static function generateTicketNumber(): string
    {
        $year = now()->format('Y');
        $prefix = "TK-{$year}";

        $last = self::where('ticket_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('ticket_number');

        $sequence = 1;
        if ($last) {
            $suffix = substr($last, strlen($prefix));
            $sequence = intval($suffix) + 1;
        }

        return $prefix . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(History::class);
    }
}
