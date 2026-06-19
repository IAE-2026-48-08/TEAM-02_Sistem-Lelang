<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\SsoUser;

class LocalRole extends Model
{
    protected $fillable = ['name', 'display_name', 'description'];

    public function ssoUsers(): HasMany
    {
        return $this->hasMany(SsoUser::class);
    }

    public function isAdmin(): bool
    {
        return $this->name === 'admin';
    }
}