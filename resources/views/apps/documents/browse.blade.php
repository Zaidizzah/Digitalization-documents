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
            <div class="search-form" id="search-form" aria-label="Search form container">
                <form action="{{ route('documents.browse', $document_type->name) }}" class="novalidate flex-wrap justify-content-start" method="get">
                    <!-- Hidden input to store the action and file id -->
                    <input type="hidden" name="action" value="{{ request('action') }}" aria-hidden="true">
                    <input type="hidden" name="file" value="{{ request('file') }}" aria-hidden="true">

                    <div class="input-group">
                        <!-- search by column -->
                        <select name="column" class="form-select" title="Select column to search">
                            <option value="">All</option>
                            @foreach ($columns_name as $column)
                                <option value="{{ $column }}" @selected(request('column') === $column)>{{ $column === 'file_id' ? 'Attached file' : $column }}</option>
                            @endforeach
                        </select>
                        <input type="search" class="form-control" name="search" placeholder="Search" value="{{ request('search') ?? '' }}">
                        <button type="submit" class="btn btn-primary" title="Button: to apply filtering data">Search</button>
                    </div>
                    @if (request('action') !== 'attach')
                        <a href="{{ route('documents.files.index', [$document_type->name, 'action' => 'attach']) }}" type="button" role="button" class="btn btn-primary btn-sm" title="Button: to attach file to document type {{ $document_type->name }} data"><i class="bi bi-file-earmark-plus fs-5"></i> Attach</a>
                    @endif
                </form>
            </div>

            @if (request('action') === 'attach')
                <!-- Form for attach file to document type data -->
                <form action="{{ route('documents.data.attach', $document_type->name) }}" class="novalidate" method="post">
                    @csrf

                    @method('PUT')
            @endif

            <div class="table-responsive">
                <table class="table table-hover table-bordered" id="table-document-type" aria-labelledby="table-document-type-label" aria-label="Table of document types {{ $document_type->name }}">
                    <caption id="table-document-type-label">List of data for document type {!! $document_type->abbr ?? $document_type->name !!}.</caption>
                    {!! $list_document_data !!}
                </table>
            </div>

            @if (request('action') === 'attach')
                <div class="attached-file-action" aria-labelledby="Attached file action">
                    <div class="input-group">
                        <!-- Hidden input to store the attached file ID -->
                        <input type="hidden" name="file_id" value="{{ $attached_file->id }}" aria-hidden="true">

                        <input type="text" class="form-control" value="{{ "{$attached_file->name}.{$attached_file->extension}" }}" aria-disabled="true" disabled>
                        <button type="submit" role="button" class="btn btn-primary btn-sm" title="Button: to attaching file {{ "{$attached_file->name}.{$attached_file->extension}" }} to document type {{ $document_type->name }} data" onclick="return confirm('Are you sure to attach file {{ "{$attached_file->name}.{$attached_file->extension}" }} to document type {{ $document_type->name }} data?')"><i class="bi bi-paperclip fs-5"></i> Attaching</button>
                    </div>
                </div>

                <!-- End form for attach file to document type data -->
                </form>
            @endif
        </div>
        @if ($pagination->hasPages())
            <div class="tile-footer">
                {{ $pagination->onEachSide(2)->links('vendors.pagination.custom') }}
            </div>
        @endif
    </div>

@endsection