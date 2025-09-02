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
        <form action="{{ route("settings.update") }}" method="POST" id="form-settings" enctype="multipart/form-data">
            <div class="tile-body">
                @csrf

                @method('PUT')
                <div class="form-group row g-1 mb-3">
                    <label for="name" class="form-label col-sm-3">Name<span aria-label="required" class="text-danger">*</span></label>
                    <div class="col-sm-9">
                        <input type="text" name="name" class="form-control" id="name"
                        minlength="1"
                        maxlength="64"
                        pattern="^[a-zA-Z][a-zA-Z0-9_ ]{0,63}$"
                        title="Only letters and numbers are allowed, and maximum of 64 characters."
                        data-bs-toggle="tooltip" data-bs-placement="bottom"
                        data-bs-custom-class="custom-tooltip"
                        data-bs-title="The name must start with a letter, and only letters, spaces and numbers are allowed, and maximum of 64 characters."
                        aria-label="Name" aria-required="true" required>
                    </div>
                </div>

                <div class="form-group row g-1 mb-3">
                    <label for="long_name" class="form-label col-sm-3">Long Name</label>
                    <div class="col-sm-9">
                        <input type="text" name="long_name" class="form-control" aria-label="Long Name" aria-required="false">
                    </div>
                </div>

                <div class="form-group row g-1 mb-3">
                    <label for="description" class="form-label col-sm-3">Description</label>
                    <div class="col-sm-9">
                        <textarea name="description" class="form-control" rows="3" autocapitalize="sentences" aria-label="Description" aria-required="false"></textarea>
                    </div>
                </div>
            </div>
            <div class="tile-footer">
                <button type="submit" role="button" class="btn btn-primary btn-sm" title="Button: to save changes for application config variables"><i class="bi bi-save fs-5"></i>Save</button>
            </div>
        </form>
    </div>

@endsection