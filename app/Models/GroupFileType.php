<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroupFileType extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'order',
        'delete_note',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function fileTypes()
    {
        return $this->hasMany(FileType::class, 'group_file_type_id');
    }
}
