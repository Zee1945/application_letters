<?php

namespace App\Models;

use App\Services\AuthService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Department extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,SoftDeletes;

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
    // public function scopeListOptions($query){
    //     $current_department = AuthService::currentAccess()['']
    //     return $query->
    // }

    public function scopeApprovalDepartment($query)
    {
        return $query->when($this->approval_by === 'self', function ($q) {
                return $q->where('id', $this->id); // departemen sendiri
            })
            ->when($this->approval_by === 'parent', function ($q) {
                return $q->where('id', $this->parent_id); // departemen parent
            })
            ->when($this->approval_by === 'central', function ($q) {
                return $q->where('code', 'REKTORAT'); // departemen parent
            });
    }

}
