@extends('adminmodule::layouts.master')

@section('title', translate('withdraw_requests'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="row g-4">
            <div class="col-12">
                <h2 class="fs-22 mt-4 text-capitalize">{{translate('withdraw_requests')}}</h2>
                <div class="d-flex flex-wrap justify-content-between align-items-center my-3 gap-3">
                    <ul class="nav nav--tabs p-1 rounded bg-white" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a href="{{url()->current()}}?status=all" class="nav-link
                                {{ !request()->has('status') || request()->get('status') =='all'? 'active' : '' }}">{{translate('all')}}</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="{{url()->current()}}?status=pending" class="nav-link
                                {{ request()->get('status') ==PENDING ? 'active' : '' }}">{{translate(PENDING)}}</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="{{url()->current()}}?status=approved" class="nav-link
                                   {{ request()->get('status') =='approved' ? 'active' : '' }}">{{translate('approved')}}</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="{{url()->current()}}?status=denied" class="nav-link
                                {{ request()->get('status') =='denied' ? 'active' : '' }}">{{translate('denied')}}</a>
                        </li>
                    </ul>

                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted text-capitalize">{{translate('total_requests')}} : </span>
                        <span class="text-primary fs-16 fw-bold">{{$requests->total()}}</span>
                    </div>
                </div>

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
                                       placeholder="{{translate('search_here_by_customer_name')}}">
                            </div>
                            <button type="submit" class="btn btn-primary search-submit" data-url="{{ url()->full() }}">{{translate('search')}}</button>
                        </form>
                    </div>
                    <div class="table-responsive mt-3">
                        <table id="datatable"
                               class="table table-borderless align-middle table-hover text-nowrap">
                            <thead class="table-light align-middle text-capitalize">
                            <tr>
                                <th>{{translate('SL')}}</th>
                                <th>{{translate('amount')}}</th>
                                <th>{{ translate('name') }}</th>
                                <th>{{translate('request_time')}}</th>
                                <th class="text-center">{{translate('status')}}</th>
                                <th class="text-center">{{translate('action')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($requests as $key=>$request)
                                <tr>
                                    <td>{{$requests->firstItem()+$key}}</td>
                                    <td>{{ set_currency_symbol($request['amount'] ?? 0) }}</td>
                                    <td>
                                        @if (isset($request->user))
                                            <a
                                                href="{{route('admin.driver.show',$request->user_id)}}"
                                                class="">{{ $request->user?->first_name . ' ' . $request->user?->last_name }}</a>
                                        @else
                                            <a href="#">{{translate('not_found')}}</a>
                                        @endif
                                    </td>
                                    <td>{{$request->created_at}}</td>
                                    <td class="text-center">
                                        @if(is_null($request->is_approved))
                                            <label class="badge badge-info">{{translate(PENDING)}}</label>
                                        @elseif($request->is_approved==1)
                                            <label class="badge badge-primary">{{translate('approved')}}</label>
                                        @elseif($request->is_approved==0)
                                            <label class="badge badge-danger">{{translate('denied')}}</label>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center">
                                            @if (isset($request->user))
                                                <a
                                                    href="{{route('admin.driver.withdraw.request-details', ['id' => $request->id])}}"
                                                    class="btn btn-outline-info btn-action"
                                                    title="{{translate('View')}}">
                                                    <i class="bi bi-eye-fill"></i>
                                                </a>
                                            @else
                                                <a href="#">
                                                    {{translate('account_disabled')}}
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <td colspan="6">
                                    <div class="d-flex flex-column justify-content-center align-items-center gap-2 py-3">
                                        <img src="{{ asset('public/assets/admin-module/img/empty-icons/no-data-found.svg') }}" alt="" width="100">
                                        <p class="text-center">{{translate('no_data_available')}}</p>
                                    </div>
                                </td>
                            @endforelse
                            </tbody>
                        </table>

                        <div class="table-responsive mt-4">
                            <div class="px-4 d-flex justify-content-center justify-content-md-end">
                                <!-- Pagination -->
                                {{$requests->links()}}
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

