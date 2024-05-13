<?php

namespace Modules\UserManagement\Http\Controllers\Web\Admin\Driver;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\UserManagement\Interfaces\DriverInterface;
use Modules\UserManagement\Interfaces\DriverLevelInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DriverLevelController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected DriverLevelInterface $level,
        protected DriverInterface      $driver,
    )
    {
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Renderable
     */
    public function index(Request $request): Renderable
    {
        $this->authorize('user_view');

        $validated = $request->validate([
            'search' => 'sometimes',
            'query' => 'sometimes',
            'value' => 'sometimes',
        ]);
        $levels = $this->level->getLevelizedTrips($validated);


        return view('usermanagement::admin.driver.level.index', [
            'levels' => $levels,
            'search' => $request['search'],
            'value' => $request['value'] ?? 'all'
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create(): Renderable
    {
        $this->authorize('user_add');

        $levels = $this->level->getLevelizedTrips([]);
        $levelArray = $levels->pluck('sequence')->toArray();
        $sequence_array = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        $sequences = array_diff($sequence_array, $levelArray);

        return view('usermanagement::admin.driver.level.create', compact('sequences'));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return RedirectResponse|Renderable
     */
    public function store(Request $request): RedirectResponse|Renderable
    {
        $this->authorize('user_add');

        $validated = $request->validate([
            'sequence' => [
                'required', 'numeric',
                Rule::unique('user_levels', 'sequence')->where('user_type', 'driver'),
            ],
            'name' => [
                'required',
                Rule::unique('user_levels', 'name')->where('user_type', 'driver'),
            ],
            'reward_type' => 'required|in:no_rewards,wallet,loyalty_points',
            'reward_amount' => $request['reward_type'] == 'no_rewards' ? '' : 'required|numeric|gt:0',
            'image' => 'required|mimes:png',
            'targeted_ride' => 'required|numeric|gt:0',
            'targeted_ride_point' => 'required|numeric|gt:0',
            'targeted_amount' => 'required|numeric|gt:0',
            'targeted_amount_point' => 'required|numeric|gt:0',
            'targeted_cancel' => 'required|numeric|gt:0',
            'targeted_cancel_point' => 'required|numeric|gt:0',
            'targeted_review' => 'required|numeric|gt:0',
            'targeted_review_point' => 'required|numeric|gt:0',
        ]);

        $levels = $this->level->get(limit: 200, offset: 1);
        if (($levels->isEmpty()) && $request['sequence'] != 1) {
            Toastr::error(LEVEL_CREATE_403['message']);

            return back();
        }
        $this->level->store(attributes: $validated);

        Toastr::success(LEVEL_CREATE_200['message']);
        return redirect(route('admin.driver.level.index'));

    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id): Renderable
    {
        $this->authorize('user_edit');
        $level = $this->level
            ->getBy(column: 'id', value: $id);

        return view('usermanagement::admin.driver.level.edit', compact('level'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return RedirectResponse|Renderable
     */
    public function update(Request $request, $id): RedirectResponse|Renderable
    {
        $this->authorize('user_edit');
        $validated = $request->validate([
            'name' => 'required',
            'image' => 'sometimes|mimes:png',
            'targeted_ride' => 'required|numeric|gt:0',
            'targeted_ride_point' => 'required|numeric|gt:0',
            'targeted_amount' => 'required|numeric|gt:0',
            'targeted_amount_point' => 'required|numeric|gt:0',
            'targeted_cancel' => 'required|numeric|gt:0',
            'targeted_cancel_point' => 'required|numeric|gt:0',
            'targeted_review' => 'required|numeric|gt:0',
            'targeted_review_point' => 'required|numeric|gt:0',
        ]);
        $this->level->update(attributes: $validated, id: $id);

        Toastr::success(LEVEL_UPDATE_200['message']);
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

        $level = $this->level->getBy(column: 'id', value: $id, attributes: ['withCount' => 'users']);
        if ( $level->users_count > 0 ) {

            Toastr::error(LEVEL_DELETE_403['message']);
            return back();
        }
        $this->level->destroy(id: $id);

        Toastr::success(LEVEL_DELETE_200['message']);
        return back();
    }

    /**
     * Summary of updateStatus
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $this->authorize('user_edit');
        $driver = $this->level->update($request->all(), $request->id);
        return response()->json($driver);
    }

    /**
     * Summary of updateStatus
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        $data = $request->date_range;
        if ($request->has('date_range')) {
            $attributes['dates'] = getDateRange($data);
        } else {
            $attributes['dates'] = [];
        }
        $levels = $this->level->getLevelizedTrips($attributes, true);
        $drivers = $this->driver->getCount('id', 'user_type', 'driver', $attributes['dates']);
        return response()->json(view('usermanagement::admin.driver.level._statistics', compact('levels', 'drivers'))->render());
    }

    /**
     * @param Request $request
     * @return Application|Factory|View|Response|string|StreamedResponse
     */
    public function export(Request $request): View|Factory|Response|StreamedResponse|string|Application
    {
        $this->authorize('user_export');
        $attributes = [
            'query' => $request['query'],
            'value' => $request['value'],
        ];

        !is_null($request['search']) ? $attributes['search'] = $request['search'] : '';
        $levels = $this->level->getLevelizedTrips(attributes: $attributes, export: true);
        if ($levels->count() < 1) {

            Toastr::warning(NO_DATA_200['message']);
            return back();
        }

        $data = $levels->map(function ($item) {
            $totalTrip = 0;
            $completedTrip = 0;
            $cancelledTrip = 0;
            foreach ($item->users as $user) {
                $totalTrip = $user->driverTripsStatus->count();
                $completedTrip += $user->driverTripsStatus->whereNotNull('completed')->count();
                $cancelledTrip += $user->driverTripsStatus->whereNotNull('cancelled')->count();
                $earning = 0;
                foreach ($user->driverTripsStatus->whereNotNull('completed') as $st) {
                    $earning += $st->trip_request->paid_fare ?? 0;
                }
            }
            if ($totalTrip == 0) {
                $totalTrip = 1;
            }
            return [
                'id' => $item['id'],
                'name' => $item['name'],
                'completed_rides' => $completedTrip,
                'total_earning_amount' => $earning ?? 0,
                'max_cancellation_rate' => $cancelledTrip / $totalTrip * 100,
                'total_driver' => $item->users->count()
            ];
        });

        return exportData($data, $request['file'], 'usermanagement::admin.driver.level.print');
    }

    /**
     * @param Request $request
     * @return Application|Factory|View|Response|string|StreamedResponse
     */
    public function log(Request $request): View|Factory|Response|StreamedResponse|string|Application
    {
        $this->authorize('user_log');
        $request->merge([
            'logable_type' => 'Modules\UserManagement\Entities\UserLevel',
            'user_type' => 'customer'
        ]);

        return log_viewer($request->all());
    }

    public function trash(Request $request)
    {
        $this->authorize('super-admin');
        $search = $request->has('search ') ?? null;
        $levels = $this->level->trashed(attributes: ['search' => $search]);

        return view('usermanagement::admin.driver.level.trashed', compact('levels', 'search'));
    }

    /**
     * @param $id
     * @return RedirectResponse
     */
    public function restore($id): RedirectResponse
    {
        $this->authorize('super-admin');
        $this->level->restore($id);

        Toastr::success(DEFAULT_RESTORE_200['message']);
        return redirect()->route('admin.driver.level.index');

    }

    public function permanentDelete($id)
    {
        $this->authorize('super-admin');
        $this->level->permanentDelete(id: $id);
        Toastr::success(DRIVER_DELETE_200['message']);
        return back();
    }


}
