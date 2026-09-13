<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = ['order_number', 'lead_id', 'session_id', 'visitor_id', 'landing_source', 'name', 'email', 'phone', 'amount', 'payment_method', 'duitku_reference', 'status', 'paid_at', 'callback_payload'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'callback_payload' => 'array', 'amount' => 'integer'];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
