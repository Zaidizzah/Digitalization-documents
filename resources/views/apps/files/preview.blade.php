@extends("layouts.main")

@section("content")

    @if ($document_type !== null)
        @include('partials.document-type-action-menu', ['document_type' => $document_type])
    @else
        @include('partials.document-menu')
    @endif

    <div class="row flex-wrap-reverse" id="row-preview-file" aria-label="File preview container">
        <!-- File Preview Section -->
        <div class="col-md-8">
            <div class="tile shadow-none" id="tile-preview-file" tabindex="0" aria-label="Tile section of preview file {{ "{$file->name}.{$file->extension}" }}" aria-labelledby="tile-preview-file-label">
                <div class="tile-title-w-btn flex-wrap">  
                    <div class="tile-title flex-nowrap">
                        <h3 class="title" id="tile-preview-file-label"><i class="bi bi-files"></i> File Preview</h3>
                        <small class="caption small font-italic fs-5 text-break">{{ "{$file->name}.{$file->extension}" }}</small>
                    </div>
                </div>
                <div class="tile-body">
                    <!-- File preview section -->
                    <div class="file-preview-wrapper" id="file-preview-wrapper" aria-label="File preview wrapper">

                        @if (in_array($file->extension, ['jpg', 'jpeg', 'png', 'webp']))
                            <!-- Image preview -->
                            <img src="{{ route('documents.files.content', $file->encrypted_name) }}" class="file-preview-image img-fluid" id="file-preview-image" aria-label="File {{ "{$file->name}.{$file->extension}" }}" title="File {{ "{$file->name}.{$file->extension}" }}" loading="lazy" alt="{{ "{$file->name}.{$file->extension}" }}">
                        @else
                            <!-- PDF preview -->
                            <div class="file-preview-pdf" id="file-preview-pdf" aria-label="File {{ "{$file->name}.{$file->extension}" }}" title="File {{ "{$file->name}.{$file->extension}" }}" data-title="{{ "{$file->name}.{$file->extension}" }}" data-url-preview="{{ route('documents.files.content', $file->encrypted_name) }}"></div>
                        @endif

                    </div>
                </div>
                <div class="tile-footer">
                    <a href="{{ $document_type !== null && $document_type->name ? route('documents.files.download', [$document_type->name, 'file' => $file->encrypted_name]) : route('documents.files.root.download', ['file' => $file->encrypted_name]) }}" role="button" class="btn btn-primary btn-sm" title="Button: to download file {{ "{$file->name}.{$file->extension}" }}"><i class="bi bi-download fs-5"></i> Download</a>
                </div>
            </div>
        </div>

        <!-- File Info Section -->
        <div class="col-md-4">
            <div class="sticky-panel">
                <div class="tile shadow-none" id="tile-info-file" tabindex="1" aria-label="Tile section of file info {{ "{$file->name}.{$file->extension}" }}" aria-labelledby="tile-info-file-label">
                    <div class="tile-title-w-btn flex-wrap">  
                        <div class="tile-title flex-nowrap">
                            <h3 class="title" id="tile-info-file-label">File Info</h3>
                            <small class="caption small font-italic fs-5 text-break">{{ "{$file->name}.{$file->extension}" }}</small>
                        </div>
                    </div>
                    <div class="tile-body">
                        <table class="table table-striped table-bordered" aria-labelledby="table-file-info-label" aria-label="Table of file info {{ "{$file->name}.{$file->extension}" }}">
                            <caption id="table-file-info-label">File {{ "{$file->name}.{$file->extension}" }}</caption>
                            <tbody>
                                <tr>
                                    <td>Name</td>
                                    <td class="text-break">{{ "{$file->name}.{$file->extension}" }}</td>
                                </tr>
                                <tr>
                                    <td>Type</td>
                                    <td>{{ $file->type }}</td>
                                </tr>
                                <tr>
                                    <td>Size</td>
                                    <td>{{ Number::fileSize($file->size) }}</td>
                                </tr>
                                <tr>
                                    <td>Last Modified</td>
                                    <td><time datetime="{{ $file->updated_at }}">{{ $file->updated_at->format('d F Y H:i A') }}</time></td>
                                </tr>
                                <tr>
                                    <td>Uploaded At</td>
                                    <td><time datetime="{{ $file->created_at }}">{{ $file->created_at->format('d F Y H:i A') }}</time></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection