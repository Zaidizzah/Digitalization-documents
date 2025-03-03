@extends("layouts.main")

@section("content")

    @include('partials.document-menu')

    <style>
        .file-preview-wrapper {
            height: calc(100vh - 2rem);
            overflow-y: auto;
            border: 1px solid var(--primary-color);
        }
        #file-preview-pdf {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        #file-preview-pdf canvas {
            border: 1px solid var(--bs-border-color);
            background-color: #fff;
            max-width: 100%;
            width: auto;
            height: auto;
        }

        #file-preview-image {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            display: block;
            margin: 0 auto;
            border: 1px solid var(--bs-border-color);
            padding: 5px;
        }

        #file-preview-pdf canvas:last-child {
            border-bottom: none
        }

        @media screen and (max-width: 768px) {
            #file-preview-pdf canvas {
                max-height: 95vh;
            }
        }
    </style>

    <div class="row flex-wrap-reverse" id="row-preview-file" aria-label="File preview container">
        <!-- File Preview Section -->
        <div class="col-md-8">
            <div class="tile shadow-none" id="tile-preview-file" tabindex="0" aria-label="Tile section of preview file {{ "{$file->name}.{$file->extension}" }}" aria-labelledby="tile-preview-file-label">
                <div class="tile-title-w-btn flex-wrap">  
                    <div class="tile-title flex-nowrap">
                        <h3 class="title" id="tile-preview-file-label"><i class="bi bi-files"></i> File Preview</h3>
                        <small class="caption small font-italic fs-5">{{ "{$file->name}.{$file->extension}" }}</small>
                    </div>
                </div>
                <div class="tile-body">
                    <!-- File preview section -->
                    <div class="file-preview-wrapper" id="file-preview-wrapper" aria-label="File preview wrapper">

                        @if (in_array($file->extension, ['jpg', 'jpeg', 'png', 'webp']))
                            <!-- Image preview -->
                            <img src="{{ route('documents.files.preview.content', $file->encrypted_name) }}" id="file-preview-image" class="file-preview-image img-fluid" aria-label="File {{ "{$file->name}.{$file->extension}" }}" title="File {{ "{$file->name}.{$file->extension}" }}" loading="lazy" alt="{{ "{$file->name}.{$file->extension}" }}">
                        @else
                            <!-- PDF preview -->
                            <div id="file-preview-pdf" class="file-preview-pdf" aria-label="File {{ "{$file->name}.{$file->extension}" }}" title="File {{ "{$file->name}.{$file->extension}" }}" data-title="{{ "{$file->name}.{$file->extension}" }}" data-url-preview="{{ route('documents.files.preview.content', $file->encrypted_name) }}"></div>
                        @endif

                    </div>
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
                            <small class="caption small font-italic fs-5">{{ "{$file->name}.{$file->extension}" }}</small>
                        </div>
                    </div>
                    <div class="tile-body">
                        <table class="table table-striped table-bordered" aria-labelledby="table-file-info-label" aria-label="Table of file info {{ "{$file->name}.{$file->extension}" }}">
                            <caption id="table-file-info-label">File {{ "{$file->name}.{$file->extension}" }}</caption>
                            <tbody>
                                <tr>
                                    <td>Name</td>
                                    <td>{{ "{$file->name}.{$file->extension}" }}</td>
                                </tr>
                                <tr>
                                    <td>Type</td>
                                    <td>{{ $file->type }}</td>
                                </tr>
                                <tr>
                                    <td>Size</td>
                                    <td>{{ format_size_file($file->size) }}</td>
                                </tr>
                                <tr>
                                    <td>Last Modified</td>
                                    <td>{{ $file->updated_at->format('d F Y H:i A') }}</td>
                                </tr>
                                <tr>
                                    <td>Uploaded At</td>
                                    <td>{{ $file->created_at->format('d F Y H:i A') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection