@extends("layouts.main")

@section("content")

<div class="row">
    <div class="col-md-6">
        <div class="tile" aria-label="Tile section of users" aria-labelledby="tile-users-label">
            <div class="tile-title-w-btn flex-wrap">  
                <div class="tile-title flex-nowrap">
                    <h3 class="title" id="tile-users-label">Your Profile</h3>
                    <small class="caption small font-italic fs-5">Displaying your profile information.</small>
                </div>
            </div> 
            <div class="tile-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <tbody>
                            <tr>
                                <td>
                                    <h5>Name</h5>
                                </td>
                                <td>
                                    <h5>{{ auth()->user()->name }}</h5>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-name">Edit</button>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h5>Email</h5>
                                </td>
                                <td>
                                    <h5>{{ auth()->user()->email }}</h5>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h5>Role</h5>
                                </td>
                                <td>
                                    <h5>{{ auth()->user()->role }}</h5>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <h5>Joined At</h5>
                                </td>
                                <td>
                                    <h5>{{ auth()->user()->created_at->format('d F Y') }}</h5>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="tile" aria-label="Tile section of users" aria-labelledby="tile-users-label">
            <div class="tile-title-w-btn flex-wrap">  
                <div class="tile-title flex-nowrap">
                    <h3 class="title" id="tile-users-label">Change Password</h3>
                    <small class="caption small font-italic fs-5">Forgot password? Change Here.</small>
                </div>
            </div> 
            <div class="tile-body">
                <form action="{{ route('profile.change_password') }}" method="post">
                    <div class="form-group mb-3">  
                        <label for="password_new">New Password<span aria-label="required" class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password_new" name="password_new" 
                            data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-custom-class="custom-tooltip"
                            data-bs-title="Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number. Example: 'Password#3529'"
                            pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,16}$$"
                            minlength="8" maxlength="16"
                            placeholder="Password" aria-required="true" 
                            autocomplete="new-password"
                            required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="password_confirmation">Confirm Password<span aria-label="required" class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" 
                            data-parsley-equalto="#password"
                            data-bs-toggle="tooltip" data-bs-placement="top"
                            data-bs-custom-class="custom-tooltip"
                            data-bs-title="It must be the same as the password field."
                            pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,16}$$"
                            minlength="8" maxlength="16"
                            placeholder="Confirm Password" aria-required="true" 
                            required>
                    </div>
                    <button type="submit" class="btn btn-block btn-primary">Send</button>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modal-name" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-label="Modal users" aria-labelledby="modal-users-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-light">
                <h1 class="modal-title fs-5" id="modal-users-label">Edit name</h1>
                <button type="button" role="button" class="btn-close bg-light-subtle" tabindex="-1" title="Button: to close this modal" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('profile.change_name') }}" class="form-horizontal" id="form-users" method="POST">
                <div class="modal-body">
                    @csrf
                    <div class="form-group mb-3 row">
                        <label for="name" class="col-sm-2 col-form-label">Name<span aria-label="required" class="text-danger">*</span></label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="name" name="name" maxlength="100" placeholder="Name" aria-label="Name" aria-required="true" autocomplete="given-name" value="{{ auth()->user()->name }}" required>
                        </div>
                    </div>
                <div class="modal-footer bg-light-subtle">
                    <button type="reset" role="button" class="btn btn-secondary" title="Button: to cancel this action" data-bs-dismiss="modal"><i class="bi bi-dash-square fs-5"></i> Cancel</button>
                    <button type="submit" role="button" class="btn btn-primary" title="Button: to save new name"><i class="bi bi-save fs-5"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection