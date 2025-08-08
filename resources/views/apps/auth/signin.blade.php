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
        <link rel="stylesheet" type="text/css" href="{{ asset('resources/apps/styles.css') }}">

        <title>{{ config('app.name') }} | Signin</title>

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
            <div class="login-box" style="border: 2px solid #00695c">
                <form class="login-form" action="/signin" method="POST">
                    @csrf

                    <h3 class="login-head"><i class="bi bi-person me-2"></i>SIGN IN</h3>
                    <div class="mb-3">
                        <label class="form-label">EMAIL</label>
                        <input class="form-control" type="text" name="email" inputmode="email" placeholder="Email" aria-invalid="true" value="{{ old('email') }}" autofocus required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">PASSWORD</label>
                        <input class="form-control" type="password" name="password" placeholder="Password" aria-invalid="true" required>
                    </div>
                    <div class="mb-3">
                        <div class="utility flex-wrap">
                            <p class="semibold-text mb-2">Don't have an account? <a href="/signup">Sign Up</a></p>
                        </div>
                    </div>
                    <div class="mb-3 btn-container d-grid">
                        <button class="btn btn-primary btn-block border-2"><i class="bi bi-box-arrow-in-right me-2 fs-5"></i>SIGN IN</button>
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
        <script type="text/javascript" src="{{ asset('resources/plugins/imgpreview/js/imgpreview.js') }}"></script>
        <!-- The Application javascripts -->
        <script type="text/javascript" src="{{ asset('resources/apps/scripts.js') }}"></script>
    </body>
</html>