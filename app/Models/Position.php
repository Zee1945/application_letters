<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // Tambahkan ini


class Position extends Model
{
    use HasFactory, Notifiable, HasRoles;
    protected $fillable = [
        'name',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
