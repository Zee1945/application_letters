<?php

namespace App\Models;

use App\Services\AuthService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationReport extends Model
{
    use HasFactory;
    use SoftDeletes;
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
        'background',
        'speaker_material',
        'recommendations',
        'total_participants',
        'total_participants_not_present',
        'total_participants_present',
        'current_approval_status',
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

    public function currentUserApproval()
    {
        return $this->belongsTo(ApplicationUserApproval::class, 'current_seq_user_approval', 'sequence');
    }
    public function attachments()
    {
        return $this->hasMany(ReportAttachment::class, 'application_report_id');
    }

    // public function scopeNeedMyProcess(Builder $query)
    // {
    //     $user = AuthService::currentAccess();
    //     return $query->where(function($q) use ($user) {
    //         $q->where(function($sub) use ($user) {
    //             $sub->whereHas('currentUserApproval',function($q) use($user){
    //                 $q->where('user_id',$user['id']);
    //             } )->where('current_approval_status', '<', 11)->whereNot('current_approval_status',0);
    //         });
    //     });
    // }
}
