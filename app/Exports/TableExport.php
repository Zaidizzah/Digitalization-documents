<?php

namespace App\Exports;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Services\SchemaBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithProperties;

class TableExport implements FromView, ShouldAutoSize, WithProperties
{
    private $table;
    private $file_name;

    public function __construct($table, $file_name)
    {
        $this->table = $table;
        $this->file_name = $file_name;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function view(): View
    {
        $table = $this->table;

        $columns = SchemaBuilder::get_table_columns_name_from_schema_representation($table);
        $columns_for_heading = array_map(function ($column) {
            return ucwords(str_replace('_', ' ', $column));
        }, $columns);

        $headings = Arr::map($columns_for_heading, function ($col, $i) {
            return Str::apa($col);
        });
        array_push($headings, 'Attached File', 'Created At',  'Updated At');

        array_push($columns, DB::raw('CONCAT(files.name, \'.\', files.extension) as file_name'), "$table.created_at",  "$table.updated_at");

        $data = DB::table($table)->select($columns)
            ->join('files', 'files.id', '=', "$table.file_id", 'left')
            ->get()->toJson();

        $data = json_decode($data, true);

        return view('vendors.exports.general', compact('data', 'headings'));
    }

    /*
     * @return array
     */
    public function properties(): array
    {
        return [
            'title' => "Digitalization Document - {$this->file_name}",
        ];
    }
}
