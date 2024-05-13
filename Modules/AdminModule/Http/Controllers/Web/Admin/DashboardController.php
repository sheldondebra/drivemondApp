<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\AdminModule\Interfaces\DashboardInterface;
use Modules\TripManagement\Entities\TripRequest;
use Modules\TripManagement\Interfaces\TripRequestInterfaces;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Entities\UserAccount;
use Modules\ZoneManagement\Interfaces\ZoneInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{

    public function __construct(
        private TripRequestInterfaces $trip,
        private ZoneInterface $zone,
        private DashboardInterface $dashboard
    )
    {
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $zones = $this->zone->get(limit: 9999999, offset: 1, attributes: [
            'query' => 'is_active',
            'value' => 'active'
        ]);
        $transactions = \auth()->user()->transactions->sortByDesc('created_at')
            ->take(7);
        $card = [
            'account' => UserAccount::query()->adminAccount()->first(),
            'customers' => User::query()->userType('customer')->ofActive()->count(),
            'drivers' => User::query()->userType('driver')->ofActive()->count(),
        ];

        return view('adminmodule::dashboard', compact('zones', 'transactions', 'card'));
    }


    public function recentTripActivity()
    {
        $trips = $this->trip->get(limit: 5, offset: 1, attributes: ['relations' => 'customer', 'type' => 'ride_request']);

        return response()->json(view('adminmodule::partials.dashboard._recent-trip-activity', compact('trips'))->render());
    }


    public function leaderBoardDriver(Request $request)
    {
        $date = getDateRange($request->data);

        $leadDriver = $this->dashboard->leaderBoard(['type' =>'driver', 'date' => $date]);

        return response()->json(view('adminmodule::partials.dashboard._leader-board-driver', compact('leadDriver'))->render());
    }

    public function leaderBoardCustomer(Request $request)
    {
        $date = getDateRange($request->data);
        $leadCustomer = $this->dashboard->leaderBoard(['type' =>'customer', 'date' => $date]);
        return response()->json(view('adminmodule::partials.dashboard._leader-board-customer', compact('leadCustomer'))->render());
    }

    public function adminEarningStatistics(Request $request)
    {
        $date = getDateRange($request->date);
        $totalTripSorted = [];
        $completedTripSorted = [];

        for ($i = 0; $i < 15; $i++) {
            $totalTripSorted[$i] = 0;
            $completedTripSorted[$i] = 0;
        }

        $attributes = ['date' => $date, 'zone' => $request->zone];
        $totalTrips = $this->dashboard->adminEarning($attributes);
        $attributes['current_status'] = 'completed';
        $completedTrips = $this->dashboard->adminEarning($attributes);

        foreach ($totalTrips as $match) {

            if (strtotime($match['time']) <= strtotime('06:00:00')) {
                $totalTripSorted[0] += $match['sums'];
            } elseif (strtotime($match['time']) <= strtotime('08:00:00')) {
                $totalTripSorted[1] += $match['sums'];
            } elseif (strtotime($match['time']) <= strtotime('10:00:00')) {
                $totalTripSorted[2] += $match['sums'];
            } elseif (strtotime($match['time']) <= strtotime('12:00:00')) {
                $totalTripSorted[3] += $match['sums'];
            } elseif (strtotime($match['time']) <= strtotime('14:00:00')) {
                $totalTripSorted[4] += $match['sums'];
            } elseif (strtotime($match['time']) <= strtotime('16:00:00')) {
                $totalTripSorted[5] += $match['sums'];
            } elseif (strtotime($match['time']) <= strtotime('18:00:00')) {
                $totalTripSorted[6] += $match['sums'];
            } elseif (strtotime($match['time']) <= strtotime('20:00:00')) {
                $totalTripSorted[7] += $match['sums'];
            } elseif (strtotime($match['time']) <= strtotime('22:00:00')) {
                $totalTripSorted[8] += $match['sums'];
            } elseif (strtotime($match['time']) <= strtotime('24:00:00')) {
                $totalTripSorted[9] += $match['sums'];
            }
        }
        foreach ($completedTrips as $match) {

            if (strtotime($match['time']) <= strtotime('06:00:00')) {
                $completedTripSorted[0] += $match['sums'];
            } elseif (strtotime($match['time']) <= strtotime('08:00:00')) {
                $completedTripSorted[1] += $match['sums'];
            } elseif (strtotime($match['time']) <= strtotime('10:00:00')) {
                $completedTripSorted[2] += $match['sums'];
            } elseif (strtotime($match['time']) <= strtotime('12:00:00')) {
                $completedTripSorted[3] += $match['sums'];
            } elseif (strtotime($match['time']) <= strtotime('14:00:00')) {
                $completedTripSorted[4] += $match['sums'];
            } elseif (strtotime($match['time']) <= strtotime('16:00:00')) {
                $completedTripSorted[5] += $match['sums'];
            } elseif (strtotime($match['time']) <= strtotime('18:00:00')) {
                $completedTripSorted[6] += $match['sums'];
            } elseif (strtotime($match['time']) <= strtotime('20:00:00')) {
                $completedTripSorted[7] += $match['sums'];
            } elseif (strtotime($match['time']) <= strtotime('22:00:00')) {
                $completedTripSorted[8] += $match['sums'];
            } elseif (strtotime($match['time']) <= strtotime('24:00:00')) {
                $completedTripSorted[9] += $match['sums'];
            }
        }

        return response()->json( compact('totalTripSorted', 'completedTripSorted'));
    }


    public function zoneWiseStatistics(Request $request)
    {
        $date = getDateRange($request->date);
        $attributes = [
            'zone' => $request->zone,
            'date' => $date
        ];
        $data = $this->dashboard->zoneStatistics($attributes);

        return response()
            ->json(view('adminmodule::partials.dashboard._areawise-statistics', ['trips' => $data['records'], 'totalCount'=> $data['count']])
                ->render());
    }

    public function areaWiseTrip(Request $request)
    {
        $date = getDateRange($request->data);

        $leadDriver = TripRequest::query()
            ->whereNotNull('area_id')
            ->selectRaw('area_id, count(*) as total_records')
            ->groupBy('area_id')->orderBy('total_records', 'asc')
            ->when($date, function ($query) use ($date) {
                $query->whereBetween('created_at', [$date['start'], $date['end']]);
            })
            ->take(6)
            ->get();

        return response()->json(view('adminmodule::partials.dashboard._leader-board-driver', compact('leadDriver'))->render());
    }

    /**
     * @param Request $request
     * @return Application|Factory|View|Response|string|StreamedResponse
     */
    public function export(Request $request): View|Factory|Response|StreamedResponse|string|Application
    {
        $attributes = [
            'relations' => ['level']
        ];

        !is_null($request['search']) ? $attributes['search'] = $request['search'] : '';

        $roles = $this->trip->get(limit: 9999999999999999, offset: 1, attributes: $attributes);
        $data = $roles->map(function ($item) {
            return ['id' => $item['id'],];
        });

        return exportData($data, $request['file'], 'usermanagement::admin.customer.print');
    }

    /**
     * @param Request $request
     * @return Application|Factory|View|Response|string|StreamedResponse
     */
    public function log(Request $request): View|Factory|Response|StreamedResponse|string|Application
    {
        $request->merge([
            'logable_type' => 'Modules\UserManagement\Entities\User',
            'user_type' => 'customer'
        ]);
        return log_viewer($request->all());

    }

}
