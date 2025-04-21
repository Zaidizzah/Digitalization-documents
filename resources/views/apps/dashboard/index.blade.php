@extends("layouts.main")

@section("content")

<div class="row">
    <div class="col-md-6 col-lg-3">
        <div class="widget-small bx-shadow primary coloured-icon border border-1 border-success"><i class="icon bi bi-people fs-1"></i>
            <div class="info">
                <h4>Users</h4>
                <p><b>{{ $users }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small bx-shadow info coloured-icon border border-1 border-info"><i class="icon bi bi-folder fs-1"></i>
            <div class="info">
                <h4>Document Types</h4>
                <p><b>{{ $document_types }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small bx-shadow warning coloured-icon border border-1 border-warning"><i class="icon bi bi-files fs-1"></i>
            <div class="info">
                <h4>Files</h4>
                <p><b>{{ $files }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small bx-shadow danger coloured-icon border border-1 border-danger"><i class="icon bi bi-file-text fs-1"></i>
            <div class="info">
                <h4>Unlabeled Files</h4>
                <p><b>{{ $unlabeled_files }}</b></p>
            </div>
        </div>
    </div>
</div>

@endsection