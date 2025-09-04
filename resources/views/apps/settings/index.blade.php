@extends("layouts.main")

@section("content")

    @include('partials.setting-menu')

    <div class="tile" aria-label="Tile section of users" aria-labelledby="tile-users-label">
        <div class="tile-title-w-btn flex-wrap">  
            <div class="tile-title flex-nowrap">
                <h3 class="title" id="tile-users-label">Action options for application config variables</h3>
                <small class="caption small font-italic fs-5">Action options for modifiying application config variables.</small>
            </div>
        </div> 
        <div class="tile-body">
            <div class="feature-cooming-soon--card" tabindex="0" role="presentasion" aria-label="Feature coming soon">
                <h1>Coming soon</h1>
            </div>
        </div>
    </div>

@endsection