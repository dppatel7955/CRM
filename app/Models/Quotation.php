<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $fillable = [
        'quotation_no',
        'revision_no',
        'custom_quotation_id',
        'enquiry_id',
        'organization_snapshot',
        'terms_and_conditions',
        'valid_till',
        'status',
    ];

    protected $casts = [
        'organization_snapshot' => 'array',
        'valid_till' => 'date',
    ];

    public function enquiry()
    {
        return $this->belongsTo(Enquiry::class);
    }

    public function products()
    {
        return $this->hasMany(QuotationProduct::class);
    }

    public function proformaInvoices()
    {
        return $this->hasMany(ProformaInvoice::class);
    }
}
