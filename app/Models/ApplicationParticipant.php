<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationParticipant extends Model
{
    use HasFactory;
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'application_id',
        'participant_type_id',
        'name',
        'institution',
        'nip',
        'rank',
        'material_file_id',
        'is_signer_commitee',
        'functional_position',
        'commitee_position',
        'participant_type_id',
        'application_id',
        'department_id',
        'delete_note',
        'cv_file_id',
        'idcard_file_id',
        'npwp_file_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function participantType()
    {
        return $this->belongsTo(ParticipantType::class);
    }
    // public function cvFiles()
    // {
    //     return $this->belongsTo(Files::class,'cv_file_id', 'id');
    // }
    // public function npwpFiles()
    // {
    //     return $this->belongsTo(Files::class,'npwp_file_id', 'id');
    // }
    // public function idCardFiles()
    // {
    //     return $this->belongsTo(Files::class,'idcard_file_id','id');
    // }
}
