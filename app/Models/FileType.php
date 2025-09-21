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
        'is_upload',
        'order',
        'created_by',
        'updated_by',
        'deleted_note',
        'deleted_at',
        'deleted_by',
    ];


    public function parent(){
        return $this->belongsTo(FileType::class,'parent_id','id');
    }


}
