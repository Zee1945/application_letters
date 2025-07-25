<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationDraftCostBudget extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'application_id',
        'code',
        'item',
        'sub_item',
        'volume',
        'unit',
        'cost_per_unit',
        'volume_realization',
        'unit_cost_realization',
        'realization',
        'total',
        'created_by',
        'updated_by',
        'deleted_by',
        'delete_note',
        'department_id',
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
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function files()
    {
        return $this->belongsToMany(Files::class, 'draft_cost_budget_files', 'application_draft_cost_budget_id', 'file_id');
    }

}
