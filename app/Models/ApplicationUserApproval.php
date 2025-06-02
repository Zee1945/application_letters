<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationUserApproval extends Model
{
protected $fillable = [
    'user_id',
    'user_text',
    'sequence',
    'status',
    'note',
    'application_id',
    'department_id',
    'delete_note',
    'created_by',
    'updated_by',
    'deleted_by',
];
    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
