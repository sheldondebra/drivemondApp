@extends('adminmodule::layouts.master')

@section('title', translate('withdrawalMethods'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="col-md-12">
                <form action="{{route('admin.driver.withdraw-method.store')}}" method="POST">
                    @csrf
                    <div class="card card-body">
                        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-3">
                            <h5 class="text-primary text-uppercase">{{translate('withdrawalMethods')}}</h5>
                            <button class="btn btn-primary text-capitalize" id="add-more-field">
                                <i class="tio-add"></i> {{translate('add_fields')}}
                            </button>
                        </div>
                        <div class="mb-4">
                            <label for="method_name" class="mb-2">{{translate('method_name')}} <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="method_name" id="method_name"
                                   placeholder="{{ translate('select_method_name') }}" value="" required>
                        </div>
                    </div>

                    <div class="mt-3">
                        <!-- HERE CUSTOM FIELDS WILL BE ADDED -->
                        <div id="custom-field-section">
                            <div class="card card-body">
                                <div class="row gy-4 align-items-center">
                                    <div class="col-md-6 col-12 mb-4">
                                        <label for="field_type" class="mb-2">{{translate('Input_Field_Type')}}
                                            <span class="text-danger">*</span></label>
                                        <select class="form-control js-select" name="field_type[]" required
                                                id="field_type">
                                            <option value="" selected
                                                    disabled>{{translate('Select_Input_Field_Type')}}</option>
                                            <option value="string">{{translate('string')}}</option>
                                            <option value="number">{{translate('number')}}</option>
                                            <option value="date">{{translate('date')}}</option>
                                            <option value="password">{{translate('password')}}</option>
                                            <option value="email">{{translate('email')}}</option>
                                            <option value="phone">{{translate('phone')}}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="mb-4">
                                            <label for="field_name" class="mb-2">{{translate('field_name')}} <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="field_name[]"
                                                   placeholder="{{ translate('select_field_name') }}" value="" required id="field_name">
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="mb-4">
                                            <label for="placeholder" class="mb-2">{{translate('placeholder_text')}}
                                                <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="placeholder_text[]"
                                                   placeholder="{{ translate('select_placeholder_text') }}" value="" id="placeholder"
                                                   required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="form-check mt-2">
                                            <label class="form-check-label" for="flexCheckDefault__0">
                                                {{translate('make_this_field_required')}}
                                            </label>
                                            <input class="form-check-input" type="checkbox" value="1"
                                                   name="is_required[0]" id="flexCheckDefault__0" checked>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex my-3">
                            <div class="form-check">
                                <label class="form-check-label" for="flexCheckDefaultMethod">
                                    {{translate('default_method')}}
                                </label>
                                <input class="form-check-input" type="checkbox" value="1" name="is_default"
                                       id="flexCheckDefaultMethod">
                            </div>
                        </div>

                        <!-- BUTTON -->
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary demo_check">
                                {{ translate('submit') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
@endsection


@push('script')
    <script>
        jQuery(document).ready(function ($) {
            counter = 1;

            $('#add-more-field').on('click', function (event) {
                if (counter < 15) {
                    event.preventDefault();

                    $('#custom-field-section').append(
                        `<div class="card card-body mt-3" id="field-row--${counter}">
                            <div class="row gy-4 align-items-center">
                                <div class="col-md-6 col-12">
                                    <select class="form-control js-select" name="field_type[]" required>
                                        <option value="" selected disabled>{{translate('Input_Field_Type')}} *</option>
                                        <option value="string">{{translate('string')}}</option>
                                        <option value="number">{{translate('number')}}</option>
                                        <option value="date">{{translate('date')}}</option>
                                        <option value="password">{{translate('password')}}</option>
                                        <option value="email">{{translate('email')}}</option>
                                        <option value="phone">{{translate('phone')}}</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="mb-4">
                                        <label class="mb-2">{{translate('field_name')}} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="field_name[]"
                                               placeholder="{{ translate('select_field_name') }}" value="" required>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="mb-4">
                                        <label class="mb-2">{{translate('placeholder_text')}} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="placeholder_text[]"
                                               placeholder="{{ translate('select_placeholder_text') }}" value="" required>
                                    </div>
                                </div>
                                <div class="col-md-6 col-12">
                                    <div class="form-check">
                                    <label class="form-check-label" for="flexCheckDefault__${counter}">
                                            {{translate('make_this_field_required')}}
                        </label>
                        <input class="form-check-input" type="checkbox" value="1" name="is_required[${counter}]" id="flexCheckDefault__${counter}" checked>

                    </div>
                </div>
                <div class="col-md-12 d-flex justify-content-end">
                    <span class="btn btn-danger remove-field" data-value="${counter}">
                                    <i class="tio-delete"></i>
                                        {{translate('Remove')}}
                        </span>
                    </div>
                </div>
            </div>`
                    );

                    $(".js-select").select2();
                    $(".remove-field").on('click', function () {
                        let fieldRowId = $(this).data('value')
                        $(`#field-row--${fieldRowId}`).remove();
                        counter--;
                    })

                    counter++;
                } else {
                    Swal.fire({
                        title: '{{translate('maximum_limit_reached')}}',
                        confirmButtonText: '{{translate('ok')}}',
                    });
                }
            })
            $('form').on('reset', function (event) {
                if (counter > 1) {
                    $('#custom-field-section').html("");
                    $('#method_name').val("");
                }

                counter = 1;
            })
        });
    </script>
@endpush
