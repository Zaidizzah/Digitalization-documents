@extends('layouts.main')

@section('content')

    @include('partials.document-type-action-menu', ["document_type" => $document_type])

    <div class="tile" aria-label="Tile section of data document type {{ $document_type->name }}" aria-labelledby="tile-data-document-type-label">
        <div class="tile-title-w-btn flex-wrap">  
            <div class="tile-title flex-nowrap">
                <h3 class="title" id="tile-data-document-type-label">Document type {!! $document_type->abbr ?? $document_type->name !!}</abbr></h3>
                <small class="caption small font-italic fs-5">Displaying list data for document type {!! $document_type->abbr ?? $document_type->name !!}.</small>
            </div>
        </div>
        <div class="tile-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="table-document-type" aria-labelledby="table-document-type-label" aria-label="Table of document types {{ $document_type->name }}">
                    <caption id="table-document-type-label">List of data for document type {!! $document_type->abbr ?? $document_type->name !!}.</caption>
                    {!! $list_document_data !!}
                </table>
            </div>
        </div>
        @if ($pagination->hasPages())
            <div class="tile-footer">
                {{ $pagination->onEachSide(2)->links('vendors.pagination.custom') }}
            </div>
        @endif
    </div>

@endsection