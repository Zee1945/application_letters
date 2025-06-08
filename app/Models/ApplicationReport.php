<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationReport extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'introduction',
        'budget_realization',
        'activity_description',
        'obstacles',
        'conclusion',
        'recommendations',
        'closing',
        'speaker_material',
        'notulen',
        'speaker_cv',
        'financial_statement',
        'photos',
        'tax_id_number',
        'identity_card_number',
        'delete_note',
        'created_by',
        'deleted_by',
        'department_id',
        'application_id',
        'updated_by',
        'created_by',
        'updated_by',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
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
