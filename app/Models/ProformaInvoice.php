<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProformaInvoice extends Model
{
    protected $fillable = [
        'quotation_id',
        'organization_snapshot',
        'invoice_percentage',
        'po_date',
        'po_number',
        'shipping_address',
        'charges',
    ];

    protected $casts = [
        'organization_snapshot' => 'array',
        'charges' => 'array',
        'po_date' => 'date',
        'invoice_percentage' => 'decimal:2',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function products()
    {
        return $this->hasMany(ProformaInvoiceProduct::class);
    }
}
