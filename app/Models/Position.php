<?php

namespace App\Models;

use App\Services\AuthService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // Tambahkan ini


class Position extends Model
{
    use HasFactory, Notifiable, HasRoles,SoftDeletes;

    protected $guard_name = 'web';
    protected $fillable = [
        'name',
        'created_by',
        'updated_by',
        'deleted_by',
    ];



    public function department(){
        return $this->belongsTo(Department::class);
    }


    public function scopeRestricted($query){
     $current_role = AuthService::currentAccess()['role'];

    if ($current_role === 'admin') {
        // Ambil posisi yang tidak memiliki role admin atau super_admin
        return $query->whereDoesntHave('roles', function ($sub_q) {
            $sub_q->whereIn('name', ['admin', 'super_admin']);
        });
    }
    // Jika super_admin, tampilkan semua
    return $query;
        
    }
}
