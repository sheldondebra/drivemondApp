@extends('adminmodule::layouts.master')

@section('title', translate('withdraw_method_list'))

@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="row g-4">
            <div class="col-12">
                <h2 class="fs-22 mt-4 text-capitalize">{{translate('withdraw_method_list')}}</h2>
                <div class="d-flex flex-wrap justify-content-between align-items-center my-3 gap-3">
                    <ul class="nav nav--tabs p-1 rounded bg-white" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a href="{{url()->current()}}?status=all" class="nav-link
                                {{ !request()->has('status') || request()->get('status') =='all'? 'active' : '' }}">{{translate('all')}}</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="{{url()->current()}}?status=active" class="nav-link
                                   {{ request()->get('status') =='active' ? 'active' : '' }}">{{translate('active')}}</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="{{url()->current()}}?status=inactive" class="nav-link
                                {{ request()->get('status') =='inactive' ? 'active' : '' }}">{{translate('inactive')}}</a>
                        </li>
                    </ul>

                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted text-capitalize">{{translate('total_methods')}} : </span>
                        <span class="text-primary fs-16 fw-bold"
                              id="total_record_count">{{$withdrawalMethods->total()}}</span>
                    </div>
                </div>

                <div class="tab-content">
                    <div class="tab-pane fade active show" id="all-tab-pane" role="tabpanel">
                        <div class="card card-body">
                            <div class="table-top d-flex flex-wrap gap-10 justify-content-between">
                                <form action="javascript:;" method="GET"
                                      class="search-form search-form_style-two">
                                    <div class="input-group search-form__input_group">
                                    <span class="search-form__icon">
                                        <i class="bi bi-search"></i>
                                    </span>
                                        <input type="search" name="search" value="{{ request()->get('search') }}" id="search"
                                               class="theme-input-style search-form__input"
                                               placeholder="{{translate('search_here_by_Method_Name')}}">
                                    </div>
                                    <button type="submit" class="btn btn-primary search-submit" data-url="{{ url()->full() }}">{{translate('search')}}</button>
                                </form>

                                <div class="d-flex flex-wrap gap-3">
                                    @can('user_add')
                                        <a href="{{route('admin.driver.withdraw-method.create')}}" type="button"
                                           class="btn btn-primary text-capitalize">
                                            <i class="bi bi-plus fs-16"></i> {{translate('add_method')}}
                                        </a>
                                    @endcan
                                </div>
                            </div>
                            <div class="table-responsive mt-3">
                                <table id="datatable"
                                       class="table table-borderless align-middle table-hover text-nowrap">
                                    <thead class="table-light align-middle text-capitalize">
                                    <tr>
                                        <th>{{translate('SL')}}</th>
                                        <th>{{translate('method_name')}}</th>
                                        <th>{{ translate('method_fields') }}</th>
                                        @can('user_edit')
                                            <th>{{translate('status')}}</th>
                                        @endcan
                                        @can('user_edit')
                                            <th class="text-center">{{translate('default_method')}}</th>
                                        @endcan
                                        <th class="text-center">{{translate('action')}}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($withdrawalMethods as $key=>$withdrawalMethod)
                                        <tr id="hide-row-{{$withdrawalMethod->id}}">
                                            <td>{{$withdrawalMethods->firstitem()+$key}}</td>
                                            <td>{{$withdrawalMethod['method_name']}}</td>
                                            <td>
                                                @foreach($withdrawalMethod['method_fields'] as $key=>$method_field)
                                                    <span
                                                        class="badge badge-success opacity-75 fz-12 border border-white">
                                                <b>Name:</b> {{$method_field['input_name'] }} |
                                                <b>Type:</b> {{ $method_field['input_type'] }} |
                                                <b>Placeholder:</b> {{ $method_field['placeholder'] }} |
                                                <b>Is Required:</b> {{ $method_field['is_required'] ? translate('yes') : translate('no') }}
                                            </span><br/>
                                                @endforeach
                                            </td>
                                            @can('user_edit')
                                                <td>
                                                    <label class="switcher">
                                                        <input class="switcher_input status-change"
                                                               data-url="{{ route('admin.driver.withdraw-method.active-status-update') }}"
                                                               id="{{ $withdrawalMethod->id }}"
                                                               type="checkbox" {{$withdrawalMethod->is_active?'checked':''}}>
                                                        <span class="switcher_control"></span>
                                                    </label>
                                                </td>
                                            @endcan
                                            @can('user_edit')
                                                <td>
                                                    <div class="d-flex justify-content-center">
                                                        <label class="switcher">
                                                            <input type="checkbox" class="switcher_input default-status"
                                                                   data-url="{{route('admin.driver.withdraw-method.default-status-update')}}"
                                                                   id="{{$withdrawalMethod->id}}" {{$withdrawalMethod->is_default?'checked':''}}>
                                                            <span class="switcher_control"></span>
                                                        </label>
                                                    </div>
                                                </td>
                                            @endcan
                                            <td>
                                                <div class="d-flex justify-content-center gap-2">
                                                    @can('user_edit')
                                                        <a href="{{route('admin.driver.withdraw-method.edit',[$withdrawalMethod->id])}}"
                                                           class="btn btn-outline-warning btn-action">
                                                            <i class="bi bi-pen"></i>
                                                        </a>
                                                    @endcan
                                                    @can('user_delete')
                                                        @if(!$withdrawalMethod->is_default)
                                                            <a class="btn btn-outline-danger btn-action form-alert"
                                                               href="javascript:"
                                                               title="{{translate('Delete')}}"
                                                               data-id="delete-{{ $withdrawalMethod->id }}" data-message="{{ translate('want_to_delete_this_item?') }}">
                                                                <i class="bi bi-trash-fill"></i>
                                                            </a>
                                                            <form
                                                                action="{{route('admin.driver.withdraw-method.delete',[$withdrawalMethod->id])}}"
                                                                method="post" id="delete-{{$withdrawalMethod->id}}">
                                                                @csrf @method('delete')
                                                            </form>
                                                        @endif
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="14">
                                                <div class="d-flex flex-column justify-content-center align-items-center gap-2 py-3">
                                                    <img src="{{ asset('public/assets/admin-module/img/empty-icons/no-data-found.svg') }}" alt="" width="100">
                                                    <p class="text-center">{{translate('no_data_available')}}</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="table-responsive mt-4">
                                <div class="px-4 d-flex justify-content-center justify-content-md-end">
                                    <!-- Pagination -->
                                    {{$withdrawalMethods->links()}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

