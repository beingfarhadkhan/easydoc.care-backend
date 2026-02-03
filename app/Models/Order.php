<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'order_no',
        'clinic_id',
        'patient_id',
        'appointment_id',
        'address',
        'status',
        'particulars',
        'payment_mode',
        'adv_payment_mode',
        'advance_amount',
        'total_amount',
        'balance_amount',
        'pdf_url',
        'remarks'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
