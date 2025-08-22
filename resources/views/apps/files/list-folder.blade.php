@foreach ($document_types as $dt)
    <a href="{{ route('documents.files.index', $dt->name) }}" role="button" class="text-decoration-none">
        <div class="folder-item p-3 {{ $document_type !== null && $document_type->name === $dt->name ? 'main-folder' : '' }}" aria-label="Folder {{ $dt->name }}" title="Folder {{ $dt->name }}">
            <div class="folder-info" aria-label="Info for folder {{ $dt->name }}">
                <div class="fw-semibold">
                    {{ $dt->name }}
                </div>
                <div class="small text-muted">
                    <span>Created on <time datetime="{{ $dt->created_at }}">{{ date('d F Y, H:i A', strtotime($dt->created_at)) }}</time>.</span>
                </div>
            </div>
        </div>
    </a>
@endforeach