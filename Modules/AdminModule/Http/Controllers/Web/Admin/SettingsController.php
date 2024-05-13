<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\UserManagement\Interfaces\CustomerInterface;
use Modules\UserManagement\Interfaces\EmployeeInterface;
use Modules\UserManagement\Repositories\EmployeeRepository;

class SettingsController extends Controller
{

    public function __construct(
        private EmployeeInterface $employee
    )
    {
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {

        return view('adminmodule::profile-settings');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('adminmodule::create');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
        ]);
        $request->merge([
            'column' => 'id'
        ]);
        $this->employee->update(attributes: $request->all(), id:  $id);

        Toastr::success(translate(DEFAULT_200['message']));
        return back();

    }
}
