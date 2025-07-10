<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Files extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'encrypted_filename',
        'mimetype',
        'belongs_to',
        'path',
        'storage_type',
        'filesize',
        'application_id',
        'department_id',
        'created_by',
        'updated_by',
        'deleted_note',
        'deleted_at',
        'deleted_by',
    ];

    // Relasi ke parent (self-relation)
    public function parent()
    {
        return $this->belongsTo(FileType::class, 'parent_id');
    }

    // Relasi ke children (self-relation)
    // public function children()
    // {
    //     return $this->hasMany(FileType::class, 'parent_id');
    // }

    // Relasi ke ApplicationFile
    public function applicationFiles()
    {
        return $this->hasMany(ApplicationFile::class, 'file_type_id');
    }
}
