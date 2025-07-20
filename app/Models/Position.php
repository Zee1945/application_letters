<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // Tambahkan ini


class Position extends Model
{
    use HasFactory, Notifiable, HasRoles;

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
}
