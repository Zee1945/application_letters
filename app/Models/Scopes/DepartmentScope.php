<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DepartmentScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public static $table_name;
    public static $table_columns;
    public function apply(Builder $builder, Model $model): void
    {
        // Jangan gunakan Auth::user() di scope User!
        if ($model->getTable() !== 'users') {
            $user = Auth::user();
            // if ($user && $this->hasColumn($model, 'department_id')) {
            //     $builder->where($model->getTable() . '.department_id', $user->department_id);
            // }
            $departmentId = $user->department_id ?? null;
            if ($user && $departmentId) {
                if ($model->getTable() == 'departments') {
                    $builder->where('id', $departmentId);
                } else {
                    if ($this->hasColumn($model, 'department_id')) {
                        $builder->where($model->getTable() . '.department_id', $departmentId);
                    }
                }
            }
        }
    }

    public function hasColumn($model, $column_name)
    {
        if (is_null(self::$table_columns) || (self::$table_name != $model->getTable())) {
            self::$table_name = $model->getTable();
            self::$table_columns = Schema::getColumnListing($model->getTable());
        }
        return in_array($column_name, self::$table_columns);
    }
}
