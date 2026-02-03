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
        'location',
        'receipt_template',
        'show_doctor_name',
        'show_doctor_sign',
        'additional_content',
    ];

    public function users()
{
    return $this->belongsToMany(User::class, 'assign_to_clinics', 'clinic_id', 'user_id');
}
}

