<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    protected $table = 'clinics';
    protected $fillable = [
        'account_id',
        'name',
        'address',
        'phone',
        'email',
        'logo_url',
        'location'
    ];

    public function users()
{
    return $this->belongsToMany(User::class, 'assign_to_clinics', 'clinic_id', 'user_id');
}
}

