<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Services\SchemaBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class TableExport implements FromView, ShouldAutoSize
{
    private $table;

    public function __construct($table)
    {
        $this->table = $table;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View
    {
        $table = $this->table;
        
        $columns = SchemaBuilder::get_form_columns_name_from_schema_representation($table);
        array_splice($columns, 0, 1);

        $headings = Arr::map($columns, function($col, $i){
            return Str::apa($col);
        });
        array_push($headings, 'Attached File', 'Created At',  'Updated At');

        $db_columns = $columns;
        array_push($db_columns, DB::raw('files.name as file_name'), $table.'.created_at',  $table.'.updated_at');

        array_push($columns, 'file_name', 'created_at',  'updated_at');
        
        $data = DB::table($table)->select($db_columns)
            ->join('files', 'files.id', '=', $table.'.file_id', 'left')
            ->get()->toJson();

        $data = json_decode($data, true);

        return view('vendors.exports.general', compact('data', 'columns', 'headings'));
    }
}
