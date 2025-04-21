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
    private string $document_type_name;
    private string $table_name;
    private string $file_name;

    public function __construct(string $document_type_name, string $table_name, string $file_name)
    {
        $this->document_type_name = $document_type_name;
        $this->table_name = $table_name;
        $this->file_name = $file_name;
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function view(): View
    {
        // Get columns name
        $columns = SchemaBuilder::get_table_columns_name_from_schema_representation($this->table_name);
        $columns_for_heading = array_map(function ($column) {
            return ucwords(str_replace('_', ' ', $column));
        }, $columns);

        $headings = Arr::map($columns_for_heading, function ($col, $i) {
            return Str::apa($col);
        });
        array_push($headings, 'Attached File', 'Created At',  'Updated At');

        array_push($columns, DB::raw('CONCAT(files.name, \'.\', files.extension) as file_name'), "{$this->table_name}.created_at",  "{$this->table_name}.updated_at");

        $data = DB::table($this->table_name)->select($columns)
            ->join('files', 'files.id', '=', "{$this->table_name}.file_id", 'left')
            ->get();

        return view('vendors.exports.general', [
            'headings' => $headings,
            'data' => $data,
            'action' => 'export'
        ]);
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
