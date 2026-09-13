<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $fillable = ['session_id', 'visitor_id', 'name', 'email', 'phone', 'landing_source', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'extra', 'status'];

    protected function casts(): array
    {
        return ['extra' => 'array'];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
