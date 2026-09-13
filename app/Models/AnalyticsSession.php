<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsSession extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id', 'visitor_id', 'landing_source', 'referral_source', 'device_type',
        'browser', 'os', 'duration_seconds', 'max_scroll_depth', 'is_engaged',
        'is_bounce', 'started_at', 'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'is_engaged' => 'boolean',
            'is_bounce' => 'boolean',
            'started_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }
}
