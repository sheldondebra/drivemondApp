<?php

namespace Modules\UserManagement\Http\Controllers\Web\Admin\Driver;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\TransactionManagement\Traits\TransactionTrait;
use Modules\UserManagement\Entities\WithdrawMethod;
use Modules\UserManagement\Interfaces\WithdrawalMethodInterface;
use Modules\UserManagement\Interfaces\WithdrawRequestInterface;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class WithdrawalController extends Controller
{
    use TransactionTrait, AuthorizesRequests;

    public function __construct(
        private WithdrawalMethodInterface $method,
        private WithdrawMethod            $withdrawalMethod,
        private WithdrawRequestInterface  $request
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

        $request->validate([
            'type' => 'in:all,active,inactive',
        ]);
        $search = $request->search;
        $value = $request->value ?? 'all';
        if ($request->has('value')) {
            $request->merge([
                'query' => 'is_active',
                'value' => $request->value
            ]);
        }
        $withdrawalMethods = $this->method->get(limit: 9999999, offset: 1, attributes: $request->all());

        return view('usermanagement::admin.driver.withdraw.index', compact('withdrawalMethods', 'search', 'value'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $this->authorize('user_add');
        return view('usermanagement::admin.driver.withdraw.create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $this->authorize('user_add');
        $request->validate([
            'method_name' => 'required',
            'field_type' => 'required|array',
            'field_name' => 'required|array',
            'placeholder_text' => 'required|array',
            'is_default' => 'in:0,1 ',
        ]);
        $method_fields = [];
        foreach ($request->field_name as $key => $field_name) {
            $method_fields[] = [
                'input_type' => $request->field_type[$key],
                'input_name' => strtolower(str_replace(' ', "_", $request->field_name[$key])),
                'placeholder' => $request->placeholder_text[$key],
                'is_required' => isset($request['is_required']) && isset($request['is_required'][$key]),
            ];
        }
        $default_method = $this->method->getBy(column: 'is_default', value: true);
        $attributes = [
            'method_name' => $request->method_name,
            'method_fields' => $method_fields,
            'is_default' => $request->has('is_default') && is_null($default_method),
        ];
        $this->method->store($attributes);

        Toastr::success(DEFAULT_STORE_200['message']);
        return redirect()->route('admin.driver.withdraw-method.index');
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('usermanagement::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $this->authorize('user_edit');
        $method = $this->method->getBy(column: 'id', value: $id);
        return view('usermanagement::admin.driver.withdraw.edit', compact('method'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $this->authorize('user_edit');
        $request->validate([
            'method_name' => 'required',
            'field_type' => 'required|array',
            'field_name' => 'required|array',
            'placeholder_text' => 'required|array',
            'is_default' => 'in:0,1 ',
        ]);

        $withdrawalMethod = $this->withdrawalMethod->find($request['id']);

        if (!isset($withdrawalMethod)) {
            Toastr::error('Withdrawal method not found!');
            return back();
        }

        $method_fields = [];
        foreach ($request->field_name as $key => $field_name) {
            $method_fields[] = [
                'input_type' => $request->field_type[$key],
                'input_name' => strtolower(str_replace(' ', "_", $request->field_name[$key])),
                'placeholder' => $request->placeholder_text[$key],
                'is_required' => isset($request['is_required']) && isset($request['is_required'][$key]) ? 1 : 0,
            ];
        }

        $withdrawalMethod->method_name = $request->method_name;
        $withdrawalMethod->method_fields = $method_fields;
        $withdrawalMethod->is_default = $request->has('is_default') && $request->is_default == '1' ? 1 : 0;
        $withdrawalMethod->save();

        if ($request->has('is_default') && $request->is_default == '1') {
            $this->withdrawalMethod->where('id', '!=', $withdrawalMethod->id)->update(['is_default' => 0]);
        }

        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy($id)
    {
        $this->authorize('user_delete');
        $this->method->destroy($id);

        Toastr::success(DEFAULT_DELETE_200['message']);
        return back();
    }


    public function statusUpdate(Request $request)
    {
        $this->authorize('user_edit');
        $success = $this->method->AjaxDefaultStatusUpdate($request->id);
        return response()->json(['success' => $success,]);
    }

    public function activeUpdate(Request $request)
    {
        $this->authorize('user_edit');
        $status = $this->method->AjaxActiveStatusUpdate($request->id);
        return response()->json(['status' => $status,]);
    }

    public function requests(Request $request)
    {
        $this->authorize('user_view');
        $value = $request->value ?? 'all';
        if ($value == PENDING) {
            $is_approved = null;
        } elseif ($value == 'denied') {
            $is_approved = 0;
        } elseif ($value == 'approved') {
            $is_approved = 1;
        } else {
            $is_approved = 'all';
        }
        $requests = $this->request->get(limit: paginationLimit(), offset: 1, attributes: ['value' => $is_approved, 'query' => 'is_approved']);

        return view('usermanagement::admin.driver.withdraw.requests', compact('requests', 'value'));


    }

    public function requestDetails($id)
    {
        $this->authorize('user_view');
        $request = $this->request->getBy(column: 'id', value: $id, attributes: ['relations' => 'user']);
        return view('usermanagement::admin.driver.withdraw.details', compact('request'));

    }

    public function action($id, Request $request)
    {
        $this->authorize('user_edit');
        $request->validate([
            'is_approved' => 'in:0,1'
        ]);

        $data = $this->request->getBy(column: 'id', value: $id, attributes: ['relations' => 'user']);
        $attributes = [
            'column' => 'id',
            'is_approved' => $request->is_approved,
        ];
        if (!is_null($request->rejection_cause)) {
            $attributes['rejection_cause'] = $request->rejection_cause;
        }
        DB::beginTransaction();
        if ($request->is_approved == 0) {
            $this->withdrawRequestCancelTransaction($data->user, $data->amount, $data);
        } else {
            $this->withdrawRequestAcceptTransaction($data->user, $data->amount, $data);
        }
        $this->request->update(attributes: $attributes, id: $id);
        DB::commit();
        if ($request->is_approved == 0) {
            sendDeviceNotification(fcm_token: $data->user->fcm_token,
                title: translate('withdraw_request_rejected'),
                description: translate(('admin_has_rejected_your_withdraw_request' . ($data->rejection_cause != null ? ', because ' . $data->rejection_cause : ' .'))),
                ride_request_id: $request['trip_request_id'],
                action: 'withdraw_rejected',
                user_id: $data->user->id
            );
        } else {
            sendDeviceNotification(fcm_token: $data->user->fcm_token,
                title: translate('withdraw_request_approved'),
                description: translate('admin_has_approved_your_withdraw_request'),
                ride_request_id: $request['trip_request_id'],
                action: 'withdraw_approved',
                user_id: $data->user->id
            );
        }

        return redirect(route('admin.driver.withdraw.requests'));
    }
}
