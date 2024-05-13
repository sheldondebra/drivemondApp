<?php

namespace Modules\UserManagement\Http\Controllers\Web\Admin\Driver;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Contracts\View\View;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Modules\TripManagement\Interfaces\TripRequestInterfaces;
use Modules\UserManagement\Entities\AppNotification;
use Illuminate\Contracts\Support\Renderable;
use Modules\UserManagement\Interfaces\AppNotificationInterface;
use Illuminate\Contracts\Foundation\Application;
use Modules\UserManagement\Interfaces\DriverInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\UserManagement\Interfaces\DriverLevelInterface;
use Modules\TransactionManagement\Interfaces\TransactionInterface;

class DriverController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private DriverInterface       $driver,
        private DriverLevelInterface  $level,
        private TransactionInterface  $transaction,
        private TripRequestInterfaces $tripRequest,
    )
    {
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request): Renderable
    {
        $this->authorize('user_view');
        $request->validate([
            'value' => 'sometimes',
            'query' => 'sometimes',
            'search' => 'sometimes'
        ]);

        $request->merge(['relations' => ['level', 'driverTrips', 'driverTripsStatus', 'lastLocations.zone']]);
        $drivers = $this->driver->get(limit: paginationLimit(), offset: 1, attributes: $request->all());

        return view('usermanagement::admin.driver.index', [
            'drivers' => $drivers,
            'value' => $request['value'] ?? 'all',
            'search' => $request['search'],
            'query' => $request['query'],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create(): Renderable
    {
        $this->authorize('user_add');
        return view('usermanagement::admin.driver.create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('user_add');
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8|max:17|min:8|max:17|unique:users,phone',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password',
            'profile_image' => 'required|image|mimes:jpeg,jpg,png,gif|max:10000',
            'identification_type' => 'required|in:passport,driving_licence,nid',
            'identification_number' => 'required',
            'identity_images' => 'required|array',
            'other_documents' => 'array',
            'identity_images.*' => 'image|mimes:jpeg,jpg,png,gif|max:10000',
        ]);
        $firstLevel = $this->level->getFirstLevel();
        if (!$firstLevel) {

            Toastr::error(LEVEL_403['message']);
            return back();
        }
        $request->merge([
            'user_level_id' => $firstLevel->id
        ]);
        $this->driver->store($request->all());

        Toastr::success(DRIVER_STORE_200['message']);
        return redirect(route('admin.driver.index'));

    }

    /**
     * Show the specified resource.
     * @param int $id
     * @param Request $request
     * @return Renderable|RedirectResponse
     */
    public function show($id, Request $request): Renderable|RedirectResponse
    {
        $this->authorize('user_view');
        $driver = $this->driver->getBy(column: 'id', value: $id, attributes: [
            'relations' => ['userAccount', 'receivedReviews', 'driverTrips', 'driverDetails', 'driverTrips']
        ]);

        if (!$driver) {

            Toastr::warning(DEFAULT_404['message']);
            return back();
        }

        $tab = $request->query('tab') ?? 'overview';

        $reviewedBy = $request->query('reviewed_by');

        //driver rate info
        $driverRateInfoData = $this->driver_rate_info($driver);

        $commonData = [
            'collectable_amount' => $driver->userAccount->payable_balance,
            'pending_withdraw' => $driver->userAccount->pending_balance,
            'already_withdrawn' => $driver->userAccount->total_withdrawn,
            'withdrawable_amount' => $driver->userAccount->receivable_balance,
            'total_earning' => $driver->userAccount->received_balance,
            'idle_rate_today' => $driverRateInfoData['idle_rate_today'],
            'avg_active_day' => $driverRateInfoData['avg_active_rate_by_day'],
            'driver_avg_earning' => $driverRateInfoData['driver_avg_earning'],
            'completed_trips' => $driverRateInfoData['completed_trips'],
            'cancelled_trips' => $driverRateInfoData['cancelled_trips'],
            'success_rate' => $driverRateInfoData['success_rate'],
            'cancel_rate' => $driverRateInfoData['cancel_rate'],
            'positive_review_rate' => $driverRateInfoData['positive_review_rate'],
            'driver' => $driver,
            'tab' => $tab,
        ];

        $otherData = [];

        if ($tab == 'overview') {
            $overviewData = $this->overview($driver);
            $otherData = [
                'totalMorningTime' => $overviewData['totalMorningTime'],
                'totalMiddayTime' => $overviewData['totalMiddayTime'],
                'totalEveningTime' => $overviewData['totalEveningTime'],
                'totalNightTime' => $overviewData['totalNightTime'],
                'total_active_hours' => $overviewData['total_active_hours'],
                'targeted_review_point' => $overviewData['targeted_review_point'],
                'targeted_cancel_point' => $overviewData['targeted_cancel_point'],
                'targeted_amount_point' => $overviewData['targeted_amount_point'],
                'targeted_ride_point' => $overviewData['targeted_ride_point'],
                'driver_lowest_fare' => $overviewData['driver_lowest_fare'],
                'driver_highest_fare' => $overviewData['driver_highest_fare'],
                'driver_level_point_goal' => $overviewData['driver_level_point_goal']
            ];

        } else if ($tab == 'vehicle') {
            if (!empty($driver->vehicle)) {
                //vehicle tab
                $vehicleTripCount = $driver->driverTrips()->where('current_status', 'completed')->where('vehicle_id', $driver->vehicle->id)->count();
                $vehicleRate = ($commonData['completed_trips'] > 0) ? ($vehicleTripCount / $commonData['completed_trips']) * 100 : 0;

                //parcel
                $parcelTripCount = $driver->driverTrips()->where('current_status', 'completed')->where('type', 'parcel')->where('vehicle_id', $driver?->vehicle?->id)->count();
                $parcelCompletedTrips = $driver->driverTrips()->where('current_status', 'completed')->where('type', 'parcel')->count();
                $parcelRate = ($parcelCompletedTrips > 0) ? ($parcelTripCount / $parcelCompletedTrips) * 100 : 0;
            } else {
                $vehicleRate = 0;
                $parcelRate = 0;
                $vehicleTripCount = 0;
            }

            $otherData = [
                'vehicle_trip_count' => $vehicleTripCount,
                'vehicle_rate' => $vehicleRate,
                'parcel_rate' => $parcelRate,
            ];

        } else if ($tab == 'trips') {
            $attributes['column'] = 'driver_id';
            $attributes['value'] = $id;
            $attributes['type'] = 'ride_request';
            !is_null($request['search']) ? $attributes['search'] = $request['search'] : '';
            $driverTrips = $this->tripRequest->get(limit: paginationLimit(), offset: 1, attributes: $attributes, relations: ['customer', 'driver', 'fee']);
            $otherData = [
                'trips' => $driverTrips,
                'search' => $request['search'],
            ];
        } else if ($tab == 'transaction') {
            $attributes = [];
            $attributes['user_id'] = $id;
            !is_null($request['search']) ? $attributes['search'] = $request['search'] : '';
            $transactions = $this->transaction
                ->get(limit: paginationLimit(), offset: 1, attributes: $attributes, relations: ['user']);
            $otherData = [
                'transactions' => $transactions,
                'search' => $request['search'],
            ];

        } else if ($tab == 'review') {
            $review_data = $this->review_information($driver, $reviewedBy);
            $otherData = [
                'customer_reviews' => $review_data['customer_reviews'],
                'driver_reviews' => $review_data['driver_reviews'],
                'one_star' => $review_data['one_star'],
                'two_star' => $review_data['two_star'],
                'four_star' => $review_data['four_star'],
                'five_star' => $review_data['five_star'],
                'three_star' => $review_data['three_star'],
                'avg_rating' => $review_data['avg_rating'],
                'total_rating' => $review_data['total_rating'],
                'reviews_count' => $review_data['reviews_count'],
                'total_review_count' => $review_data['total_review_count'],
                'reviewed_by' => $reviewedBy
            ];
        }

        return view('usermanagement::admin.driver.details', compact('driver', 'commonData', 'otherData'));

    }

    private function driver_rate_info($driver)
    {
        //driver active rate/ day
        $timeLog = $driver->latestTrack;
        if (!empty($timeLog)) {
            $totalActiveTime = $driver->timeTrack()->sum('total_online') ?? 0;
            $totalActiveHour = $totalActiveTime / 60;
            $toDate = Carbon::parse($driver->created_at);
            $fromDate = Carbon::today();
            $days = $toDate->diffInDays($fromDate);
            $avgActiveRateByDay = (($totalActiveHour / ($days > 0 ? $days : 1)) / 24) * 100;
            $onlineHours = $timeLog['total_online'] / 60;
            $idleOnlineHours = $timeLog['total_idle'] / 60;
            $idleRateToday = ($idleOnlineHours / ($onlineHours > 0 ? $onlineHours : 1)) * 100;
        } else {
            $avgActiveRateByDay = 0;
            $idleRateToday = 0;
        }

        $driverTrips = $driver->driverTrips()
            ->where('driver_id', $driver->id)
            ->whereIn('current_status', ['completed', 'cancelled'])
            ->where('payment_status', PAID)
            ->get();

        $driverAvgEarning = ($driver?->userAccount?->received_balance + $driver?->userAccount?->receivable_balance) / (count($driverTrips) > 0 ? count($driverTrips) : 1);

        //Positive review rate
        $positiveReviewRate = $driver->receivedReviews()
            ->where('trip_type', 'ride_request')
            ->selectRaw('SUM(CASE WHEN rating IN (4,5) THEN 1 ELSE 0 END) / COUNT(*) * 100 AS positive_review_rate')
            ->value('positive_review_rate');

        //driver success rate
        $completedTrips = $driver->driverTrips()->where('current_status', 'completed')->count();
        $cancelledTrips = $driver->driverTrips()->where('current_status', 'cancelled')->count();
        $totalTrips = $driver->driverTrips()->whereIn('current_status', ['completed', 'cancelled', ONGOING])->count();

        $successRate = ($totalTrips > 0) ? (($totalTrips - $cancelledTrips) / $totalTrips) * 100 : 0;
        $cancelRate = ($totalTrips > 0) ? (($totalTrips - $completedTrips) / $totalTrips) * 100 : 0;

        return compact('idleRateToday', 'avgActiveRateByDay', 'driverAvgEarning',
            'positiveReviewRate', 'completedTrips', 'cancelledTrips', 'successRate', 'cancelRate');
    }

    private function review_information($driver, $reviewedBy)
    {
        if ($reviewedBy == 'customer') {
            $avgRating = $driver->receivedReviews()->avg('rating');
            $totalRating = $driver->receivedReviews()->sum('rating');
            $reviewsCount = $driver->receivedReviews()->whereNotNull('feedback')->count();
            $totalReviewCount = $driver->receivedReviews()->count();
            $reviews = $driver->receivedReviews()
                ->selectRaw('rating, COUNT(*) as count')
                ->groupBy('rating')
                ->pluck('count', 'rating')
                ->toArray();

            $oneStar = $reviews[1] ?? 0;
            $twoStar = $reviews[2] ?? 0;
            $threeStar = $reviews[3] ?? 0;
            $fourStar = $reviews[4] ?? 0;
            $fiveStar = $reviews[5] ?? 0;

        } elseif ($reviewedBy == 'driver') {
            $avgRating = $driver->givenReviews()->avg('rating');
            $totalRating = $driver->givenReviews()->sum('rating');
            $reviewsCount = $driver->givenReviews()->whereNotNull('feedback')->count();
            $totalReviewCount = $driver->givenReviews()->count();
            $reviews = $driver->givenReviews()
                ->selectRaw('rating, COUNT(*) as count')
                ->groupBy('rating')
                ->pluck('count', 'rating')
                ->toArray();

            $oneStar = $reviews[1] ?? 0;
            $twoStar = $reviews[2] ?? 0;
            $threeStar = $reviews[3] ?? 0;
            $fourStar = $reviews[4] ?? 0;
            $fiveStar = $reviews[5] ?? 0;
        }

        $customerReviews = $driver->receivedReviews()->with('givenUser')->paginate(paginationLimit());
        $driverReviews = $driver->givenReviews()->paginate(paginationLimit());

        return compact('customerReviews', 'driverReviews', 'oneStar', 'twoStar', 'threeStar', 'fourStar', 'fiveStar', 'avgRating',
            'totalRating', 'reviewsCount', 'totalReviewCount');
    }

    private function overview($driver)
    {
        $targetedAmountPoint = $targetedReviewPoint = $targetedCancelPoint = $targetedRidePoint = 0;
        // Calculate Morning time
        $totalMorningTime = $driver->timeLog()
            ->whereDate('created_at', Carbon::today())
            ->whereRaw("TIME(online) >= '06:00:00' AND TIME(offline) <= '11:59:59'")
            ->sum('online_time');


        // Calculate Midday time
        $totalMiddayTime = $driver->timeLog()
            ->whereDate('created_at', Carbon::today())
            ->whereRaw("TIME(online) >= '12:00:00' AND TIME(offline) <= '15:59:59'")
            ->sum('online_time');

        // Calculate Evening time
        $totalEveningTime = $driver->timeLog()
            ->whereDate('created_at', Carbon::today())
            ->whereRaw("TIME(online) >= '16:00:00' AND TIME(offline) <= '20:59:59'")
            ->sum('online_time');

        // Calculate Night time
        $totalNightTime = $driver->timeLog()
            ->whereDate('created_at', Carbon::today())
            ->where(function ($query) {
                $query->whereRaw("TIME(online) >= '21:00:00' AND TIME(offline) <= '23:59:59'");
            })
            ->orWhere(function ($query) {
                $query->whereRaw("TIME(online) >= '00:00:00' AND TIME(offline) <= '05:59:59'");
            })
            ->sum('online_time');


        //trip info of driver details
        $driverLowestFare = $driver->driverTrips()->whereIn('current_status', ['completed', 'cancelled'])->min('paid_fare');
        $driver_highest_fare = $driver->driverTrips()->whereIn('current_status', ['completed', 'cancelled'])->max('paid_fare');

        //driver details duty and review
        $total_active_min = $driver->timeTrack()->sum('total_online');
        $totalActiveHours = intdiv($total_active_min, 60) . ':' . ($total_active_min % 60);

        //driver level calculation
        $driverLevelPointGoal = $driver->level()->selectRaw('(targeted_ride + targeted_amount + targeted_cancel + targeted_review) as level_point')
            ->first()?->level_point;

        $driverLevel = $driver->level()->first();

        $driverLevelHistory = $driver->latestLevelHistory()->first();

        if (!empty($driverLevelHistory)) {
            if ($driverLevelHistory->ride_reward_status == 1) {
                $targetedRidePoint = $driverLevel->targeted_ride_point;
            } else {
                $targetedRidePoint = 0;
            }

            if ($driverLevelHistory->amount_reward_status == 1) {
                $targetedAmountPoint = $driverLevel->targeted_amount_point;
            } else {
                $targetedAmountPoint = 0;
            }

            if ($driverLevelHistory->cancellation_reward_status == 1) {
                $targetedCancelPoint = $driverLevel->targeted_cancel_point;
            } else {
                $targetedCancelPoint = 0;
            }

            if ($driverLevelHistory->reviews_reward_status == 1) {
                $targetedReviewPoint = $driverLevel->targeted_review_point;
            } else {
                $targetedReviewPoint = 0;
            }
        }

        return [
            'driver_highest_fare' => $driver_highest_fare,
            'driver_lowest_fare' => $driverLowestFare,
            'totalMorningTime' => $totalMorningTime,
            'totalMiddayTime' => $totalMiddayTime,
            'totalEveningTime' => $totalEveningTime,
            'totalNightTime' => $totalNightTime,
            'targeted_ride_point' => $targetedRidePoint,
            'targeted_amount_point' => $targetedAmountPoint,
            'targeted_cancel_point' => $targetedCancelPoint,
            'targeted_review_point' => $targetedReviewPoint,
            'driver_level_point_goal' => $driverLevelPointGoal,
            'total_active_hours' => $totalActiveHours,
        ];
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id): Renderable
    {
        $this->authorize('user_edit');
        $driver = $this->driver
            ->getBy(column: 'id', value: $id);

        return view('usermanagement::admin.driver.edit', compact('driver'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $this->authorize('user_edit');
        $validated = $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8|max:17|min:8|max:17|unique:users,phone,' . $id,
            'password' => !is_null($request['password']) ? 'min:8' : '',
            'confirm_password' => !is_null($request['password']) ? 'same:password' : '',
            'profile_image' => 'sometimes|image|mimes:jpeg,jpg,png,gif|max:10000',
            'identification_type' => 'required|in:passport,driving_licence,nid',
            'identification_number' => 'required',
            'identity_images' => 'sometimes|array',
            'other_documents' => 'sometimes|array',
            'identity_images.*' => 'sometimes|image|mimes:jpeg,jpg,png,gif|max:10000',
        ]);

        $this->driver->update($validated, $id);

        Toastr::success(DRIVER_UPDATE_200['message']);
        return back();
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy($id): RedirectResponse
    {
        $this->authorize('user_delete');
        $driver = $this->driver->getBy(column: 'id', value: $id);
        $driver->delete();

        Toastr::success(DRIVER_DELETE_200['message']);
        return back();

    }

    /**
     * Remove the specified resource from storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $this->authorize('user_edit');
        $driver = $this->driver->update($request->all(), $request->id);
        $driverNotification= AppNotification::where('user_id',$request->id)->where('action','account_approved')->get();
        if (count($driverNotification)==0){
            $push = getNotification('registration_approved');
            if ($request->status && $driver->fcm_token) {
                sendDeviceNotification(
                    fcm_token: $driver->fcm_token,
                    title: translate($push['title']),
                    description: translate($push['description']),
                    action: 'account_approved',
                    user_id: $driver->id
                );
            }
        }
        if ($driver->is_active == 0){
            foreach($driver->tokens as $token) {
                $token->revoke();
            }
        }
        return response()->json($driver);
    }

    /**
     * Remove the specified resource from storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllAjax(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'sometimes'
        ]);

        $drivers = $this->driver->get(limit: 8, offset: 1, attributes: $validated);

        $mapped = $drivers->map(function ($items) {
            return [
                'text' => $items['first_name'] . ' ' . $items['last_name'] . ' ' . '(' . $items['phone'] . ')',
                'id' => $items['id']
            ];
        });
        if ($request->all_driver) {
            $all_driver = (object)['id' => 0, 'text' => translate('all_driver')];
            $mapped->prepend($all_driver);
        }

        return response()->json($mapped);
    }

    public function getAllAjaxVehicle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'sometimes'
        ]);

        $drivers = $this->driver->getDriverWithoutVehicle(limit: 100, offset: 1, dynamic_page: true, attributes: $validated);

        $mapped = $drivers->map(function ($items) {
            return [
                'text' => $items['first_name'] . ' ' . $items['last_name'] . ' ' . '(' . $items['phone'] . ')',
                'id' => $items['id']
            ];
        });
        if ($request->all_driver) {
            $all_driver = (object)['id' => 0, 'text' => translate('all_driver')];
            $mapped->prepend($all_driver);
        }

        return response()->json($mapped);
    }

    public function statistics(Request $request)
    {
        $attributes = [];

        $data = $request->date_range;

        $attributes['dates'] = getDateRange('today');

        if ($request->has('date_range')) {
            $attributes['dates'] = getDateRange($data);
        }

        $analytics = $this->driver->getStatisticsData($attributes);

        $total = $analytics[0];
        $active = $analytics[1];
        $inactive = $analytics[2];
        $car = $analytics[3];
        $motor_bike = $analytics[4];

        return response()->json(view('usermanagement::admin.driver._statistics',
            compact('total', 'active', 'inactive', 'car', 'motor_bike'))->render());
    }

    /**
     * @param Request $request
     * @return Application|Factory|View|Response|string|StreamedResponse
     */
    public function export(Request $request): View|Factory|Response|StreamedResponse|string|Application
    {
        $this->authorize('user_export');
        $attributes = [
            'relations' => ['level'],
            // 'query' => $request['query'],
            // 'value' => $request['value'],
        ];

        !is_null($request['search']) ? $attributes['search'] = $request['search'] : '';
        !is_null($request['query']) ? $attributes['query'] = $request['query'] : '';
        !is_null($request['value']) ? $attributes['value'] = $request['value'] : '';

        $request->merge(['relations' => ['level', 'driverTrips', 'driverTripsStatus', 'lastLocations.zone']]);

        $roles = $this->driver->get(limit: 9999999999999999, offset: 1, dynamic_page: true, attributes: $request->all());

        $data = $roles->map(function ($item) {

            $count = 0;
            if (!is_null($item?->first_name)) {
                $count++;
            }
            if (!is_null($item?->last_name)) {
                $count++;
            }
            if (!is_null($item?->email)) {
                $count++;
            }
            if (!is_null($item->phone)) {
                $count++;
            }
            if (!is_null($item->gender)) {
                $count++;
            }
            if (!is_null($item->identification_number)) {
                $count++;
            }
            if (!is_null($item->identification_type)) {
                $count++;
            }
            if (!is_null($item->identification_image)) {
                $count++;
            }
            if (!is_null($item->other_documents)) {
                $count++;
            }
            if (!is_null($item->date_of_birth)) {
                $count++;
            }
            if (!is_null($item->profile_image)) {
                $count++;
            }

            $ids = $item->driverTripsStatus->whereNotNull('completed')->pluck('trip_request_id');
            $earning = $item->driverTrips->whereIn('id', $ids)->sum('paid_fare');

            return [
                'id' => $item['id'],
                'name' => $item['first_name'] . ' ' . $item['last_name'],
                'email' => $item['email'],
                'phone' => $item['phone'],
                'profile_status' => round(($count / 11) * 100),
                'level' => $item?->level->name ?? 'no level attached',
                'total_trip' => $item->driverTrips->count(),
                'earning' => $earning,
                'status' => $item['is_active'] ? 'active' : 'inactive',
            ];
        });

        return exportData($data, $request['file'], 'usermanagement::admin.driver.print');
    }

    public function driverTransactionExport(Request $request)
    {
        $attributes = [
            'search' => $request['search'],
        ];

        $attributes['rider_id'] = $request->id;

        $roles = $this->transaction->get(limit: 9999999999999999, offset: 1, attributes: $attributes, relations: ['user']);
        $exportDatas = $roles->map(function ($item) {
            return [
                'id' => $item['id'],
                'transaction_id' => $item['id'],
                'account' => $item['account'],
                'transaction_to' => $item->user?->first_name . ' ' . $item->user?->last_name,
                'debit' => $item['debit'],
                'credit' => $item['credit'],
                'last_balance' => $item['balance'],
            ];
        });

        return exportData($exportDatas, $request['file'], 'usermanagement::admin.driver.transaction.print');
    }

    /**
     * @param Request $request
     * @return Application|Factory|View|Response|string|StreamedResponse
     */
    public function log(Request $request): View|Factory|Response|StreamedResponse|string|Application
    {
        $this->authorize('user_log');
        $request->merge([
            'logable_type' => 'Modules\UserManagement\Entities\User',
            'user_type' => 'customer'
        ]);
        return log_viewer($request->all());

    }

    public function trash(Request $request)
    {
        $this->authorize('super-admin');
        $search = $request->has('search ') ?? null;
        $drivers = $this->driver->trashed(attributes: ['search' => $search, 'relations' => ['level', 'lastLocations.zone', 'driverTrips', 'driverTripsStatus']]);
        return view('usermanagement::admin.driver.trashed', compact('drivers', 'search'));

    }

    /**
     * @param $id
     * @return RedirectResponse
     */
    public function restore($id): RedirectResponse
    {
        $this->authorize('super-admin');
        $this->driver->restore($id);

        Toastr::success(DEFAULT_RESTORE_200['message']);
        return redirect()->route('admin.driver.index');

    }

    public function permanentDelete($id)
    {
        $this->authorize('super-admin');
        $this->driver->permanentDelete(id: $id);
        Toastr::success(DRIVER_DELETE_200['message']);
        return back();
    }
}
