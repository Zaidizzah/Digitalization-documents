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
    private $table;
    private $columns;
    private $rules;

    public $messages;
    public $success = false;

    public function __construct($table, $form_schema)
    {
        $this->table = $table;
        $columns = SchemaBuilder::get_table_columns_name_from_schema_representation($table);
        $this->columns = $columns; 
        $rules = SchemaBuilder::get_validation_rules_from_schema($table, json_decode($form_schema, true), $columns);
        $this->rules = $rules;
    }
    
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        $data = [];
        $messages = new MessageBag();
        foreach ($collection as $j => $row) {
            array_push($data, []);
            foreach ($this->columns as $i => $col) {
                $data[$j] += [$col => $row[$i]];
            }
            $valid = Validator::make($data[$j], $this->rules);
            if($valid->fails()){
                $message = implode('<br>', $valid->messages()->all());
                $messages->add('row'.$j, 'Invalid value at row '.($j + 1).': <br>'.$message);
            }
        }
        
        $this->messages = $messages;
        if($this->messages->isEmpty()){
            $this->success = true;
            DB::table($this->table)->insert($data);
        }
    }
}
