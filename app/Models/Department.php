<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Department extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'delete_note',
        'created_by',
        'limit_submission',
        'current_limit_submission',
        'parent_id',
        'approval_by',
        'updated_by',
        'deleted_by',
    ];


    public function parent(){
        return $this->belongsTo(Department::class,'parent_id');
    }
    public function children(){
        return $this->hasMany(Department::class,'parent_id');
    }
}
