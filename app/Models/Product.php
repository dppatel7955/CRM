<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'product_name',
        'model_name',
        'hsn_code',
        'price',
        'dealer_price',
        'purchase_price',
        'max_discount',
        'short_description',
        'description',
        'attributes',
        'active',
    ];

    protected $casts = [
        'attributes' => 'array',
        'active' => 'boolean',
        'price' => 'decimal:2',
        'dealer_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'max_discount' => 'decimal:2',
    ];
}
