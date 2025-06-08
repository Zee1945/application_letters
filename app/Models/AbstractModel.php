<?php

namespace App\Models;

use App\Models\Scopes\DepartmentScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

abstract class AbstractModel extends Model
{
    use HasFactory;

    public static $table_columns;
    public static $table_name;
    public static $cancel_reason;
    public static $deleted_time;

    protected $use_validation = true;

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope(new DepartmentScope);

        // static::saving(function ($model) {
        //     $request = Request();
        //     $attr_has_updated = $model->getDirty();
        //     if ($model->use_validation && !in_array('deleted_at', array_keys($attr_has_updated)))
        //         Validator::make($model->attributes, $model::getValidation())->validate();
        // });


        // static::creating(function ($model) {
        //     $userid = (!Auth::guest()) ? Auth::user()->id : null;
        //     if (User::find($userid) && !$model->created_by) {
        //         $model->created_by = $userid;
        //     }
        //     // if ($model->created_by_ip) $model->created_by_ip = request()->ip();
        //     if ($model->updated_by && !$model->updated_by) {
        //         $model->updated_by = $userid;
        //         // $model->updated_by_ip = request()->ip();
        //     }
        //     if (Auth::check()) {
        //         $user = Auth::user();
        //         if (!($user)) {
        //             $model->department_id = Auth::user()->department_id;
        //         }
        //     }
        //     //  elseif ($model->getTable() == 'core_directories' && $model->parent_id && !$model->core_client_id) {
        //     //     $parent = CoreDirectory::withoutGlobalScope(ClientScope::class)->find($model->parent_id);
        //     //     $model->core_client_id = $parent->core_client_id;
        //     // }
        // });

        // static::updating(function ($model) {
        //     $attr_has_updated = $model->getDirty();

        //     if (!in_array('deleted_by', array_keys($attr_has_updated))) {
        //         $userid = (!Auth::guest()) ? Auth::user()->id : null;
        //         if ($model->updated_by) {
        //             $model->updated_by = $userid;
        //             // $model->updated_by_ip = request()->ip();
        //         }
        //     }
        // });

        // if (method_exists(static::class, 'restoring')) {
        //     static::restoring(function ($model) {
        //         if ($model->deleted_note) $model->deleted_note = null;
        //         $model->deleted_by = null;
        //         // $model->deleted_by_ip = null;
        //     });
        // }
    }

    public function hasColumn($column_name)
    {
        if (is_null(self::$table_columns) || (self::$table_name != $this->getTable())) {
            self::$table_name = $this->getTable();
            self::$table_columns = Schema::getColumnListing($this->getTable());
        }
        return in_array($column_name, self::$table_columns);
    }








}
