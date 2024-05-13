<?php

namespace Modules\UserManagement\Http\Controllers\Web\Admin\Employee;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Contracts\View\View;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Modules\UserManagement\Interfaces\AddressInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Modules\UserManagement\Interfaces\EmployeeInterface;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\AdminModule\Repositories\ActivityLogRepository;
use Modules\UserManagement\Interfaces\EmployeeRoleInterface;

class EmployeeController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private EmployeeInterface     $employee,
        private AddressInterface     $employeeAddress,
        private EmployeeRoleInterface $role,
        private ActivityLogRepository $activityLogs
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
            'value' => 'sometimes',
            'search' => 'sometimes',
            'query' => 'sometimes',
        ]);
        $employees = $this->employee
            ->get(limit: paginationLimit(), offset: 1, attributes: $validated, relations: ['role', 'moduleAccess']);

        return view('usermanagement::admin.employee.index', [
            'value' => $request['value'] ?? 'all',
            'search' => $request['search'],
            'employees' => $employees
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $this->authorize('user_add');
        $roles = $this->role->get(limit: 200, offset: 1, attributes: [
            'query' => 'is_active',
            'value' => 1
        ]);

        return view('usermanagement::admin.employee.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('user_add');
        DB::beginTransaction();
        $validated = $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8|max:17|unique:users,phone',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password',
            'profile_image' => 'required|image|mimes:jpeg,jpg,png,gif|max:10000',
            'identification_type' => 'required|in:passport,driving_licence,nid',
            'identification_number' => 'required',
            'identity_images' => 'required|array',
            'other_documents' => 'array',
            'identity_images.*' => 'image|mimes:jpeg,jpg,png,gif|max:10000',
            'role_id' => 'required',
            'address' => 'required',
            'permission' => 'array|required'
        ]);

        $employee = $this->employee->store(attributes: $validated);
        $address =[
            'user_id' => $employee->id,
            'address' => $request->address
        ];
        $this->employeeAddress->store($address);
        DB::commit();
        Toastr::success(EMPLOYEE_STORE_200['message']);
        return redirect(route('admin.employee.index'));

    }

    /**
     * Show the specified resource.
     * @param Request $request
     * @return Renderable
     */
    public function show(Request $request)
    {
        $this->authorize('user_view');
        $request->validate([
            'id' => 'required',
        ]);
        $search = $request['search'] ?? null;

        $attributes['logable_type'] = 'Modules\UserManagement\Entities\User';

        $attributes['logable_id'] = $request['id'];

        if ($request->has('search')) {
            $attributes['search'] = $request['search'];
        }

        $attributes['user_type'] = 'admin-employee';

        $logs = $this->activityLogs->get(attributes: $attributes);

        $employee = $this->employee->getBy(column: 'id', value: $request['id']);
        $roles = $this->role->get(limit: 200, offset: 1);

        return view('usermanagement::admin.employee.show', compact('employee', 'roles', 'logs', 'search'));
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id): Renderable
    {
        $this->authorize('user_edit');
        $employee = $this->employee->getBy(column: 'id', value: $id);
        $employeeAddress = $this->employeeAddress->getBy(column: 'user_id', value: $id);
        $roles = $this->role->get(limit: 200, offset: 1);
        $role = $this->role->getBy(column: 'id', value: $employee->role_id);

        return view('usermanagement::admin.employee.edit', compact('employee','employeeAddress', 'roles','role'));
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
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:8|max:17|unique:users,phone,' . $id,
            'password' => 'nullable|min:8',
            'confirm_password' => 'same:password',
            'profile_image' => 'sometimes|required|image|mimes:jpeg,jpg,png,gif|max:10000',
            'identification_type' => 'required|in:passport,driving_licence,nid',
            'identification_number' => 'required',
            'identity_images' => 'sometimes|required|array',
            'identity_images.*' => 'sometimes|image|mimes:jpeg,jpg,png,gif|max:10000',
            'address' => 'required',
            'permission' => 'array|required'
        ]);

        $this->employee
            ->update($request->all(), $id);
        $address = $this->employeeAddress->getBy(column: 'user_id', value: $id);
        $addressData =[
            'user_id' => $id,
            'address' => $request->address
        ];
        $this->employeeAddress->update($addressData,$address->id);

        Toastr::success(EMPLOYEE_UPDATE_200['message']);
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
        $this->employee
            ->destroy(id: $id);

        Toastr::success(EMPLOYEE_DELETE_200['message']);
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

        $employee = $this->employee
            ->update(attributes: $validated, id: $request->id);

        return response()->json($employee);
    }

    /**
     * @param Request $request
     * @return Application|Factory|View|Response|string|StreamedResponse
     */
    public function export(Request $request): View|Factory|Response|StreamedResponse|string|Application
    {
        $this->authorize('user_export');
        $validated = $request->validate([
            'value' => 'sometimes',
            'search' => 'sometimes',
            'query' => 'sometimes',
        ]);
        $employees = $this->employee
            ->get(limit: 500, offset: 1, attributes: $validated, relations: ['role']);

        $data = $employees->map(function ($item) {

            return [
                'name' => $item['first_name'] . ' ' . $item['last_name'],
                'email' => $item['email'],
                'phone' => $item['phone'],
                'identification_number' => $item['identification_number'],
                'identification_type' => $item['identification_type'],
                'user_type' => $item['user_type'],
                'role' => $item->role->name,
                'modules' => json_encode($item->role->modules),
                'status' => $item['is_active'] ? 'active' : 'inactive'
            ];
        });

        return exportData($data, $request['file'], 'usermanagement::admin.employee.print');
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
            'user_type' => 'admin-employee'
        ]);
        return log_viewer($request->all());
    }

    public function trash(Request $request)
    {
        $this->authorize('super-admin');
        $search = $request->has('search ') ?? null;
        $employees = $this->employee->trashed(attributes: ['search' => $search, 'relations' => ['role']]);
        return view('usermanagement::admin.employee.trashed', compact('employees', 'search'));

    }

    /**
     * @param $id
     * @return RedirectResponse
     */
    public function restore($id): RedirectResponse
    {
        $this->authorize('super-admin');
        $this->employee->restore($id);

        Toastr::success(DEFAULT_RESTORE_200['message']);
        return redirect()->route('admin.employee.index');

    }

    public function permanentDelete($id){
        $this->authorize('super-admin');
        $this->employee->permanentDelete(id: $id);
        Toastr::success(EMPLOYEE_DELETE_200['message']);
        return back();
    }
}
