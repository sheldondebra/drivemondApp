<?php

namespace Modules\UserManagement\Http\Controllers\Web\Admin\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Modules\TripManagement\Interfaces\TripRequestInterfaces;
use function PHPUnit\Framework\isFalse;
use Modules\UserManagement\Entities\User;
use Illuminate\Contracts\Support\Renderable;
use Modules\UserManagement\Entities\UserLevel;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Modules\UserManagement\Interfaces\CustomerInterface;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\UserManagement\Interfaces\CustomerLevelInterface;
use Modules\TripManagement\Repositories\TripRequestRepository;
use Modules\TransactionManagement\Interfaces\TransactionInterface;

class CustomerController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private CustomerInterface      $customer,
        private CustomerLevelInterface $level,
        private TransactionInterface   $transaction,
        private TripRequestInterfaces  $tripRequest,
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

        $request->validate([
            'type' => 'in:all,active,inactive',
        ]);
        $request->merge(['relations' => ["customerTrips", "level"]]);
        if ($request->has('value')) {
            $request->merge([
                'query' => 'user_level_id',
                'value' => $request->value
            ]);
        }

        $customers = $this->customer
            ->get(limit: paginationLimit(), offset: 1, attributes: $request->all());
        $levels = $this->level->get(limit: 9999, offset: 1, dynamic_page: true, attributes: ['orderBy' => 'asc']);

        return view('usermanagement::admin.customer.index', [
            'customers' => $customers,
            'search' => $request['search'],
            'value' => $request['value'] ?? 'all',
            'levels' => $levels
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create(): Renderable
    {
        $this->authorize('user_add');
        return view('usermanagement::admin.customer.create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return RedirectResponse|Renderable
     */
    public function store(Request $request): RedirectResponse|Renderable
    {
        $this->authorize('user_add');
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8|max:17|unique:users,phone',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password',
            'profile_image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:10000',
            'identification_type' => 'nullable|in:passport,driving_licence,nid',
            'identification_number' => 'nullable',
            'identity_images' => 'nullable|array',
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
        $this->customer->store($request->all());

        Toastr::success(DRIVER_STORE_200['message']);
        return redirect(route('admin.customer.index'));

    }

    /**
     * Show the specified resource.
     * @param $id
     * @return Renderable
     */
    public function show($id, Request $request): Renderable
    {
        $this->authorize('user_view');
        $customer = $this->customer
            ->getBy(column: 'id', value: $id, attributes: ['relations' => ['customerTrips']]);

        $attributes = [];

        $attributes['customer_id'] = $id;

        $commonData = [];
        $otherData = [];

        $tab = $request->query('tab') ?? 'overview';
        $reviewed_by = $request->query('reviewed_by');

        !is_null($request['search']) ? $attributes['search'] = $request['search'] : '';

        $customer_rate_info_data = $this->customer_rate_info($customer);
        $customer_total_reviews = $customer->givenReviews()->count();
        $commonData = [
            'customer_total_review_count' => $customer_total_reviews,
            'customer_lowest_fare' => $customer_rate_info_data['customer_lowest_fare'],
            'customer_highest_fare' => $customer_rate_info_data['customer_highest_fare'],
            'digitalPaymentPercentage' => $customer_rate_info_data['digitalPaymentPercentage'],
            'total_success_request' => $customer_rate_info_data['total_success_request'],
            'success_percentage' => $customer_rate_info_data['success_percentage'],
            'cancel_percentage' => $customer_rate_info_data['cancel_percentage'],
            'total_cancel_request' => $customer_rate_info_data['total_cancel_request'],
            'tab' => $tab,
            'customer' => $customer,
        ];

        if ($tab == 'overview') {
            $overview_data = $this->overview($customer);
            $otherData = [
                'customer_level_point_goal' => $overview_data['customer_level_point_goal'],
                'targeted_ride_point' => $overview_data['targeted_ride_point'],
                'targeted_amount_point' => $overview_data['targeted_amount_point'],
                'targeted_cancel_point' => $overview_data['targeted_cancel_point'],
                'targeted_review_point' => $overview_data['targeted_review_point']
            ];
        } else if ($tab == 'trips') {
            $attributes['column'] = 'customer_id';
            $attributes['value'] = $id;
            $attributes['type'] = 'ride_request';
            !is_null($request['search']) ? $attributes['search'] = $request['search'] : '';
            $customerTrips = $this->tripRequest->get(limit: paginationLimit(), offset: 1, attributes: $attributes);
            $otherData = [
                'trips' => $customerTrips,
                'search' => $request['search'],
            ];
        } else if ($tab == 'area') {
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
            $customer_given_reviews = $customer->givenReviews()->paginate(paginationLimit());
            $driver_reviews = $customer->receivedReviews()->paginate(paginationLimit());

            $otherData = [
                'customer_given_reviews' => $customer_given_reviews,
                'driver_reviews' => $driver_reviews,
                'reviewed_by' => $reviewed_by,
            ];
        } else if ('activitylog') {

        }
        return view('usermanagement::admin.customer.details', compact('customer', 'commonData', 'otherData'));
    }

    private function customer_rate_info($customer)
    {
        $totalRequests = $customer->customerTrips()->count();
        $totalDigitalPayments = $customer->customerTrips()->whereNotIn('payment_method', ['cash', 'wallet'])->count();
        $digitalPaymentPercentage = $totalRequests == 0 ? 0 : ($totalDigitalPayments / $totalRequests) * 100;

        //customer completed review count
        $customerCompletedReviewCount = $customer->givenReviews()
            ->whereHas('trip', function ($query) {
                $query->where('current_status', 'completed');
            })
            ->whereNotNull('feedback')->count() ?? 0;

        //total success rate
        $totalSuccessRequest = $customer->customerTrips()->where('current_status', 'completed')->count();
        $successPercentage = $totalSuccessRequest == 0 ? 0 : ($totalSuccessRequest / $totalRequests) * 100;

        //total cancel rate
        $totalCancelRequest = $customer->customerTrips()->where('current_status', 'cancelled')->count();
        $cancelPercentage = $totalCancelRequest == 0 ? 0 : ($totalCancelRequest / $totalRequests) * 100;

        //trip info of customer details
        $customerLowestFare = $customer->customerTrips()->where('current_status', 'completed')->min('paid_fare');
        $customerHighestFare = $customer->customerTrips()->where('current_status', 'completed')->max('paid_fare');

        return [
            'customer_completed_review_count' => $customerCompletedReviewCount,
            'customer_lowest_fare' => $customerLowestFare,
            'customer_highest_fare' => $customerHighestFare,
            'digitalPaymentPercentage' => $digitalPaymentPercentage,
            'total_success_request' => $totalSuccessRequest,
            'success_percentage' => $successPercentage,
            'cancel_percentage' => $cancelPercentage,
            'total_cancel_request' => $totalCancelRequest,
        ];
    }

    private function overview($customer)
    {

        //customer label calculation
        $targetedAmountPoint = $targetedReviewPoint = $targetedCancelPoint = $targetedRidePoint = 0;
        $customerLevelPointGoal = $customer->level()->selectRaw('(targeted_ride + targeted_amount + targeted_cancel + targeted_review) as level_point')
            ->first()?->level_point;

        $customerLevel = $customer->level()->first();

        $customer_level_history = $customer->latestLevelHistory()->first();

        if (!empty($customer_level_history)) {
            if ($customer_level_history->ride_reward_status == 1) {
                $targetedRidePoint = $customerLevel->targeted_ride_point;
            } else {
                $targetedRidePoint = 0;
            }

            if ($customer_level_history->amount_reward_status == 1) {
                $targetedAmountPoint = $customerLevel->targeted_amount_point;
            } else {
                $targetedAmountPoint = 0;
            }

            if ($customer_level_history->cancellation_reward_status == 1) {
                $targetedCancelPoint = $customerLevel->targeted_cancel_point;
            } else {
                $targetedCancelPoint = 0;
            }

            if ($customer_level_history->reviews_reward_status == 1) {
                $targetedReviewPoint = $customerLevel->targeted_review_point;
            } else {
                $targetedReviewPoint = 0;
            }
        }

        return [
            'customer_level_point_goal' => $customerLevelPointGoal,
            'targeted_ride_point' => $targetedRidePoint,
            'targeted_amount_point' => $targetedAmountPoint,
            'targeted_cancel_point' => $targetedCancelPoint,
            'targeted_review_point' => $targetedReviewPoint,
        ];
    }

    /**
     * Show the form for editing the specified resource.
     * @param string $id
     * @return Renderable
     */

    public function edit(string $id): Renderable
    {
        $this->authorize('user_edit');
        $customer = $this->customer
            ->getBy(column: 'id', value: $id);

        return view('usermanagement::admin.customer.edit', compact('customer'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param string $id
     * @return RedirectResponse
     */
    public function update(Request $request, string $id)
    {
        $this->authorize('user_edit');
        $validated = $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8|max:17|unique:users,phone,' . $id,
            'password' => !is_null($request['password']) ? 'min:8' : '',
            'confirm_password' => 'same:password',
            'profile_image' => 'sometimes|image|mimes:jpeg,jpg,png,gif|max:10000',
            'identification_type' => 'nullable|in:passport,driving_licence,nid',
            'identification_number' => 'nullable',
            'identity_images' => 'sometimes|array',
            'other_documents' => 'array',
            'identity_images.*' => 'image|mimes:jpeg,jpg,png,gif|max:10000',
        ]);

        $this->customer->update(attributes: $validated, id: $id);

        Toastr::success(DRIVER_UPDATE_200['message']);
        return back();
    }

    /**
     * Remove the specified resource from storage.
     * @param string $id
     * @return RedirectResponse|Renderable
     */
    public function destroy(string $id): RedirectResponse|Renderable
    {
        $this->authorize('user_delete');
        $this->customer->destroy(id: $id);

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
        $validated = $request->validate([
            'status' => 'required',
            'id' => 'required'
        ]);

        $customer = $this->customer->update(attributes: $validated, id: $request['id']);
        if ($customer->is_active == 0){
            foreach($customer->tokens as $token) {
                $token->revoke();
            }
        }
        return response()->json($customer);
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

        $customers = $this->customer
            ->get(limit: 8, offset: 1, dynamic_page: true, attributes: $validated);

        $mapped = $customers->map(function ($items) {
            return [
                'text' => $items['first_name'] . ' ' . $items['last_name'] . ' ' . '(' . $items['phone'] . ')',
                'id' => $items['id']
            ];
        });

        if ($request->all_customer) {
            $all_customer = (object)['id' => 'all', 'text' => translate('all_customer')];
            $mapped->prepend($all_customer);
        }

        return response()->json($mapped);
    }

    public function statistics()
    {
        $loyalLevelId = businessConfig('loyal_customer_tag', CUSTOMER_SETTINGS)->value['id'] ?? null;
        $loyalCustomer = 0;
        if ($loyalLevelId != null) {

            $loyalCustomer = User::where(['user_type' => 'customer'])->selectRaw('COUNT(*)')
                ->where(function ($query) use ($loyalLevelId) {
                    $query->whereHas('level', function ($subquery) use ($loyalLevelId) {
                        $subquery->where('id', $loyalLevelId);
                    })->orWhereHas('level', function ($subquery) use ($loyalLevelId) {
                        $subquery->where('sequence', '>', function ($subquery) use ($loyalLevelId) {
                            $subquery->select('sequence')
                                ->from('user_levels')
                                ->where('id', $loyalLevelId);
                        });
                    });
                })
                ->count();

        }

        $count = $this->customer->overviewCount();

        $registered = User::query()->where(['user_type' => 'customer'])->count();
        $inactive = User::query()->where(['user_type' => 'customer', 'is_active' => false])->count();
        $active = User::query()->where(['user_type' => 'customer', 'is_active' => true])->count();
        $verified = User::query()->where(['user_type' => 'customer'])->whereNotNull(['first_name', 'last_name'])->count();
        $new = User::query()->where(['user_type' => 'customer'])->whereBetween('created_at', [now()->subDays(7), now()->addDays(7)])->count();

        return response()->json(view('usermanagement::admin.customer._statistics',
            compact('registered', 'inactive', 'new', 'verified', 'loyalCustomer', 'active'))->render());

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
        ];

        !is_null($request['search']) ? $attributes['search'] = $request['search'] : '';

        $roles = $this->customer->get(limit: 9999999999999999, offset: 1, attributes: $attributes);
        $data = $roles->map(function ($item) {

            return [
                'id' => $item['id'],
                'name' => $item['first_name'] . ' ' . $item['last_name'],
                'email' => $item['email'],
                'phone' => $item['phone'],
                'gender' => $item['gender'],
                'identification' => $item['identification_type'] . ': ' . $item['identification_number'],
                'date_of_birth' => $item['date_of_birth'],
                'level' => $item->level->name ?? 'no level attached',
                'status' => $item['is_active'] ? 'active' : 'inactive',
                'created_at' => $item['created_at'],

            ];
        });

        return exportData($data, $request['file'], 'usermanagement::admin.customer.print');
    }

    public function customerTransactionExport($id, Request $request)
    {
        $attributes = [
            'query' => $request['query'],
            'value' => $request['value'],
            'search' => $request['search'],
        ];

        $attributes['customer_id'] = $id;

        $roles = $this->transaction->get(limit: 9999999999999999, offset: 1, attributes: $attributes);

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
        $customers = $this->customer->trashed(attributes: ['search' => $search, 'relations' => ['level', 'lastLocations.zone', 'customerTrips', 'customerTripsStatus']]);

        return view('usermanagement::admin.customer.trashed', compact('customers', 'search'));

    }

    /**
     * @param $id
     * @return RedirectResponse
     */
    public function restore($id): RedirectResponse
    {
        $this->authorize('super-admin');
        $this->customer->restore($id);

        Toastr::success(DEFAULT_RESTORE_200['message']);
        return redirect()->route('admin.customer.index');

    }

    public function permanentDelete($id)
    {
        $this->authorize('super-admin');
        $this->customer->permanentDelete(id: $id);
        Toastr::success(CUSTOMER_DELETE_200['message']);
        return back();
    }

}
