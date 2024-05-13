<?php

namespace Modules\UserManagement\Http\Controllers\Web\New\Admin\Driver;

use App\Http\Controllers\BaseController;
use App\Service\BaseServiceInterface;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\TransactionManagement\Traits\TransactionTrait;
use Modules\UserManagement\Http\Requests\WithdrawMethodStoreOrUpdateRequest;
use Modules\UserManagement\Service\Interface\WithdrawMethodServiceInterface;
use Modules\UserManagement\Service\Interface\WithdrawRequestServiceInterface;

class WithdrawalController extends BaseController
{
    use AuthorizesRequests;

    protected $withdrawMethodService;
    protected $withdrawRequestService;

    public function __construct(WithdrawMethodServiceInterface $withdrawMethodService, WithdrawRequestServiceInterface $withdrawRequestService)
    {
        parent::__construct($withdrawMethodService);
        $this->withdrawMethodService = $withdrawMethodService;
        $this->withdrawRequestService = $withdrawRequestService;
    }

    public function index(?Request $request, string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        $this->authorize('user_view');
        $withdrawalMethods = $this->withdrawMethodService->index(criteria: $request?->all(), limit: paginationLimit(), offset: $request['page'] ?? 1, orderBy: ['created_at' => 'desc']);
        return view('usermanagement::admin.driver.withdraw.index', compact('withdrawalMethods'));
    }

    public function create()
    {
        $this->authorize('user_add');
        return view('usermanagement::admin.driver.withdraw.create');
    }

    public function store(WithdrawMethodStoreOrUpdateRequest $request)
    {
        $this->authorize('user_add');
        $this->withdrawMethodService->create(data: $request->validated());
        Toastr::success(DEFAULT_STORE_200['message']);
        return redirect()->route('admin.driver.withdraw-method.index');
    }


    public function edit($id)
    {
        $this->authorize('user_edit');
        $method = $this->withdrawMethodService->findOne(id: $id);
        return view('usermanagement::admin.driver.withdraw.edit', compact('method'));
    }


    public function update(WithdrawMethodStoreOrUpdateRequest $request, $id)
    {
        $this->authorize('user_edit');
        $withdrawalMethod = $this->withdrawMethodService->findOne(id: $id);
        if (!isset($withdrawalMethod)) {
            Toastr::error('Withdrawal method not found!');
            return back();
        }
        $this->withdrawMethodService->update(id: $id, data: $request->validated());
        Toastr::success(DEFAULT_UPDATE_200['message']);
        return back();
    }

    public function destroy($id)
    {
        $this->authorize('user_delete');
        $this->withdrawMethodService->delete(id: $id);
        Toastr::success(DEFAULT_DELETE_200['message']);
        return back();
    }


    public function statusUpdate(Request $request)
    {
        $this->authorize('user_edit');
        if ($request->status == 0) {
            return response()->json(['status' => 0], 403);
        }
        $status = $this->withdrawMethodService->defaultStatusChange(id: $request->id, data: $request->all());
        return response()->json(['status' => $status]);
    }

    public function activeUpdate(Request $request)
    {
        $this->authorize('user_edit');
        $withdrawMethod = $this->withdrawMethodService->findOne(id: $request->id);
        if ($request->status == 0 && $withdrawMethod->is_default == true) {
            return response()->json(['status' => ""], 403);
        }
        $status = $this->withdrawMethodService->statusChange(id: $request->id, data: $request->all());

        return response()->json(['status' => $status]);
    }

    public function withdrawRequests(Request $request)
    {
        $this->authorize('user_view');
        $value = $request->status ?? 'all';
        $criteria = [];
        if ($value == PENDING) {
            $criteria['is_approved'] = null;
        } elseif ($value == 'denied') {
            $criteria['is_approved'] = 0;
        } elseif ($value == 'approved') {
            $criteria['is_approved'] = 1;
        }
        $searchCriteria = [];
        if ($request->has('search')) {
            $searchCriteria = [
                'relations' => [
                    'user' => ['full_name', 'first_name', 'last_name'],
                ],
                'value' => $request->search,
            ];
        }
        $requests = $this->withdrawRequestService->getBy(criteria: $criteria, searchCriteria: $searchCriteria, orderBy: ['created_at' => 'desc'], limit: paginationLimit(), offset: $request['page'] ?? 1);
        return view('usermanagement::admin.driver.withdraw.requests', compact('requests'));


    }

    public function requestDetails($id)
    {
        $this->authorize('user_view');
        $request = $this->withdrawRequestService->findOne(id: $id, relations: ['user' => []]);
        return view('usermanagement::admin.driver.withdraw.details', compact('request'));
    }

    public function action($id, Request $request)
    {
        $this->authorize('user_edit');
        $request->validate([
            'is_approved' => 'in:0,1',
            'rejection_cause'=>'nullable|max:2000'
        ]);
        $this->withdrawRequestService->update(id: $id, data: $request->all());
        return redirect(route('admin.driver.withdraw.requests'));
    }
}
