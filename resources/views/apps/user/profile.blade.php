@extends("layouts.main")

@section("content")

    @php
        $user = auth()->user();
    @endphp

    <div class="row">
        <div class="col-md-6">
            <div class="tile h-100" aria-label="Tile section of users" aria-labelledby="tile-users-label">
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
                                    <td class="bg-body-tertiary">
                                        <h5>Name</h5>
                                    </td>
                                    <td>
                                        <h5 class="fw-normal">{{ $user->name }}</h5>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="bg-body-tertiary">
                                        <h5>Email</h5>
                                    </td>
                                    <td>
                                        <h5 class="fw-normal">{{ $user->email }}</h5>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="bg-body-tertiary">
                                        <h5>Role</h5>
                                    </td>
                                    <td>
                                        <h5 class="fw-normal">{{ $user->role }}</h5>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="bg-body-tertiary">
                                        <h5>Joined At</h5>
                                    </td>
                                    <td>
                                        <h5 class="fw-normal">{{ $user->created_at->format('d F Y') }}</h5>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="tile-footer">
                    <button type="button" role="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-edit" title="Button: to edit about your profile information">Edit</button>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="tile h-100" aria-label="Tile section of users" aria-labelledby="tile-users-label">
                <div class="tile-title-w-btn flex-wrap">  
                    <div class="tile-title flex-nowrap">
                        <h3 class="title" id="tile-users-label">Change Password</h3>
                        <small class="caption small font-italic fs-5">Forgot password? Change Here.</small>
                    </div>
                </div> 
                <form action="{{ route('users.profile.change.password') }}" class="form-horizontal" method="post">
                    @csrf

                    @method('PUT')
                    <div class="tile-body">
                        <div class="form-group mb-3">  
                            <label for="password_new" class="form-label">New Password<span aria-label="required" class="text-danger">*</span></label>
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
                            <label for="password_confirmation" class="form-label">Confirm Password<span aria-label="required" class="text-danger">*</span></label>
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
                    </div>
                    <div class="tile-footer">
                        <button type="submit" role="button" class="btn btn-block btn-primary" title="Button: to save the changes password">Send</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modal-edit" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-label="Modal users" aria-labelledby="modal-users-label" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-light">
                    <h1 class="modal-title fs-5" id="modal-users-label">Edit name</h1>
                    <button type="button" role="button" class="btn-close bg-light-subtle" tabindex="-1" title="Button: to close this modal" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('users.profile.update') }}" class="form-horizontal" id="form-users" method="POST">
                    <div class="modal-body">
                        @csrf

                        @method('PUT')
                        <div class="form-group mb-3 row">
                            <label for="name" class="col-sm-2 col-form-label">Name<span aria-label="required" class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="name" name="name" maxlength="100" placeholder="Name" aria-label="Name" aria-required="true" autocomplete="given-name" value="{{ $user->name }}" required>
                            </div>
                        </div>
                        <div class="form-group mb-3 row">
                            <label for="gender" class="col-sm-2 col-form-label">Gender</label>
                            <div class="col-sm-10">
                                <select class="form-control" id="gender" name="gender" placeholder="Gender" aria-label="Gender" aria-required="true" value="{{ $user->jenis_kelamin }}" required>
                                    <option value="laki-laki">Men</option>
                                    <option value="laki-laki">Women</option>
                                    <option value="etc">etc</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group mb-3 row">
                            <label for="address" class="col-sm-2 col-form-label">Address</label>
                            <div class="col-sm-10">
                                <textarea class="form-control" id="address" name="address" maxlength="100" placeholder="Address" aria-label="Address">{{ $user->address }}</textarea>
                            </div>
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