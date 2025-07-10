<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileType extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'code',
        'mimetype',
        'trans_type',
        'signed_role_id',
        'parent_id',
        'created_by',
        'updated_by',
        'deleted_note',
        'deleted_at',
        'deleted_by',
    ];


}
