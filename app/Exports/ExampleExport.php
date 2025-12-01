<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithProperties;

class ExampleExport implements FromView, WithProperties
{
    private string $file_name;
    private \Illuminate\Support\Collection $data;

    public function __construct(string $file_name, array $data)
    {
        $this->file_name = $file_name;
        $this->data = collect($data);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    public function view(): View
    {
        return view('vendors.exports.general', [
            'data' => $this->data,
            'action' => 'example'
        ]);
    }

    public function properties(): array
    {
        return [
            'title' => "Digitalization Document - {$this->file_name}"
        ];
    }
}
