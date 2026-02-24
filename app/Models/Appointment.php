<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $table = 'appointments';
    protected $fillable = [
        'clinic_id',
        'patient_id',
        'doctor_id',
        'mode',
        'appointment_date',
        'duration',
        'time_slot',
        'check_in_status',
        'type',
        'services'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
    
    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function clinic()
    {
        return $this->belongsTo(Clinic::class, 'clinic_id', 'id');
    }

    public function prescription()
    {
        return $this->hasOne(Prescription::class, 'appointment_id');
    }

     public function receipts()
    {
        return $this->hasMany(Receipt::class, 'appointment_id');
    }

    
}
