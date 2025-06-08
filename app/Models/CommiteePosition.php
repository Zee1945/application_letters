<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommiteePosition extends Model
{
    protected $fillable = [
        'name',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
