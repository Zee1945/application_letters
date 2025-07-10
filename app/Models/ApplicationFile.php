<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationFile extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'file_id',
        'file_type_id',
        'status_ready',
        'application_id',
        'department_id',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
    public function file()
    {
        return $this->belongsTo(Files::class,'file_id','id');
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function fileType()
    {
        return $this->belongsTo(FileType::class, 'file_type_id');
    }

    public function scopeWithFileCodeAndParent($query, $file_code)
    {
        return $query->whereHas('fileType', function ($query) use ($file_code) {
            $query->where('code', $file_code)
                ->where(function ($q) {
                    $q->whereNotNull('parent_id')  // Mencari yang parent_id tidak null
                        ->orWhereNull('parent_id'); // Jika tidak ada, mencari yang parent_id null
                });
        });
    }
}
