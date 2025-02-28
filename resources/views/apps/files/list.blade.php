@foreach ($file_data as $f)
    <div class="file-item uploaded-file-item p-3" aria-label="File {{ $f->name }}.{{ $f->extension }}" title="File {{ $f->name }}.{{ $f->extension }}">
        <div class="file-info-wrapper d-flex align-items-center">
            <div class="file-info" aria-label="Info for file {{ $f->name }}.{{ $f->extension }}">
                <div class="fw-semibold"><span>{{ $f->name }}.{{ $f->extension }}</span></div>
                <div class="small text-muted">
                    <span>{{ format_size_file($f->size) }} - Uploaded on {{ date('d F Y, H:i A', strtotime($f->created_at)) }}.</span>
                </div>
            </div>
        </div>
        <div class="dropdown">
            <button class="file-browse btn btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Actions button for file {{ $f->name }}.{{ $f->extension }}" title="Actions button for file {{ $f->name }}.{{ $f->extension }}">
                <i class="bi bi-three-dots-vertical"></i>
            </button>
            <ul class="dropdown-menu">
                <li>
                    <a class="dropdown-item" href="javascript:void(0)"
                        aria-label="Browse file {{ $f->name }}.{{ $f->extension }}" title="Button: to browse file {{ $f->name }}.{{ $f->extension }}" 
                        data-bs-toggle="modal" data-bs-target="#modal-files"
                        data-file-id="{{ $f->id }}" data-file-name="{{ $f->name }}" data-file-extension="{{ $f->extension }}" 
                        data-file-size="{{ format_size_file($f->size) }}"
                        data-file-uploaded-at="{{ date('d F Y, H:i A', strtotime($f->created_at)) }}" data-file-modified-at="{{ date('d F Y, H:i A', strtotime($f->updated_at)) }}" 
                        data-file-labeled="kk" data-file-abbr="kartu kaluarga"
                        aria-label="File {{ $f->name }}" title="File {{ $f->name }}.{{ $f->extension }}">
                        <i class="bi bi-search fs-5"></i>Info
                    </a>
                </li>
                <li>
                    <a href="{{ route('documents.files.root.preview', ['file' => $f->encrypted_name]) }}" role="button" class="dropdown-item" aria-label="Preview file {{ $f->name }}" title="Button: to preview file {{ $f->name }}"><i class="bi bi-eye fs-5"></i> Preview</a>
                </li>
                <li>
                    <a href="{{ route('documents.files.download', ['file' => $f->encrypted_name]) }}" role="button" class="dropdown-item" aria-label="Download file {{ $f->name }}" title="Button: to download file {{ $f->name }}"><i class="bi bi-download fs-5"></i> Download</a>
                </li>
                <li>    
                    <a href="javascript:void(0)" role="button" class="dropdown-item" aria-label="Edit file {{ $f->name }}" 
                        title="Button: to edit file {{ $f->name }}"
                        data-file-id="{{ $f->id }}" data-file-name="{{ $f->name }}" data-file-extension="{{ $f->extension }}" data-file-document-id="{{ $f->document_type_id }}"
                        data-bs-toggle="modal" data-bs-target="#modal-files-edit">
                        <i class="bi bi-pencil-square fs-5"></i> Edit
                    </a>
                </li>
                <li>
                    <a href="{{ route('documents.files.delete', ['file' => $f->encrypted_name]) }}" role="button" class="dropdown-item" aria-label="Delete file {{ $f->name }}" title="Button: to delete file {{ $f->name }}" onclick="return confirm('Are you sure to delete this file?')">
                        <i class="bi bi-trash fs-5"></i> Delete
                    </a>
                </li>
            </ul>
        </div>
    </div>
@endforeach