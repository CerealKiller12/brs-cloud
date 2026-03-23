<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;

class Device extends Model
{
    use HasApiTokens;

    protected $fillable = [
        'tenant_id',
        'store_id',
        'device_id',
        'name',
        'platform',
        'device_type',
        'app_mode',
        'channel',
        'branch_name',
        'current_version',
        'ip_address',
        'metadata_json',
        'last_seen_at',
    ];

    protected $hidden = [
        'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
