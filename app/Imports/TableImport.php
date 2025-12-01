<?php

namespace App\Imports;

use App\Services\SchemaBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Maatwebsite\Excel\Concerns\ToCollection;

class TableImport implements ToCollection
{
    public string $table_name;
    private array $columns;
    private array $rules;

    public MessageBag $messages;
    public bool $is_success = false;
    public array $data = [];

    public function __construct(string $table_name, array $form_schema)
    {
        $this->table_name = $table_name;
        $this->columns = SchemaBuilder::get_table_columns_name_from_schema_representation($this->table_name);
        $this->rules = SchemaBuilder::get_validation_rules_from_schema($this->table_name, $form_schema, $this->columns);
    }

    /**
     * Import data from the given collection to the specified table.
     *
     * This function validates the given data by checking the number of columns
     * and validating it against the rules defined in the schema. If any of the
     * validation fails, the error messages are stored in the messages property.
     *
     * If all validation passes and the data is not empty, the function inserts
     * the data to the specified table and sets the success property to true.
     *
     * @param Collection $collection The data to be imported.
     *
     * @return void
     */
    public function collection(Collection $collection): void
    {
        $messages = new MessageBag();

        foreach ($collection as $j => $row) {
            // Check number of columns
            if (count($row) !== count($this->columns)) {
                $messages->add("row$j", "Invalid number of columns at row " . ($j + 1) . ". Expected " . count($this->columns) . ", but got " . count($row) . ".");
                continue;
            }

            $rowData = array_combine($this->columns, $row->toArray());
            // Validate all rows
            $valid = Validator::make($rowData, $this->rules);
            if ($valid->fails()) {
                $messages->add("row$j", "Invalid value at row " . ($j + 1) . ": <br>" . implode("<br>", $valid->messages()->all()));
            }

            $this->data[] = $rowData;
        }
        $this->messages = $messages;

        // Save all valid rows
        if ($this->messages->isEmpty() && !empty($this->data)) $this->is_success = true;
    }
}
