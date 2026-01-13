<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = [
        'organization_name',
        'contact_person_name',
        'phone',
        'email',
        'address',
        'gst_number',
        'is_dealer',
        'active',
    ];
}
