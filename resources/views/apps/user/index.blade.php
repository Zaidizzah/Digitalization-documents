@extends("layouts.main")

@section("content")

    <!-- Modal for creating and editing users -->
    <div class="modal fade" id="modal-users" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-label="Modal users" aria-labelledby="modal-users-label" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-light">
                    <h1 class="modal-title fs-5" id="modal-users-label">{{ $subtitle }}</h1>
                    <button type="button" role="button" class="btn-close bg-light-subtle" tabindex="-1" title="Button: to close this modal" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('users.store') }}" class="form-horizontal" id="form-users" method="POST">
                    <div class="modal-body">
                        @csrf
                        <div class="form-group mb-3 row">
                            <label for="name" class="col-sm-2 col-form-label">Name<span aria-label="required" class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="name" name="name" maxlength="100" placeholder="Name" aria-label="Name" aria-required="true" autocomplete="given-name" value="{{ old('name') }}" required>
                            </div>
                        </div>
                        <div class="form-group mb-3 row">
                            <label for="email" class="col-sm-2 col-form-label">Email<span aria-label="required" class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input type="email" class="form-control" id="email" name="email" minlength="6" maxlength="76" title="Please enter a valid email address, and enter a value between 6 and 76." placeholder="Email" aria-label="Email" aria-required="true" autocomplete="email" value="{{ old('email') }}" required>
                            </div>
                        </div>
                        <div class="form-group mb-3 row">
                            <label for="role" class="col-sm-2 col-form-label">Role<span aria-label="required" class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <select class="form-select" id="role" name="role" aria-label="Role" aria-required="true" disabled required>
                                    <option value="user" selected>User</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group mb-3 row">  
                            <label for="password" class="col-sm-2 col-form-label">Password<span aria-label="required" class="text-danger">*</span></label>
                            <div class="col-sm-10">
                                <input type="password" class="form-control" id="password" name="password" 
                                data-bs-toggle="tooltip" data-bs-placement="top"
                                data-bs-custom-class="custom-tooltip"
                                data-bs-title="Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number. Example: 'Password#3529'"
                                pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,16}$$"
                                minlength="8" maxlength="16"
                                placeholder="Password" aria-required="true" 
                                autocomplete="new-password"
                                required>
                            </div>
                        </div>
                        <div class="form-group mb-3 row">
                            <label for="password_confirmation" class="col-sm-2 col-form-label">Confirm Password<span aria-label="required" class="text-danger">*</span></label>
                            <div class="col-sm-10">
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
                    </div>
                    <div class="modal-footer bg-light-subtle">
                        <button type="reset" role="button" class="btn btn-secondary" title="Button: to cancel this action" data-bs-dismiss="modal"><i class="bi bi-dash-square fs-5"></i> Cancel</button>
                        <button type="submit" role="button" class="btn btn-primary" title="Button: to save new user"><i class="bi bi-save fs-5"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="tile" aria-label="Tile section of users" aria-labelledby="tile-users-label">
        <div class="tile-title-w-btn flex-wrap">  
            <div class="tile-title flex-nowrap">
                <h3 class="title" id="tile-users-label">List of users</h3>
                <small class="caption small font-italic fs-5">Displaying a list of users.</small>
            </div>
            <button type="button" class="btn btn-primary btn-sm" role="button" data-bs-toggle="modal" data-bs-target="#modal-users" title="Button: to add new user"><i class="bi bi-plus-square fs-5"></i> Add</button>
        </div> 
        <div class="tile-body">
            <div class="search-form" id="search-form" aria-label="Search form container">
                <form action="{{ route('users.index') }}" class="novalidate" method="get">
                    <div class="input-group">
                        <input type="search" class="form-control" name="search" placeholder="Search" value="{{ request('search') ?? '' }}">
                        <button type="submit" class="btn btn-primary" title="Button: to apply filtering data">Search</button>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-bordered" aria-labelledby="table-users-label" aria-label="Table of users">
                    <caption>List of data for users.</caption>
                    <thead>
                        <tr>
                            <th class="text-nowrap" scope="col">No</th>
                            <th class="text-nowrap" scope="col">Name</th>
                            <th class="text-nowrap" scope="col">Email</th>
                            <th class="text-nowrap" scope="col">Role</th>
                            <th class="text-nowrap" scope="col">Created At</th>
                            <th class="text-nowrap" sope="col">Modified At</th>
                            <th class="text-nowrap" scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($users->isEmpty())
                            <tr class="text-muted text-center" aria-hidden="true" aria-label="No data users">
                                <td colspan="7" aria-colspan="7">No user data available{!! request('search') ? ' for <mark>' . request('search') . '</mark>' : '' !!}.</td>
                            </tr>
                        @else
                            @foreach ($users as $user)
                                <tr aria-rowindex="{{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}" role="row">
                                    <th scope="row" class="text-nowrap" data-id="{{ $user->id }}">{{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}</th>
                                    <td class="text-nowrap">{!! str_replace(request('search'), '<mark>' . request('search') . '</mark>', $user->name) !!}</td>
                                    <td class="text-nowrap">{!! str_replace(request('search'), '<mark>' . request('search') . '</mark>', $user->email) !!}</td>
                                    <td class="text-nowrap">{!! str_replace(request('search'), '<mark>' . request('search') . '</mark>', $user->role) !!}</td>
                                    <td class="text-nowrap"><time datetime="{{ $user->created_at }}">{!! str_replace(request('search'), '<mark>' . request('search') . '</mark>', $user->created_at->format('d F Y, H:i A')) !!}</time></td>
                                    <td class="text-nowrap"><time datetime="{{ $user->updated_at }}">{!! str_replace(request('search'), '<mark>' . request('search') . '</mark>', $user->updated_at->format('d F Y, H:i A')) !!}</time></td>
                                    <td class="text-nowrap">
                                        <button type="button" class="btn btn-warning btn-sm btn-edit" role="button" title="Button: to edit user" data-id="{{ $user->id }}"><i class="bi bi-pencil-square fs-5"></i></button>
                                        <a href="{{ route('users.delete', $user->id) }}" class="btn btn-danger btn-sm btn-delete" role="button" title="Button: to delete user" data-id="{{ $user->id }}"><i class="bi bi-trash fs-5"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
        @if ($users->hasPages())
            <div class="tile-footer">
                {{ $users->onEachSide(2)->links('vendors.pagination.custom') }}
            </div>
        @endif
    </div>

@endsection