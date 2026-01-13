<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationProduct extends Model
{
    protected $fillable = [
        'quotation_id',
        'product_snapshot',
        'custom_price',
        'quantity',
    ];

    protected $casts = [
        'product_snapshot' => 'array',
        'custom_price' => 'decimal:2',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }
}
