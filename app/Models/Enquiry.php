<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    protected $fillable = [
        'organization_id',
        'created_by',
        'assigned_to',
        'subject',
        'message',
        'products',
        'order_status',
        'enquiry_source',
        'active',
        'follow_up_date',
        'follow_up_notes',
    ];

    protected $casts = [
        'active' => 'boolean',
        'follow_up_date' => 'date',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
