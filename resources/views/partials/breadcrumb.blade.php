<header class="app-title main-header gap-2 flex-wrap border-bottom border-1 border-success" aria-label="page title" aria-labelledby="page-title">
    <div class="page-title">
        <h1 id="page-title">{!! $icon_page !!} {!! $subtitle !!}</h1>
        <p>{!! $description_page !!}</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
        <li class="breadcrumb-item" aria-current="page" aria-label="Home" title="Home"><i class="bi bi-house-door fs-6"></i></li>
        @foreach ($breadcrumb as $key => $value)
            @if (is_string($key) && is_string($value))
                <li class="breadcrumb-item"><a href="{{ $value }}" aria-current="page" title="{!! $key !!}">{!! $key !!}</a></li>
            @elseif (is_string($key) && is_array($value))
                @foreach ($value as $file)
                    <li class="breadcrumb-item"><a href="{{ $file }}" aria-current="page" title="{!! $key !!}">{!! $key !!}</a></li>
                @endforeach
            @elseif (is_numeric($key) && is_string($value))
                <li class="breadcrumb-item active"><a href="javascript:void(0)" aria-current="page" title="{!! $value !!}">{!! $value !!}</a></li>
            @endif
        @endforeach
    </ul>
</header>
