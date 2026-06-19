<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SsoUser extends Model
{
    protected $fillable = [
        'sso_subject',
        'email',
        'full_name',
        'nim',
        'token_type',
        'sso_payload',
        'local_role_id',
        'last_jwt_token',
        'token_expires_at',
        'last_login_at',
    ];

    protected $casts = [
        'sso_payload'      => 'array',
        'token_expires_at' => 'datetime',
        'last_login_at'    => 'datetime',
    ];

    protected $hidden = ['last_jwt_token'];

    public function localRole(): BelongsTo
    {
        return $this->belongsTo(LocalRole::class);
    }

    public function hasRole(string $roleName): bool
    {
        return $this->localRole?->name === $roleName;
    }

    public function isAdmin(): bool  { return $this->hasRole('admin');  }
    public function isBidder(): bool { return $this->hasRole('bidder'); }
}