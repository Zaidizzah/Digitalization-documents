<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DynamicModel extends Model
{
    use HasFactory;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'trashed_at' => 'datetime',
    ];

    protected $connection = 'mysql';
    protected $__except_fillable_fields = ['id', 'created_at', 'updated_at'];

    // protected $with = ['file']; // because it's a one-to-one relationship to files table

    /**
     * Set the database connection for the model.
     *
     * @param string $connection The connection name to set.
     *
     * @return \App\Models\DynamicModel
     */
    public function __setConnection($connection): DynamicModel
    {
        $this->setConnection($connection);
        return $this;
    }

    /**
     * Set the table name for the model.
     *
     * @param string $tableName The name of the table to set.
     * 
     * @return \App\Models\DynamicModel
     */
    public function __setTableName($tableName): DynamicModel
    {
        $this->setTable($tableName);
        return $this;
    }

    /**
     * Get the table name for the model.
     *
     * @return string The name of the table.
     */
    public function __getTableName(): string
    {
        return $this->table;
    }

    /**
     * Set the fields that should be excluded from fillable fields.
     *
     * This method allows setting which fields should be excluded from
     * being fillable. By default, it merges the provided fields with the
     * model's default excluded fields, unless specified otherwise.
     *
     * @param array $fields The fields to exclude.
     * @param bool $default_exclude_fields Whether to merge with default excluded fields. Defaults to true.
     * 
     * @return \App\Models\DynamicModel
     */

    public function __setExceptFillableFields(array $fields, bool $default_exclude_fields = true): DynamicModel
    {
        $this->__except_fillable_fields = $default_exclude_fields === TRUE ? [...$fields, ...$this->__except_fillable_fields] : $fields;
        return $this;
    }

    /**
     * Set the fillable fields for the model.
     *
     * @param array $fields The fields to set as fillable.
     * @param bool $include_exclude_fields Whether to merge with excluded fields. Defaults to true.
     * 
     * @return \App\Models\DynamicModel
     */
    public function __setFillableFields(array $fields, bool $include_exclude_fields = true): DynamicModel
    {
        // Checking and removing except fields
        if ($include_exclude_fields) {
            $fields = array_diff($fields, $this->__except_fillable_fields);
        }

        $this->fillable($fields);
        return $this;
    }

    /**
     * Set the timestamps for the model.
     *
     * This method allows setting whether the model should have timestamps.
     *
     * @param bool $timestamps Whether the model should have timestamps.
     *
     * @return \App\Models\DynamicModel
     */
    public function __useTimestamps(bool $timestamps): DynamicModel
    {
        $this->timestamps = $timestamps;
        return $this;
    }
}
