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
        'type_name',
        'code',
        'file_id',
        'trans_type',
        'status_ready',
        'application_id',
        'department_id',
        'delete_note',
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
}
