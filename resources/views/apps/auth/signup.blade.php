<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Manage {{ config('app.name') }}">
        <meta name="keywords" content="Manage {{ config('app.name') }}">
        <!-- Main CSS-->
        <link rel="stylesheet" type="text/css" href="{{ asset('assets/vali-admin-master/css/main.css') }}">
        <!-- Font-icon css-->
        <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
        <!-- Styles CSS -->
        <link rel="stylesheet" type="text/css" href="{{ asset('resources/apps/main/css/styles.css') }}">

        <title>{{ config('app.name') }} | Signup</title>

        <!-- Custom Style -->
        <style type="text/css" aria-label="style">
            .logo {
                width: calc(100% - 1rem) !important;
                margin: 0 auto !important;
                text-align: center !important;
                margin-bottom: 2rem !important;
            } 
            .login-content .login-box {
                max-width: 450px !important;
                margin: 0 auto !important;
                height: auto !important;
                width: calc(100% - 1rem) !important;
                border: 2px solid #00695c
            }
            .login-form {
                position: relative !important;
                width: 100% !important;
                height: 100% !important;
                padding: 2rem 2rem !important;
                margin: 0 auto !important;
            }
        </style>
    </head>
    <body>
        <section class="material-half-bg">
            <div class="cover"></div>
        </section>
        <section class="login-content">
            <div class="logo">
                <h1>Document Digitalization</h1>
            </div>
            <div class="login-box">
                <form class="login-form" action="/signup" method="POST">
                    @csrf

                    <h3 class="login-head"><i class="bi bi-person me-2"></i>SIGN UP</h3>
                    <div class="mb-3">
                        <label for="name" class="form-label">Name<span aria-label="required">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" maxlength="100" placeholder="Name" aria-label="Name" aria-required="true" autofocus autocomplete="given-name" value="{{ old('name') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email<span aria-label="required">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" inputmode="email" minlength="6" maxlength="76" title="Please enter a valid email address, and enter a value between 6 and 76." placeholder="Email" aria-label="Email" aria-required="true" autocomplete="email" value="{{ old('email') }}" required>
                    </div>
                    <div class="mb-3">  
                        <label for="password" class="form-label">Password<span aria-label="required">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" 
                        data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-custom-class="custom-tooltip"
                        data-bs-title="Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number. Example: 'Password#3529'"
                        pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,16}$$"
                        minlength="8" maxlength="16"
                        placeholder="Password" 
                        aria-label="Password" aria-required="true" 
                        autocomplete="new-password"
                        required>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm Password<span aria-label="required">*</span></label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" 
                        data-parsley-equalto="#password"
                        data-bs-toggle="tooltip" data-bs-placement="top"
                        data-bs-custom-class="custom-tooltip"
                        data-bs-title="It must be the same as the password field."
                        pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,16}$$"
                        minlength="8" maxlength="16"
                        placeholder="Confirm Password" 
                        aria-label="Confirm Password" aria-required="true" 
                        required>
                    </div>
                    <div class="mb-3">
                        <div class="utility flex-wrap">
                            <p class="semibold-text mb-2">Already have an account? <a href="/signin">Sign In</a></p>
                        </div>
                    </div>
                    <div class="mb-3 btn-container d-grid">
                        <button class="btn btn-primary btn-block border-2"><i class="bi bi-box-arrow-in-right me-2 fs-5"></i>SIGN UP</button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Including alert Section -->
        @includeWhen($errors->any() || session()->has('message'), "partials.alert")

        <!-- Essential javascripts for application to work-->
        <script src="{{ asset('assets/vali-admin-master/js/jquery-3.7.0.min.js') }}"></script>
        <script src="{{ asset('assets/vali-admin-master/js/bootstrap.min.js') }}"></script>
        <script src="{{ asset('assets/vali-admin-master/js/main.js') }}"></script>
        <!-- The Application javascripts -->
        <script type="text/javascript" src="{{ asset('resources/apps/main/js/scripts.js') }}"></script> 

        <script type="text/javascript">
            INITIALIZE_TOOLTIPS();
        </script>
    </body>
</html>