<?php

namespace Modules\PromotionManagement\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\PromotionManagement\Interfaces\CoupounInterface;
use Modules\UserManagement\Interfaces\CustomerLevelInterface;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CouponSetupController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private CoupounInterface       $coupon,
        private CustomerLevelInterface $customerLevel)
    {
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return Renderable
     */
    public function index(Request $request): Renderable
    {
        $this->authorize('promotion_view');

        $validated = $request->validate([
            'value' => 'in:all,active,inactive',
            'query' => 'sometimes',
            'search' => 'sometimes'
        ]);

        $dateRange = $request->query('date_range');

        $attributes = [];

        $data = $request->date_range;

        if ($request->has('date_range')) {
            $attributes['dates'] = getDateRange($data);
        } else {
            $attributes['dates'] = getDateRange('today');
        }

        $startDate = $attributes['dates']['start'];
        $endDate = $attributes['dates']['end'];

        $cardValues = $this->coupon->getCardValues($data);

        $analytics = $this->coupon->getAnalytics($data);

        $coupons = $this->coupon->get(limit: paginationLimit(), offset: 1, attributes: $validated);

        return view('promotionmanagement::admin.coupon-setup.index', [
            'coupons' => $coupons,
            'value' => $request->value ?? 'all',
            'search' => $request->search,
            'cardValues' => $cardValues,
            'label' => $analytics[0],
            'data' => $analytics[1],
            'dateRangeValue' => $dateRange,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create(): Renderable
    {
        $this->authorize('promotion_add');

        $levels = $this->customerLevel->get(limit: 100, offset: 1);
        return view('promotionmanagement::admin.coupon-setup.create', compact('levels'));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('promotion_add');

        $request->validate([
            'coupon_title' => 'required|max:50',
            'short_desc' => 'required|max:255',
            'coupon_code' => 'required|unique:coupon_setups,coupon_code|max:30',
            'user_id' => 'required',
            'user_level_id' => 'required|string',
            'limit_same_user' => 'required|gt:0',
            'coupon_type' => 'required',
            'amount_type' => 'required',
            'coupon' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    $amountType = $request->input('amount_type');
                    $minTripAmount = $request->input('minimum_trip_amount');
                    $couponAmount = $request->input('coupon');
                    if ($amountType === 'amount' && $value <= 0) {
                        $fail('The coupon amount  value must be gather than 0 ');
                    }
                    if ($amountType === 'percentage' && $value <= 0) {
                        $fail('The coupon percent value must be gather than 0 ');
                    }

                    if ($amountType === 'percentage' && $value > 100) {
                        $fail('The coupon percent value must be less than 100% ');
                    }
                    if ($amountType !== 'percentage' && $couponAmount >= $minTripAmount) {
                        $fail('Coupon amount is not equal or more than minimum trip amount');
                    }
                },
            ],
            'minimum_trip_amount' => 'required|gt:0',
            'max_coupon_amount' => $request->input('amount_type') == 'percentage' ? 'required|numeric|gt:0' : '',
            'start_date' => 'required|after:after_or_equal:today',
            'end_date' => 'required|after_or_equal:start_date',
            'coupon_rules' => 'required|in:default,area_wise,vehicle_category_wise',
            'areas' => 'array',
            'categories' => 'array'
        ]);


        if ($request->user_id == 'Select customer' && $request->user_level_id) {

            Toastr::error('please select customer or user level');
            return back();
        }
        if ($request->user_id != 'Select customer') {
            $request->request->remove('user_level_id');
        } else {
            $request->request->remove('user_id');
        }
        if ($request->coupon_rules == 'area_wise' && !($request->areas)) {

            Toastr::error(DEFAULT_FAIL_200['message']);
            return redirect()->back();

        }

        if ($request->coupon_rules == 'vehicle_category_wise' && !($request->categories)) {

            Toastr::error(DEFAULT_FAIL_200['message']);
            return redirect()->back();
        }

        $this->coupon->store(attributes: $request->all());

        Toastr::success(COUPON_STORE_200['message']);
        return redirect()->route('admin.promotion.coupon-setup.index');
    }

    /**
     * Show the form for editing the specified resource.
     * @param string $id
     * @return Renderable
     */
    public function edit(string $id): Renderable
    {
        $this->authorize('promotion_edit');

        $relations = [ 'categories', 'customer', 'level'];
        $coupon = $this->coupon->getBy(column: 'id', value: $id, attributes: $relations);

        return view('promotionmanagement::admin.coupon-setup.edit', compact('coupon'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $this->authorize('promotion_edit');

        $validated = $request->validate([
            'coupon_title' => 'required',
            'short_desc' => 'required',
            'limit_same_user' => 'required',
            'amount_type' => 'required',
            'coupon' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    $amountType = $request->input('amount_type');
                    $minTripAmount = $request->input('minimum_trip_amount');
                    $couponAmount = $request->input('coupon');
                    if ($amountType === 'amount' && $value <= 0) {
                        $fail('The coupon amount  value must be gather than 0 ');
                    }
                    if ($amountType === 'percentage' && $value <= 0) {
                        $fail('The coupon percent value must be gather than 0 ');
                    }

                    if ($amountType === 'percentage' && $value > 100) {
                        $fail('The coupon percent value must be less than 100% ');
                    }
                    if ($amountType !== 'percentage' && $couponAmount >= $minTripAmount) {
                        $fail('Coupon amount is not equal or more than minimum trip amount');
                    }
                },
            ],
            'coupon_type' => 'string',
            'minimum_trip_amount' => 'required',
            'max_coupon_amount' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
        ]);

        $this->coupon->update(attributes: $validated, id: $id);

        Toastr::success(COUPON_UPDATE_200['message']);
        return redirect()->route('admin.promotion.coupon-setup.index');
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy($id)
    {
        $this->authorize('promotion_view');

        $this->coupon->destroy($id);

        Toastr::success(COUPON_DESTROY_200['message']);
        return back();
    }

    /**
     * Update the status specified resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        $this->authorize('promotion_edit');

        $validated = $request->validate([
            'status' => 'boolean'
        ]);
        $model = $this->coupon->update(attributes: $validated, id: $request->id);

        return response()->json($model);
    }

    /**
     * Download the  specified resource in storage.
     * @param Request $request
     * @return mixed
     */
    public function download(Request $request): mixed
    {
        $this->authorize('promotion_export');

        $model = $this->coupon->download(attributes: $request->all());
        if ($request->file == 'excel') {
            return (new FastExcel($model))->download(time() . '-file.xlsx');
        }

        if ($request->file == 'csv') {
            return (new FastExcel($model))->download(time() . '-file.csv');
        }
    }

    /**
     * @param Request $request
     * @return Application|Factory|View|Response|string|StreamedResponse
     */
    public function export(Request $request): View|Factory|Response|StreamedResponse|string|Application
    {
        $this->authorize('promotion_export');

        $attributes = [
            'relations' => ['level'],
            'query' => $request['query'],
            'value' => $request['value'],
        ];

        !is_null($request['search']) ? $attributes['search'] = $request['search'] : '';

        $discount = $this->coupon->download(attributes: $attributes);
        $data = $discount->map(function ($item) {

            return [
                'id' => $item['id'],
                'name' => $item['name'],
                'description' => $item['description'],
                'user_id' => $item['user_id'],
                'user_level_id' => $item['user_level_id'],
                'min_trip_amount' => $item['min_trip_amount'],
                "max_coupon_amount" => $item['max_coupon_amount'],
                "coupon" => $item['coupon'],
                "amount_type" => $item['amount_type'],
                "coupon_type" => $item['coupon_type'],
                "coupon_code" => $item['coupon_code'],
                "limit" => $item['limit'],
                "start_date" => $item['start_date'],
                "end_date" => $item['end_date'],
                "rules" => $item['rules'],
                "total_used" => $item['total_used'],
                "total_amount" => $item['total_amount'],
                "duration_in_days" => $item['duration_in_days'],
                "avg_amount" => $item['avg_amount'],
                "is_active" => $item['is_active'],
                "created_at" => $item['created_at'],
            ];
        });

        return exportData($data, $request['file'], 'promotionmanagement::admin.coupon-setup.print');
    }

    /**
     * @param Request $request
     * @return Application|Factory|View|Response|string|StreamedResponse
     */
    public function log(Request $request): View|Factory|Response|StreamedResponse|string|Application
    {
        $this->authorize('promotion_log');

        $request->merge(['logable_type' => 'Modules\PromotionManagement\Entities\CouponSetup']);

        return log_viewer($request->all());
    }


    /**
     * @param Request $request
     * @return View
     */
    public function trashed(Request $request): View
    {
        $this->authorize('super-admin');

        $search = $request->has('search') ? $request->search : null;
        $coupons = $this->coupon->trashed(['search' => $search]);

        return view('promotionmanagement::admin.coupon-setup.trashed', compact('coupons', 'search'));
    }

    /**
     * @param $id
     * @return RedirectResponse
     */
    public function restore($id): RedirectResponse
    {
        $this->authorize('super-admin');

        $this->coupon->restore($id);

        Toastr::success(DEFAULT_RESTORE_200['message']);
        return redirect()->route('admin.promotion.coupon-setup.index');
    }

    public function permanentDelete($id)
    {
        $this->authorize('super-admin');
        $this->coupon->permanentDelete(id: $id);
        Toastr::success(COUPON_DESTROY_200['message']);
        return back();
    }

}

