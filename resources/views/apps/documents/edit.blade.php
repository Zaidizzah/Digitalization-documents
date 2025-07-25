@extends('layouts.main')

@section('content')

    @include('partials.document-type-action-menu', ['document_type', $document_type])

    {!! note("Input fields labeled <strong class=\"text-danger fs-4\">*</strong> must be filled in and must not be empty.") !!}
    
    <!-- Edit data to document type -->
    <div class="tile" aria-label="Tile section of edit data to document type {{ $document_type->name }}" aria-labelledby="tile-edit-data-document-type-label">
        <div class="tile-title-w-btn flex-wrap">  
            <div class="tile-title flex-nowrap">
                <h3 class="title" id="tile-edit-data-document-type-label">Edit data to document type {!! $document_type->abbr ?? $document_type->name !!}</abbr></h3>
                <small class="caption small font-italic fs-5">Edit data to document type {!! $document_type->abbr ?? $document_type->name !!}.</small>
            </div>
        </div>
        <form action="{{ route("documents.data.update", [$document_type->name, $id]) }}" method="post" id="form-edit-document-type">
            @csrf

            @method('PUT')
            <div class="tile-body">
                {!! $form_html !!}
            </div>
            <div class="tile-footer">
                <button type="submit" role="button" class="btn btn-primary btn-sm" title="Button: to edit new data to document type" onclick="return confirm('Are you sure you want to edit this data to document type {{ $document_type->name }}?')"><i class="bi bi-save fs-5"></i>Edit</button>
            </div>
        </form>
    </div>

@endsection