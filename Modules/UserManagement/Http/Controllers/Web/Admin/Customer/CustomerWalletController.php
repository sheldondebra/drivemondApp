<?php

namespace Modules\UserManagement\Http\Controllers\web\Admin\Customer;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\DB;
use Modules\TransactionManagement\Interfaces\TransactionInterface;
use Modules\UserManagement\Interfaces\UserAccountInterface;

class CustomerWalletController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private TransactionInterface $transaction,
        private UserAccountInterface $customerAccount,
    )
    {
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        $this->authorize('user_view');
        $validated = $request->validate([
            'search' => 'sometimes',
            'query' => 'sometimes',
            'value' => 'sometimes|in:all',
            'data' => 'in:all_time,this_week,last_week,this_month,last_month,last_15_days,this_year,last_year,custom_date',
            'start' => $request['data'] == 'custom_date' ? 'required' : '',
            'end' => $request['data'] == 'custom_date' ? 'required' : '',
        ]);

        if ($request->data == 'custom_date') {
            $data['start'] = $request->start;
            $data['end'] = $request->end;

        } else {
            $data = $request->data;
        }

        if ($request->has('data')) {
            $request->merge([
                'dates' => getDateRange($data),
            ]);
        }

        $request->merge([
            'transaction_type' => ['fund_by_admin']
        ]);

        //params
        $queryParams = [];
        $queryParams['account_type'] = 'wallet_balance';
        $search = $request['search'];
        $queryParams['search'] = $search;
        if ($request->has('user_id')) {
            if ($request->user_id != "all") {
                $queryParams['user_id'] = $request['user_id'];
            }
        }
        if ($request->has('data')) {
            $queryParams['dates'] = getDateRange($data);
        }
        if ($request->has('data') && $request['data'] == 'custom_date') {
            $queryParams['start'] = $request['start'];
            $queryParams['end'] = $request['end'];
        }

        $transactions = $this->transaction
            ->get(limit: paginationLimit(), offset: 1, attributes: $queryParams, relations: ['user']);
        return view('usermanagement::admin.customer.wallet.index', [
            'search' => $request['search'],
            'value' => $request['value'],
            'transactions' => $transactions,
            'queryParams' => $queryParams
        ]);
    }


    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->authorize('user_add');
        $validated = $request->validate([
            'customer_id' => 'sometimes',
            'amount' => 'required|numeric|gt:0',
            'reference' => 'max:50',
        ]);
        DB::beginTransaction();
        $this->customerAccount->store(attributes: $validated);
        Toastr::success(CUSTOMER_FUND_STORE_200['message']);
        DB::commit();
        return redirect()->back();
    }

    public function export(Request $request)
    {
        $this->authorize('user_export');
        $attributes = [
            'relations' => ['user'],
            'query' => $request['query'],
            'value' => $request['value'],
        ];

        if ($request->has('data') && $request->data == 'custom_date') {
            $data['start'] = $request->start;
            $data['end'] = $request->end;
        } else {
            $data = $request->data;
        }

        if ($request->has('data')) {
            $attributes['dates'] = getDateRange($data);
        }

        if ($request->has('customer_id')) {
            $attributes['customer_id'] = $request->customer_id;
        }

        $attributes['transaction_type'] = 'fund_by_admin';

        !is_null($request['search']) ? $attributes['search'] = $request['search'] : '';
        $roles = $this->transaction->get(limit: 9999999999999999, offset: 1, attributes: $attributes);
        $exportDatas = $roles->map(function ($item) {
            return [
                'id' => $item['id'],
                'transaction_id' => $item['id'],
                'reference' => $item['trx_ref_id'],
                'transaction_date' => $item['created_at'],
                'transaction_to' => $item->user?->first_name . ' ' . $item->user?->last_name,
                'credit' => $item['credit'],
                'balance' => $item['balance'],
            ];
        });

        return exportData($exportDatas, $request['file'], 'usermanagement::admin.customer.wallet.print');
    }


}
