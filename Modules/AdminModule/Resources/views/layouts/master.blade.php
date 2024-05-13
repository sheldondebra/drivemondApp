<!DOCTYPE html>
<html lang="{{ session()->has('locale') ? session('locale') : 'en' }}" dir="{{ session()->get('direction') ?? 'ltr' }}">
@php($logo = getSession('header_logo'))
@php($favicon = getSession('favicon'))
@php($preloader = getSession('preloader'))

<head>
    <!-- Page Title -->
    <title>@yield('title')</title>

    <!-- Meta Data -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="" />
    <meta name="keywords" content="" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Favicon -->
    {{-- <link rel="shortcut icon" href="{{$favicon ? asset("storage/app/public/business/.$favicon"): " "}}"
          onerror="this.src='{{asset('public/assets/admin-module/img/favicon.png')}}'"/> --}}
    <link rel="shortcut icon"
        href="{{ onErrorImage(
            $favicon,
            asset('storage/app/public/business') . '/' . $favicon,
            asset('assets/admin-module/img/favicon.png'),
            'business/',
        ) }}" />
    {{-- <link rel="shortcut icon" href="{{ $favicon ? asset("storage/app/public/business/{$favicon}") : '' }}"
        onerror="this.src='{{ asset('public/assets/admin-module/img/favicon.png') }}'" /> --}}
    <link rel="shortcut icon"
        href="{{ onErrorImage(
            $favicon,
            asset('storage/app/public/business') . '/' . $favicon,
            asset('assets/admin-module/img/favicon.png'),
            'business/',
        ) }}" />
    <!-- Web Fonts -->
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/fonts/google.css') }}" />

    <!-- ======= BEGIN GLOBAL MANDATORY STYLES ======= -->
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/bootstrap-icons.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin-module/plugins/icon-set/style.css') }}" />
    <link rel="stylesheet"
        href="{{ asset('assets/admin-module/plugins/perfect-scrollbar/perfect-scrollbar.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/toastr.css') }}" />
    <!-- ======= END BEGIN GLOBAL MANDATORY STYLES ======= -->

    <!-- ======= BEGIN PAGE LEVEL PLUGINS STYLES ======= -->
    <link rel="stylesheet" href="{{ asset('assets/admin-module/plugins/apex/apexcharts.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin-module/plugins/select2/select2.min.css') }}" />
    <!-- ======= END BEGIN PAGE LEVEL PLUGINS STYLES ======= -->

    <link href="{{ asset('assets/admin-module/css/intlTelInput.min.css') }}" rel="stylesheet"/>

    <!-- ======= MAIN STYLES ======= -->
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/style.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/admin-module/css/custom.css') }}" />
    @include('adminmodule::layouts.css')
    <!-- ======= END MAIN STYLES ======= -->

    <!-- ======= FOR CUSTOM STYLE ======= -->
    @stack('css_or_js')
    @stack('css_or_js2')
</head>

