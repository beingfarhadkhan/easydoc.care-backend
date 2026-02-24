<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Payment extends Model
{
    public $table = 'payments';
    protected $fillable = [
        'clinic_id',
        'account_id',
        'receipt_id',
        'amount',
        'payment_mode',
        'payment_date',
        'transaction_id',
        'remarks',
    ];

    protected $appends = ['patient_name'];
    protected $hidden = [
        'receipt'
    ];

    public function getPatientNameAttribute()
    {
        return $this->receipt?->appointment?->patient?->name;
    }

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }

}
