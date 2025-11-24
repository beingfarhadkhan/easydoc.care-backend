<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $table = 'patients';
    protected $fillable = [
        'clinic_id',
        'uhid',
        'name',
        'email',
        'phone',
        'gender',
        'dob',
        'age',
        'ref_doctor_name',
        'ref_doctor_phone',
        'alt_phone',
        'city',
        'pin',
        'blood_group',
        'marital_status',
        'occupation',
        'guardian_name',
        'guardian_relation',
        'patient_language',
    ];


    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'patient_to_accounts');
    }
}