<body>
    <script>
        localStorage.theme && document.querySelector('body').setAttribute("theme", localStorage.theme);
        localStorage.dir && document.querySelector('html').setAttribute("dir", localStorage.dir);
    </script>

    <!-- Offcanval Overlay -->
    <div class="offcanvas-overlay"></div>
    <!-- Offcanval Overlay -->
    <!-- Preloader -->
    <div class="preloader" id="preloader">
        @if ($preloader)
            <img class="preloader-img" width="160" loading="eager"
                src="{{ $preloader ? asset('storage/app/public/business/' . $preloader) : '' }}" alt="">
        @else
            <div class="spinner-grow" role="status">
                <span class="visually-hidden">{{ translate('Loading...') }}</span>
            </div>
        @endif
    </div>
    <div class="resource-loader" id="resource-loader" style="display: none;">
        @if ($preloader)
            <img width="160" loading="eager"
                src="{{ asset('storage/app/public/business') }}/{{ $preloader ?? null }}" alt="">
        @else
            <div class="spinner-grow" role="status">
                <span class="visually-hidden">{{ translate('Loading...') }}</span>
            </div>
        @endif
    </div>
    <!-- End Preloader -->

    <!-- Header -->
    @include('adminmodule::partials._header')
    <!-- End Header -->

    <!-- Aside -->
    @include('adminmodule::partials._sidebar')
    <!-- End Aside -->

    <!-- Settings Sidebar -->
    @include('adminmodule::partials._settings')
    <!-- End Settings Sidebar -->


    <!-- Wrapper -->
    <main class="main-area">
        <!-- Main Content -->
        @yield('content')
        <!-- End Main Content -->

        <!-- Footer -->
        @include('adminmodule::partials._footer')
        <!-- End Footer -->

    </main>
    <!-- End wrapper -->

    <span class="system-default-country-code" data-value="{{ getSession('country_code') ?? 'us' }}"></span>

    <script src="{{ asset('assets/admin-module/js/firebase.min.js') }}"></script>

    <!-- ======= BEGIN GLOBAL MANDATORY SCRIPTS ======= -->
    <script src="{{ asset('assets/admin-module/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/admin-module/plugins/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>
    <script src="{{ asset('assets/admin-module/plugins/select2/select2.min.js') }}"></script>
    {{-- TOASTR and SWEETALERT --}}
    <script src="{{ asset('assets/admin-module/js/sweet_alert.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/toastr.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/dev.js') }}"></script>

    <script src="{{ asset('assets/admin-module/js/intlTelInput.min.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/country-picker-init.js') }}"></script>
    <script src="{{ asset('assets/admin-module/js/main.js') }}"></script>
    <!-- ======= BEGIN GLOBAL MANDATORY SCRIPTS ======= -->

    {!! Toastr::message() !!}
    @if ($errors->any())
        <script>
            "use strict";
            @foreach ($errors->all() as $error)
                toastr.error('{{ $error }}', Error, {
                    CloseButton: true,
                    ProgressBar: true
                });
            @endforeach
        </script>
    @endif
    <script>
        "use strict";

        $(".status-change").on('change', function() {
            statusAlert(this);
        })

        function statusAlert(obj) {
            let url = $(obj).data('url');
            let checked = $(obj).prop("checked");
            let status = checked === true ? 1 : 0;
            Swal.fire({
                title: '{{ translate('are_you_sure') }}?',
                text: '{{ translate('want_to_change_status') }}',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'let(--bs-primary)',
                cancelButtonColor: 'default',
                cancelButtonText: '{{ translate('no') }}',
                confirmButtonText: '{{ translate('yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: url,
                        _method: 'PUT',
                        data: {
                            status: status,
                            id: obj.id
                        },
                        success: function() {
                            toastr.success("{{ translate('status_changed_successfully') }}");
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        },
                        error: function() {
                            if (status === 1) {
                                $('#' + obj.id+'.status-change').prop('checked', false)
                            } else if (status === 0) {
                                $('#' + obj.id+'.status-change').prop('checked', true)
                            }
                            toastr.error("{{ translate('status_change_failed') }}");
                        }
                    });
                } else {
                    if (status === 1) {
                        $('#' + obj.id+'.status-change').prop('checked', false)
                    } else if (status === 0) {
                        $('#' + obj.id+'.status-change').prop('checked', true)
                    }
                }
            })
        }

        $(".default-status").on('change', function() {
            defaultStatusAlert(this);
        })

        function defaultStatusAlert(obj) {
            let url = $(obj).data('url');
            let checked = $(obj).prop("checked");
            let status = checked === true ? 1 : 0;
            Swal.fire({
                title: '{{ translate('are_you_sure') }}?',
                text: '{{ translate('want_to_change_default_status') }}',
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: 'let(--bs-primary)',
                cancelButtonColor: 'default',
                cancelButtonText: '{{ translate('no') }}',
                confirmButtonText: '{{ translate('yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        url: url,
                        _method: 'PUT',
                        data: {
                            status: status,
                            id: obj.id
                        },
                        success: function() {
                            toastr.success("{{ translate('default_status_changed_successfully') }}");
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        },
                        error: function() {
                            if (status === 1) {
                                $('#' + obj.id+'.default-status').prop('checked', false)
                            } else if (status === 0) {
                                $('#' + obj.id+'.default-status').prop('checked', true)
                            }
                            toastr.error("{{ translate('default_status_change_failed') }}");
                        }
                    });
                } else {
                    if (status === 1) {
                        $('#' + obj.id+'.default-status').prop('checked', false)
                    } else if (status === 0) {
                        $('#' + obj.id+'.default-status').prop('checked', true)
                    }
                }
            })
        }

        $(".form-alert").on('click', function() {
            let id = $(this).data('id');
            let message = $(this).data('message');
            formAlert(id, message)
        })

        function formAlert(id, message) {
            Swal.fire({
                title: '{{ translate('are_you_sure') }}?',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: 'let(--bs-danger)',
                cancelButtonText: '{{ translate('no') }}',
                confirmButtonText: '{{ translate('yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $('#' + id).submit()
                }
            })
        }

        $(".form-alert-approved-rejected").on('click', function() {
            let id = $(this).data('id');
            let message = $(this).data('message');
            formAlertApprovedRejected(id, message)
        })

        function formAlertApprovedRejected(id, message) {
            Swal.fire({
                title: '{{ translate('are_you_sure') }}?',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: 'let(--bs-danger)',
                cancelButtonText: '{{ translate('no') }}',
                confirmButtonText: '{{ translate('yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $('#' + id).submit()
                }
            })
        }

        $(".form-alert-warning").on('click', function() {
            let id = $(this).data('id');
            let message = $(this).data('message');
            formAlertWarning(id, message)
        })

        function formAlertWarning(id, message) {
            Swal.fire({
                title: '{{ translate('warning') }}!',
                imageUrl: '{{asset('public/assets/admin-module/img/warning.png')}}',
                text: message,
                showCloseButton: true,
                showConfirmButton: false
            })
        }

        $(".restore-data").on('click', function() {
            let route = $(this).data('route');
            let message = $(this).data('message');
            restoreData(route, message)
        })

        function restoreData(route, message) {
            Swal.fire({
                title: '{{ translate('are_you_sure') }}?',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: 'let(--bs-primary)',
                cancelButtonText: '{{ translate('no') }}',
                confirmButtonText: '{{ translate('yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    window.location.href = route;
                }
            })
        }

        function loadPartialView(url, divId, data = null) {
            $.get({
                url: url,
                dataType: 'json',
                data: {
                    data
                },
                beforeSend: function() {
                    $('#resource-loader').show();
                },
                success: function(response) {
                    $(divId).empty().html(response)
                },
                complete: function() {
                    $('#resource-loader').hide();
                },
                error: function() {
                    $('#resource-loader').hide();
                    toastr.error('{{ translate('failed_to_load_data') }}')
                },
            });
        }

        function seenNotification(id) {
            $.get({
                url: '{{ route('admin.seen-notification') }}',
                dataType: 'json',
                data: {
                    id: id
                },
                complete: function() {
                    $('#resource-loader').hide();
                    if (id == 0) {
                        location.reload()
                    }
                },
            });
        }

        function getNotifications() {
            $.get({
                url: '{{ route('admin.get-notifications') }}',
                dataType: 'json',
                success: function(response) {
                    $('#notification').empty().html(response)
                    commonFunctionRecall();
                },
                error: function(xhr, status, error) {},
            });
        }

        function commonFunctionRecall() {
            $('.seen-notification').on('click', function() {
                let id = $(this).data('value');
                seenNotification(id)
            })
        }

        getNotifications();
        setInterval(getNotifications, 15000);
    </script>
{{--Remove non-numeric characters from the input value  with type="tel" --}}
<script>
    document.addEventListener('input', function(event) {

        if (event.target.tagName === 'INPUT' && event.target.type === 'tel') {
            validateNumbers(event.target);
        }
    });

    function validateNumbers(input) {
        input.value = input.value.replace(/\D/g, '');
    }
</script>
    @stack('script')
    @stack('script2')

</body>

</html>
