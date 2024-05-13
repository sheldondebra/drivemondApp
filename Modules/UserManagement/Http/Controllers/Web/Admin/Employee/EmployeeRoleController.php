<?php

namespace Modules\UserManagement\Http\Controllers\Web\Admin\Employee;

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
use Modules\AdminModule\Repositories\ActivityLogRepository;
use Modules\UserManagement\Interfaces\EmployeeRoleInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EmployeeRoleController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private EmployeeRoleInterface $role)
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
            'query' => 'sometimes',
            'value' => 'sometimes',
            'search' => 'sometimes',
        ]);

        $roles = $this->role->get(limit: paginationLimit(), offset: 1, attributes: $validated);

        return view('usermanagement::admin.employee.role.index', [
            'roles'=> $roles,
            'value' => $request['value'] ?? 'all',
            'search' => $request['search']
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('usermanagement::create');
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
            'name' => 'required',
            'modules' => 'required'
        ]);

        $this->role->store($request->all());

        Toastr::success(ROLE_STORE_200['message']);
        return back();

    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id): Renderable
    {
        return view('usermanagement::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id): Renderable
    {
        $this->authorize('user_edit');
        $role = $this->role->getBy(column: 'id', value:  $id);
        return view('usermanagement::admin.employee.role.edit', compact('role'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable|RedirectResponse
     */
    public function update(Request $request, $id): Renderable|RedirectResponse
    {
        $this->authorize('user_edit');
        $request->validate([
            'name' => 'required',
        ]);

        $this->role->update(attributes: $request->all(), id: $id);
        return redirect(route('admin.employee.role.index'));
    }

    /**
     * Remove the specified resource from storage.
     * @param string $id
     * @return RedirectResponse|Renderable
     */
    public function destroy(string $id): RedirectResponse|Renderable
    {
        $this->authorize('user_delete');
        $this->role->destroy($id);
        Toastr::success(ROLE_DESTROY_200['message']);
        return back();
    }


    /**
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $this->authorize('user_edit');
        $validated = $request->validate([
            'status' => 'boolean',
        ]);
        $role = $this->role->update(attributes: $validated, id: $id);
        return response()->json($role);
    }

    public function getRoles(Request $request)
    {
        $role = $this->role->getBy(column: 'id', value: $request->id);

        return response()->json(
            ['view' => view('usermanagement::admin.employee.partials._employee_roles', compact('role'))->render()], 200);
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

        !is_null($request['search'])? $attributes['search'] = $request['search'] : '';

        $roles = $this->role->get(limit: 500, offset: 1, attributes: $attributes);
        $data = $roles->map(function ($item){

            return [
                'id' => $item['id'],
                'name' => $item['name'],
                'modules' => json_encode($item->modules),
                'status' => $item['is_active'] ? 'active' : 'inactive'
            ];
        });

        return exportData($data, $request['file'], 'usermanagement::admin.employee.role.print');
    }

    /**
     * @param Request $request
     * @return Application|Factory|View|Response|string|StreamedResponse
     */
    public function log(Request $request): View|Factory|Response|StreamedResponse|string|Application
    {
        $this->authorize('user_log');
        $request->merge(['logable_type' => 'Modules\UserManagement\Entities\Role']);

        return log_viewer($request->all());
    }


}
