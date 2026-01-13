<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProformaInvoiceProduct extends Model
{
    protected $fillable = [
        'proforma_invoice_id',
        'product_snapshot',
        'custom_price',
        'quantity',
    ];

    protected $casts = [
        'product_snapshot' => 'array',
        'custom_price' => 'decimal:2',
    ];

    public function proformaInvoice()
    {
        return $this->belongsTo(ProformaInvoice::class);
    }
}
