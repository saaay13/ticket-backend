<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $casts = [
        'sla_config' => 'array',
        'active' => 'boolean',
    ];

    protected $fillable = [
        'name',
        'sla_config',
        'active',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}