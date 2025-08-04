@extends("layouts.main")

@section("content")

    @include('partials.document-menu')

    <div class="tile" aria-label="Tile section, list of document types" aria-labelledby="tile-document-types-label">
        <div class="tile-title-w-btn flex-wrap">  
            <div class="tile-title flex-nowrap">
                <h3 class="title" id="tile-document-types-label">List of document types</h3>
                <small class="caption small font-italic fs-5">Displaying a list of document types.</small>
            </div>
            @can('role-access', 'Admin')
                <a href="{{ route('documents.create') }}" type="link" class="btn btn-primary btn-sm" role="link" id="btn-add-document-type" title="Button: to add new document type"><i class="bi bi-plus-square fs-5"></i> Add</a>
            @endcan
        </div>
        <div class="tile-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered" aria-labelledby="table-documents-label" aria-label="Table of document types">
                    <caption id="table-documents-label">List of document types.</caption>
                    <thead>
                        <tr>
                            <th class="text-nowrap" scope="col">No</th>
                            <th class="text-nowrap" scope="col">Name</th>
                            <th class="text-nowrap" scope="col">Status</th>
                            <th class="text-nowrap" scope="col">Created At</th>
                            <th class="text-nowrap" sope="col">Modified At</th>
                            <th class="text-nowrap" sope="col">Deleted At</th>
                            <th class="text-nowrap" scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($document_types->isEmpty())
                            <tr>
                                <td colspan="8" aria-colspan="8" class="text-center">No data available for active document types.</td>
                            </tr>
                        @else
                            @foreach ($document_types as $document_type)
                                <tr aria-rowindex="{{ ($document_types->currentPage() - 1) * $document_types->perPage() + $loop->iteration }}" role="row" aria-label="Document type: {{ $document_type->name }}">
                                    <th scope="row" data-id="{{ $document_type->id }}">{{ ($document_types->currentPage() - 1) * $document_types->perPage() + $loop->iteration }}</th>
                                    <td class="text-nowrap">{!! (!empty($document_type->long_name) ? '<abbr title="' . $document_type->long_name . '">' . $document_type->name . '</abbr>' : $document_type->name ) !!}</td>
                                    <td class="text-nowrap">{!! $document_type->is_active ? '<span class="badge bg-success">active</span>' : '<span class="badge bg-danger">inactive</span>' !!}</td>
                                    <td class="text-nowrap"><time datetime="{{ $document_type->created_at }}">{{ $document_type->created_at->format('d F Y, H:i A') }}</time></td>
                                    <td class="text-nowrap"><time datetime="{{ $document_type->updated_at }}">{{ $document_type->updated_at->format('d F Y, H:i A') }}</time></td>
                                    <td class="text-nowrap">{!! $document_type->deleted_at !== NULL ? "<time datetime=\"{$document_type->deleted_at}\">{$document_type->deleted_at->format('d F Y, H:i A')}</time>" : '-' !!}</td>
                                    <td class="text-nowrap">
                                        @if ($document_type->is_active)
                                            <a href="{{ route('documents.browse', [$document_type->name, 'action' => 'browse']) }}" type="button" class="btn btn-info btn-sm btn-browse" role="button" title="Button: to browse data of document type '{{ $document_type->name }}'" data-id="{{ $document_type->id }}"><i class="bi bi-search fs-5"></i></a>
                                            @can('role-access', 'Admin')
                                                <a href="{{ route("documents.insert.schema.page", $document_type->name) }}" type="button" class="btn btn-primary btn-sm" id="btn-insert-schema-attributes-document-type" title="Button: to insert new schema attributes for document type {{ $document_type->name }}"><i class="bi bi-plus-square fs-5"></i></a>

                                                <form action="{{ route('documents.delete', $document_type->id) }}" class="form-delete d-inline" method="post">
                                                    @csrf

                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm btn-delete" role="button" title="Button: to delete document type '{{ $document_type->name }}'" data-id="{{ $document_type->id }}"><i class="bi bi-trash fs-5"></i></button>
                                                </form>
                                            @endcan
                                        @else
                                            <form action="{{ route('documents.restore', $document_type->id) }}" class="form-restore d-inline" method="post">
                                                @csrf

                                                @method('PUT')
                                                <button type="submit" class="btn btn-secondary btn-sm" role="button" title="Button: to restore or activate document type '{{ $document_type->name }}'"><i class="bi bi-arrow-counterclockwise fs-5"></i></button>
                                            </form>

                                            <p class="d-inline"><span class="badge bg-danger">Deactivated</span> by "<strong>{{ $document_type->user->name }}</strong>"</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        @if ($document_types->hasPages())
            <div class="tile-footer">
                {{ $document_types->onEachSide(2)->links('vendors.pagination.custom') }}
            </div>
        @endif
    </div>

@endsection