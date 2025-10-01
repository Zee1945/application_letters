<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportAttachment extends Model
{  
     use HasFactory;
     use SoftDeletes;

      protected $fillable = [
        'file_id',
        'reference_id',
        'application_report_id',
        'type',
        'delete_note',
        'created_by',
        'deleted_by',
        'updated_by',
        'created_by',
        'updated_by',
      ];
      public function applicationReport()
    {
        return $this->belongsTo(ApplicationReport::class, 'application_report_id');
    }

        public function file()
        {
            return $this->belongsTo(Files::class, 'file_id');
        }
}
