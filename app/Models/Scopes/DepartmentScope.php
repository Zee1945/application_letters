<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
    if ($model->getTable() !== 'users') {
        $user = Auth::user();
        
        if (!$user || !$user->department_id) {
            return;
        }

        $userRoles = $user->position->getRoleNames()->toArray();
        $isFinanceOrDekan = array_intersect(['finance', 'dekan','kabag','admin','super_admin','monitor'], $userRoles);
        
        if ($this->hasColumn($model, 'department_id')) {
            $tableName = $model->getTable();
            
            if ($isFinanceOrDekan) {
                // Finance/Dekan bisa lihat department sendiri + child departments
                $childDepartmentIds = \App\Models\Department::where('parent_id', $user->department_id)
                                                          ->pluck('id')
                                                          ->toArray();
                $allDepartmentIds = array_merge([$user->department_id], $childDepartmentIds);
                
                $builder->whereIn($tableName . '.department_id', $allDepartmentIds);
            } else {
                // User biasa hanya department sendiri
                $builder->where($tableName . '.department_id', $user->department_id);
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
