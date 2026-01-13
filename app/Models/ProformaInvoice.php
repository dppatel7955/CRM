<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProformaInvoice extends Model
{
    protected $fillable = [
        'enquiry_id',
        'quotation_id',
        'organization_snapshot',
        'invoice_percentage',
        'po_date',
        'po_number',
        'shipping_address',
        'charges',
        'proforma_no',
        'revision_no',
        'custom_proforma_id',
    ];

    protected $casts = [
        'organization_snapshot' => 'array',
        'charges' => 'array',
        'po_date' => 'date',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function enquiry()
    {
        return $this->belongsTo(Enquiry::class);
    }

    public function products()
    {
        return $this->hasMany(ProformaInvoiceProduct::class);
    }
}
