@extends('layouts.main')

@section('content')

    @include('partials.document-type-action-menu', ["document_type" => $document_type])

    <div class="tile" aria-label="Tile section of schema attributes for document type {{ $document_type->name }}" aria-labelledby="tile-schema-document-type-label">
        <div class="tile-title-w-btn flex-wrap">  
            <div class="tile-title flex-nowrap">
                <h3 class="title" id="tile-schema-document-type-label">Schema attributes for document type {!! $document_type->abbr ?? $document_type->name !!}</abbr></h3>
                <small class="caption small font-italic fs-5">Displaying list attributes for document type {!! $document_type->abbr ?? $document_type->name !!}.</small>
            </div>
            <div class="tile-buttons">
                <a href="{{ route("documents.insert.schema.page", $document_type->name) }}" class="btn btn-primary btn-sm" id="btn-insert-schema-attributes-document-type" title="Button: to insert new schema attributes for document type {{ $document_type->name }}"><i class="bi bi-plus-square fs-5"></i> Insert New</a>
                <a href="#" class="btn btn-primary btn-sm" id="btn-change-order-schema-attributes-document-type" title="Button: to change the order of schema attributes for document type {{ $document_type->name }}"><i class="bi bi-sort-numeric-down fs-5"></i> Change Order</a>
                <a href="{{ route("documents.edit.schema", $document_type->name) }}" class="btn btn-primary btn-sm" id="btn-modify-schema-attributes-document-type" title="Button: to modify all schema attributes for document type {{ $document_type->name }}"><i class="bi bi-pencil-square fs-5"></i> Modify All</a>
            </div>
        </div>
        <div class="tile-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped table-bordered" id="table-schema-document-type" aria-labelledby="table-schema-document-type-label" aria-label="Table of schema attributes for document type {{ $document_type->name }}">
                    <caption id="table-schema-document-type-label">List of schema attributes for document type {!! $document_type->abbr ?? $document_type->name !!}.</caption>
                    <thead>
                        <tr>
                            <th class="text-nowrap" scope="col">No</th>
                            <th class="text-nowrap" scope="col">Name</th>
                            <th class="text-nowrap" scope="col">Type</th>
                            <th class="text-nowrap" scope="col">Required</th>
                            <th class="text-nowrap" scope="col">Unique</th>
                            <th class="text-nowrap" scope="col">Created At</th>
                            <th class="text-nowrap" sope="col">Modified At</th>
                            <th class="text-nowrap" scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (empty($list_schema_data))
                            <tr aria-rowindex="1" role="row">
                                <th scope="row" colspan="9" aria-colspan="9" class="text-center">No schema attributes available for document type {!! $document_type->abbr ?? $document_type->name !!}.</th>
                            </tr>
                        @else
                            {!! $list_schema_data !!}
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection