<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Files extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'file_type',
        'encrypted_filename',
        'mimetype',
        'belongs_to',
        'path',
        'storage_type',
        'filesize',
        'application_id',
        'department_id',
        'created_by',
        'updated_by',
        'deleted_note',
        'deleted_at',
        'deleted_by',
    ];

    public function applicationDraftCostBudgets()
    {
        return $this->belongsToMany(ApplicationDraftCostBudget::class, 'draft_cost_budget_files');
    }
}
