<?php

namespace App\Exports;

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
        
        $columns = SchemaBuilder::get_table_columns_name_from_schema_representation($table);

        $headings = Arr::map($columns, function($col, $i){
            return Str::apa($col);
        });
        array_push($headings, 'Attached File', 'Created At',  'Updated At');

        array_push($columns, DB::raw('CONCAT(files.name, ".", files.extension) as file_name'), $table.'.created_at',  $table.'.updated_at');

        $data = DB::table($table)->select($columns)
            ->join('files', 'files.id', '=', $table.'.file_id', 'left')
            ->get()->toJson();

        $data = json_decode($data, true);

        return view('vendors.exports.general', compact('data', 'headings'));
    }
}
