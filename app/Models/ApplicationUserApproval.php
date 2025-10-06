<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationUserApproval extends Model
{
use HasFactory;
use SoftDeletes;
protected $fillable = [
    'user_id',
    'user_text',
    'sequence',
    'status',
    'note',
    'report_note',
    'application_id',
    'position_id',
    'trans_type',
    'role',
    'role_text',
    'is_verificator',
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
    public function position()
    {
        return $this->belongsTo(Position::class);
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

    public function currentUserApproval()
    {
        return $this->hasMany(Application::class, 'current_seq_user_approval', 'sequence');
    }
}
