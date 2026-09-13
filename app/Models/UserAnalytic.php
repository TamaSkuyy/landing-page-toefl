<?php

namespace App\Models;

use App\Analytics\EventType;
use Illuminate\Database\Eloquent\Model;

/**
 * @property EventType $event_type
 * @property int|string $total
 */
class UserAnalytic extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'session_id', 'visitor_id', 'event_type', 'event_data', 'referral_source',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
        'ip_hash', 'user_agent', 'user_id', 'created_at', 'landing_source',
        'scroll_depth', 'section_id', 'cta_zone', 'cta_action', 'payment_status',
        'payment_amount',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => EventType::class,
            'event_data' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
