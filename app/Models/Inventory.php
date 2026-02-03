<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    public $table = 'inventories';
    protected $fillable = [
        'id',
        'account_id',
        'clinic_id',
        'product_name',
        'product_code',
        'barcode',
        'size',
        'mrp',
        'selling_price',
        'purchase_price',
        'stock',
        'expiry_date',
        'manufacture_date',
        'manufacturer',
        'image',
        'description',
        'side_effects',
        'disclaimer',
        'category',
        'product_type',
        'consume_type',
        'sizes',
        'content',
        'total_stock_available',
        'stock_details',
        'created_at',
        'updated_at'
    ];
}
