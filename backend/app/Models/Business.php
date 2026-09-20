<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'timezone',
        'currency'
    ];
    public function members(): HasMany
    {
        return $this->hasMany(BusinessMember::class);
    }
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
    public function workingHours(): HasMany
    {
        return $this->hasMany(BusinessWorkingHour::class);
    }
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
 