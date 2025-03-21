<div class="toast-container position-fixed bottom-0 end-0 p-3">
    @if ($errors->any())
        @foreach ($errors->all() as $error)
            <div class="toast bs-shadow error fade show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="15000">
                <div class="toast-header">
                    <strong class="me-auto">Error!</strong>
                    <button type="button" class="btn-close bg-light-subtle" title="Close toast" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    <p class="paragraph">{!! $error !!}</p>
                </div>
            </div>
        @endforeach
    @endif

    @if (session()->has('message')) 
        {!! session()->get('message') !!}
    @endif
</div>