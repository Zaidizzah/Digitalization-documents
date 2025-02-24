@extends("layouts.main")

@section("content")

<div class="row">
    <div class="col-md-6 col-lg-3">
        <div class="widget-small bx-shadow primary coloured-icon border border-1 border-success"><i class="icon bi bi-people fs-1"></i>
            <div class="info">
                <h4>Users</h4>
                <p><b>5</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small bx-shadow info coloured-icon border border-1 border-info"><i class="icon bi bi-heart fs-1"></i>
            <div class="info">
                <h4>Likes</h4>
                <p><b>25</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small bx-shadow warning coloured-icon border border-1 border-warning"><i class="icon bi bi-folder2 fs-1"></i>
            <div class="info">
                <h4>Uploades</h4>
                <p><b>10</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="widget-small bx-shadow danger coloured-icon border border-1 border-danger"><i class="icon bi bi-star fs-1"></i>
            <div class="info">
                <h4>Stars</h4>
                <p><b>500</b></p>
            </div>
        </div>
    </div>
</div>

@endsection