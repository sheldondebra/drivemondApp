@extends('adminmodule::layouts.master')

@section('title', translate('customer_Levels'))

@section('content')
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="row g-4">
                <div class="col-12">
                    <h2 class="fs-22 mt-4 text-capitalize">{{ translate('level_list') }}</h2>

                    <div class="d-flex flex-wrap justify-content-end align-items-center my-3 gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted text-capitalize">{{ translate('total_levels') }} : </span>
                            <span class="text-primary fs-16 fw-bold">{{ $levels->total() }}</span>
                        </div>
                    </div>

                    <div class="tab-content">
                        <div class="tab-pane fade active show" id="all-tab-pane" role="tabpanel">
                            <div class="card">
                                <div class="table-responsive mt-3">
                                    <table class="table table-borderless align-middle table-hover text-nowrap">
                                        <thead class="table-light align-middle text-capitalize">
                                            <tr>
                                                <th class="sl">{{ translate('SL') }}</th>
                                                <th class="level_name -center">{{ translate('level_name') }}</th>
                                                <th class="total_ride_complete text-center">{{ translate('total') }}
                                                    <br>{{ translate('ride_complete') }}
                                                </th>
                                                <th class="total_earning_amount text-center">{{ translate('total') }}
                                                    <br> {{ translate('earning_amount') }}
                                                    ({{ session()->get('currency_symbol') ?? '$' }})
                                                </th>
                                                <th class="text-center maximum_cancellation_rate">
                                                    {{ translate('maximum') }}
                                                    <br> {{ translate('cancellation_rate') }}
                                                </th>
                                                <th class="text-center total_customer">
                                                    {{ translate('total_customer') }}</th>
                                                <th class="action text-center">{{ translate('action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($levels as $key => $level)
                                                <?php
                                                $totalTrip = 0;
                                                $completedTrip = 0;
                                                $cancelledTrip = 0;
                                                $trip_earning = 0;
                                                ?>
                                                @forelse($level->users as $user)
                                                    @php($totalTrip = $user->customerTrips?->count())
                                                    @php($completedTrip += $user->customerTrips?->where('current_status', 'completed')->count())
                                                    @php($cancelledTrip += $user->customerTrips?->where('current_status', 'cancelled')->count())
                                                    @php($earning = $user->customerTrips?->where('current_status', 'completed')->sum('total_fare'))
                                                @empty
                                                @endforelse
                                                <tr id="hide-row-{{ $level->id }}">
                                                    <td class="sl">{{ $levels->firstItem() + $key }}</td>
                                                    <td class="level_name text-center">
                                                        <div class="media gap-2 gap-xl-3 align-items-center max-content">
                                                            <img width="45" class="dark-support"
                                                                src="{{ onErrorImage(
                                                                    $level?->image,
                                                                    asset('storage/app/public/customer/level') . '/' . $level?->image,
                                                                    asset('public/assets/admin-module/img/media/level5.png'),
                                                                    'customer/level/',
                                                                ) }}"
                                                                alt="">
                                                            <div class="media-body">{{ $level->name }}</div>
                                                        </div>
                                                    </td>
                                                    <td class="total_ride_complete text-center">{{ $completedTrip }}</td>
                                                    <td class="total_earning_amount text-center">{{ $earning ?? 0 }}</td>
                                                    @if ($totalTrip == 0)
                                                        @php($totalTrip = 1)
                                                    @endif
                                                    <td class="text-center maximum_cancellation_rate">
                                                        {{ ($cancelledTrip / $totalTrip) * 100 }}</td>
                                                    <td class="text-center total_customer">{{ $level->users_count }}</td>
                                                    <td class="action">
                                                        <div class="d-flex justify-content-center gap-2 align-items-center">
                                                            <a href="{{ route('admin.customer.level.restore', ['id' => $level->id]) }}"
                                                                class="btn btn-outline-primary btn-action">
                                                                <i class="bi bi-arrow-repeat"></i>
                                                            </a>
                                                            <button data-id="delete-{{ $level->id }}"
                                                                data-message="{{ translate('want_to_permanent_delete_this_level?') }} {{ translate('you_cannot_revert_this_action') }}"
                                                                type="button"
                                                                class="btn btn-outline-danger btn-action form-alert form-alert">
                                                                <i class="bi bi-trash-fill"></i>
                                                            </button>

                                                            <form
                                                                action="{{ route('admin.customer.level.permanent-delete', ['id' => $level->id]) }}"
                                                                id="delete-{{ $level->id }}" method="post">
                                                                @csrf
                                                                @method('delete')
                                                            </form>
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

                                <div
                                    class="table-bottom d-flex flex-column flex-sm-row justify-content-sm-between align-items-center gap-2">

                                    <div
                                        class="d-flex flex-wrap align-items-center justify-content-center justify-content-sm-end gap-3 gap-sm-4">
                                        {!! $levels->links() !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <!-- End Main Content -->
@endsection

@push('script')
    <script>
        "use strict";
        loadPartialView('{{ route('admin.customer.level.statistics') }}', '#statistics')
    </script>
@endpush
