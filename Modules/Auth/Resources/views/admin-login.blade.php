<!DOCTYPE html>
<html lang="en">
<head>
    @include('adminmodule::layouts.partials._document-head', ['pageTitle' => translate('admin_Sign_In')])
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta http-equiv="content-type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="description" content=""/>
    <meta name="keywords" content=""/>
    <meta name="robots" content="nofollow, noindex ">
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"/>

    <link href="{{asset('assets/admin-module')}}/css/material-icons.css" rel="stylesheet"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/bootstrap.min.css"/>
    <link rel="stylesheet"
          href="{{asset('assets/admin-module')}}/plugins/perfect-scrollbar/perfect-scrollbar.min.css"/>

    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/style.css"/>
    <link rel="stylesheet" href="{{asset('assets/admin-module')}}/css/toastr.css">
    <style>
        body {
            background: #f4f6fb;
            min-height: 100vh;
        }
        .login-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100dvh;
            padding: 1.5rem;
        }
        .login-right-wrap {
            width: 100%;
            max-width: 450px;
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(15, 23, 42, 0.08);
        }
        .login-right {
            max-width: none;
            width: 100%;
        }
        .login-logo {
            max-block-size: none;
            max-inline-size: 280px;
            width: 100%;
            height: auto;
        }
        .login-field {
            margin-bottom: 1rem;
        }
        .login-field .form-control.is-invalid {
            border-color: #dc3545 !important;
            background-image: none;
            box-shadow: 0 0 0 1px #dc3545;
        }
        .login-field .form-control.is-invalid:focus {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.16);
        }
        .login-error {
            display: block;
            color: #dc3545;
            font-size: 0.8125rem;
            line-height: 1.35;
            margin-top: 0.35rem;
        }
        .login-error[hidden] {
            display: none;
        }
        .login-form-alert {
            background: #fdecea;
            color: #b42318;
            border: 1px solid #f5c2c7;
            border-radius: 8px;
            padding: 0.65rem 0.75rem;
            font-size: 0.875rem;
            line-height: 1.4;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
<div class="preloader"></div>

<div>
    <form action="{{route('admin.auth.login')}}" enctype="multipart/form-data" method="POST"
            id="login-form" novalidate>
        @csrf
        <div class="login-wrap">
            <div class="login-right-wrap bg-white">

                <div class="login-right w-100 m-auto p-3">
                    <div class="d-flex flex-column align-items-center text-center gap-2 mb-5 mt-3">
                        <img class="login-logo mb-2"
                            src="{{ asset('assets/admin-module/img/panun-kaergar-logo.png') }}"
                            alt="Panun Kaergar">
                        <h2 class="c1 fw-medium mb-0">{{translate('admin_Sign_In')}}</h2>
                        <p class="mb-0">{{translate('sign_in_to_stay_connected')}}</p>
                    </div>

                    @if ($errors->has('login') || $errors->has('g-recaptcha-response'))
                        <div class="login-form-alert" role="alert">
                            {{ $errors->first('login') ?: $errors->first('g-recaptcha-response') }}
                        </div>
                    @endif

                    <div class="mb-4">
                        <div class="login-field">
                            <div class="form-floating form-floating__icon">
                                <input type="email" name="email_or_phone"
                                        class="form-control @if($errors->has('email_or_phone') || $errors->has('login')) is-invalid @endif"
                                        value="{{ old('email_or_phone', request()->cookie('remember_email')) }}"
                                        placeholder="{{translate('example@gmail.com')}}"
                                        autocomplete="username"
                                        aria-invalid="{{ $errors->has('email_or_phone') || $errors->has('login') ? 'true' : 'false' }}"
                                        aria-describedby="email-error"
                                        id="email">
                                <label>{{translate('email')}}</label>
                                <span class="material-icons">mail</span>
                            </div>
                            <div class="login-error" id="email-error" @unless($errors->has('email_or_phone')) hidden @endunless>
                                {{ $errors->first('email_or_phone') }}
                            </div>
                        </div>
                        <div class="login-field">
                            <div class="form-floating form-floating__icon">
                                <input type="password" name="password"
                                        class="form-control @if($errors->has('password') || $errors->has('login')) is-invalid @endif"
                                        value="{{ $errors->any() ? '' : request()->cookie('remember_password') }}"
                                        placeholder="{{translate('********')}}"
                                        autocomplete="current-password"
                                        aria-invalid="{{ $errors->has('password') || $errors->has('login') ? 'true' : 'false' }}"
                                        aria-describedby="password-error"
                                        id="password">
                                <label>{{translate('password')}}</label>
                                <span class="material-icons togglePassword">visibility_off</span>
                                <span class="material-icons">lock</span>
                            </div>
                            <div class="login-error" id="password-error" @unless($errors->has('password')) hidden @endunless>
                                {{ $errors->first('password') }}
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <div class="d-flex gap-1 align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" value="1"
                                        {{ request()->cookie('remember_checked') ? 'checked' : '' }} id="rememberMeCheckbox">
                                    <label class="form-check-label" for="rememberMeCheckbox">{{translate('Remember me?')}}</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    @php($recaptcha = business_config('recaptcha', 'third_party'))
                    @if(isset($recaptcha) && $recaptcha->is_active)
                        <div class="recaptcha d-flex justify-content-center mb-4">
                            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                        </div>
                    @endif

                    <div class="d-flex mb-2">
                        <button class="btn btn--primary flex-grow-1 text-capitalize" id="signInBtn"
                                type="submit">{{translate('sign_in')}}</button>
                    </div>
                </div>

                @if(env('APP_ENV')=='demo')
                    <div class="login-footer d-flex justify-content-between text-light c1-bg gap-3">
                        <button type="button" class="btn login-copy">
                            <span class="material-symbols-outlined m-0">content_copy</span>
                        </button>
                        <div class="flex-grow-1">
                            <div>{{translate('email')}} : {{translate('admin@admin.com')}}</div>
                            <div>{{translate('password')}} : {{translate('12345678')}}</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </form>
</div>


<script src="{{asset('assets/admin-module')}}/js/jquery-3.6.0.min.js"></script>
<script src="{{asset('assets/admin-module')}}/js/bootstrap.bundle.min.js"></script>
<script src="{{asset('assets/admin-module')}}/plugins/perfect-scrollbar/perfect-scrollbar.min.js"></script>
<script src="{{asset('assets/admin-module')}}/js/main.js"></script>

<script src="{{asset('assets/admin-module')}}/js/sweet_alert.js"></script>
<script src="{{asset('assets/admin-module')}}/js/toastr.js"></script>
{!! Toastr::message() !!}

@php($recaptcha = business_config('recaptcha', 'third_party'))
@if(isset($recaptcha) && $recaptcha->is_active)
    <script src="https://www.google.com/recaptcha/api.js?render={{$recaptcha->live_values['site_key']}}"></script>
    <script>
        "use strict";
        $('#signInBtn').click(function (e) {
            e.preventDefault();

            if (typeof window.loginFormIsValid === 'function' && !window.loginFormIsValid()) {
                return;
            }

            if (typeof grecaptcha === 'undefined') {
                toastr.error('Invalid recaptcha key provided. Please check the recaptcha configuration.');
                return;
            }

            grecaptcha.ready(function () {
                grecaptcha.execute('{{$recaptcha->live_values['site_key']}}', {action: 'submit'}).then(function (token) {
                    document.getElementById('g-recaptcha-response').value = token;
                    document.querySelector('form').submit();
                });
            });

            window.onerror = function(message) {
                var errorMessage = 'An unexpected error occurred.';
                if (message.includes('Invalid site key')) {
                    errorMessage = 'Invalid site key provided. Please check the site key configuration.';
                } else if (message.includes('not loaded in api.js')) {
                    errorMessage = 'reCAPTCHA API could not be loaded. Please check the API configuration.';
                }
                toastr.error(errorMessage)
                return true;
            };
        });
    </script>
@endif


<script>
    "use strict";

    (function () {
        var form = document.getElementById('login-form');
        var email = document.getElementById('email');
        var password = document.getElementById('password');

        function messageFor(input) {
            var value = input.value.trim();
            if (input === email) {
                if (value === '') {
                    return 'Email is required.';
                }
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    return 'Enter a valid email address.';
                }
                return '';
            }
            if (value === '') {
                return 'Password is required.';
            }
            return '';
        }

        function showError(input, message) {
            var field = input.closest('.login-field');
            var error = field.querySelector('.login-error');
            if (message) {
                input.classList.add('is-invalid');
                input.setAttribute('aria-invalid', 'true');
                error.hidden = false;
                error.textContent = message;
            } else {
                input.classList.remove('is-invalid');
                input.setAttribute('aria-invalid', 'false');
                error.hidden = true;
                error.textContent = '';
            }
        }

        function validateField(input) {
            var message = messageFor(input);
            showError(input, message);
            return message === '';
        }

        function validateForm() {
            var emailOk = validateField(email);
            var passwordOk = validateField(password);
            if (!emailOk) {
                email.focus();
            } else if (!passwordOk) {
                password.focus();
            }
            return emailOk && passwordOk;
        }

        [email, password].forEach(function (input) {
            input.addEventListener('blur', function () {
                if (input.value.trim() !== '' || input.classList.contains('is-invalid')) {
                    validateField(input);
                }
            });
            input.addEventListener('input', function () {
                if (input.classList.contains('is-invalid')) {
                    validateField(input);
                }
                var alert = form.querySelector('.login-form-alert');
                if (alert) {
                    alert.remove();
                }
            });
        });

        form.addEventListener('submit', function (event) {
            if (!validateForm()) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        });

        window.loginFormIsValid = validateForm;
    })();

    @if(env('APP_ENV')=='demo')
        $('.login-copy').on('click', function () {
            copy_cred()
        })

        function copy_cred() {
            $('#email').val('admin@admin.com');
            $('#password').val('12345678');
            toastr.success('{{translate('Copied successfully')}}', 'Success', {
                CloseButton: true,
                ProgressBar: true
            });
        }
   @endif
</script>
</body>
</html>
