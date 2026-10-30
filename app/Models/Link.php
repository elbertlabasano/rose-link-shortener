<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    protected $fillable = ['title', 'code', 'url', 'url_hash', 'is_active', 'expires_at'];

    protected $hidden = ['url_hash'];

    protected $appends = ['short_url'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'is_active' => 'boolean', 'expires_at' => 'datetime', 'last_clicked_at' => 'datetime', 'clicks' => 'integer'];
    }

    public function getShortUrlAttribute(): string
    {
        return rtrim(config('shortener.url'), '/').'/'.$this->code;
    }

    public function getStatusAttribute(): string
    {
        return ! $this->is_active ? 'Disabled' : ($this->expires_at?->isPast() ? 'Expired' : 'Active');
    }
}
