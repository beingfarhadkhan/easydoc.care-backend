<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientToAccount extends Model
{
    protected $table = 'patient_to_accounts';
    protected $fillable = [
        'patient_id',
        'account_id',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}

