<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogActivity extends Model
{
    protected $table = 'log_activities';

    protected $fillable = [
        'activity',
        'description',
        'user_id',
        'reference_id',
    ];

    /**
     * Relasi ke user yang melakukan aktivitas.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
