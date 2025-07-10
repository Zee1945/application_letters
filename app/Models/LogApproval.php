<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogApproval extends Model
{
    use HasFactory;
    protected $fillable = [
        'notes',
        'location_city',
        'action',
        'trans_type',
        'user_id',
        'application_id',
        'position_id',
        'department_id',
        'created_by',
        'updated_by',
        'deleted_note',
        'deleted_at',
        'deleted_by',
    ];
}
