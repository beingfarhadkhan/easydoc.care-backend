<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EasydocBillingPayment extends Model
{
    protected $table = 'easydoc_billing_payments';
    protected $fillable = [
        'receipt_id',
        'user_id',
        'razorpay_order_id',
        'rozarpay_payment_id',
        'amount',
        'status',
        'captured'
    ];
}
