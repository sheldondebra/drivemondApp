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
use Modules\PromotionManagement\Interfaces\BannerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BannerSetupController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private BannerInterface $banner)
    {
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        $this->authorize('promotion_view');

        $validated = $request->validate([
            'value' => 'in:all,active,inactive',
            'query' => 'sometimes',
            'search' => 'sometimes'
        ]);
        $banners = $this->banner->get(limit: paginationLimit(), offset: 1, attributes: $validated);
        return view('promotionmanagement::admin.banner-setup.index', [
            'banners' => $banners,
            'value' => $request->value ?? 'all',
            'search' => $request->search
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $this->authorize('promotion_add');

        $validated = $request->validate([
            'banner_title' => 'required',
            'short_desc' => 'required',
            'time_period' => 'required',
            'redirect_link' => 'required',
            'start_date' => 'exclude_if:time_period,all_time|required|after_or_equal:today',
            'end_date' => 'exclude_if:time_period,all_time|required|after_or_equal:start_date',
            'banner_image' => 'required|mimes:png,jpg,jpeg'
        ]);


        $this->banner->store(attributes: $validated);

        Toastr::success(BANNER_STORE_200['message']);
        return back();
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $this->authorize('promotion_edit');

        $banner = $this->banner->getBy(column: 'id', value: $id);

        return view('promotionmanagement::admin.banner-setup.edit', compact('banner'));
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
            'banner_title' => 'required',
            'short_desc' => 'required',
            'time_period' => 'required',
            'redirect_link' => 'required',
            'start_date' => 'exclude_if:time_period,all_time|required|after_or_equal:today',
            'end_date' => 'exclude_if:time_period,all_time|required|after_or_equal:start_date',
            'banner_image' => 'sometimes'
        ]);

        $this->banner->update(attributes: $validated, id: $id);

        Toastr::success(BANNER_UPDATE_200['message']);
        return back();

    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy($id)
    {
        $this->authorize('promotion_delete');

        $this->banner->destroy($id);

        Toastr::success(BANNER_DESTROY_200['message']);
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
        $model = $this->banner->update(attributes: $validated, id: $request->id);

        return response()->json($model);
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

        $banner = $this->banner->get(limit: 9999999999999999, offset: 1, attributes: $attributes);

        $data = $banner->map(function ($item) {

            return [
                'id' => $item['id'],
                'banner_title' => $item['name'],
                "image" => $item['image'],
                'position' => $item['display_position'],
                'redirect_link' => $item['redirect_link'],
                "total_redirection" => $item['total_redirection'],
                "group" => $item['banner_group'],
                'time_period' => $item['time_period'] == 'all_time' ? 'All Time' : $item['start_date'] . ' To ' . $item['end_date'],
                "is_active" => $item['is_active'],
                "created_at" => $item['created_at'],
            ];
        });

        return exportData($data, $request['file'], 'promotionmanagement::admin.banner-setup.print');
    }

    /**
     * @param Request $request
     * @return Application|Factory|View|Response|string|StreamedResponse
     */
    public function log(Request $request): View|Factory|Response|StreamedResponse|string|Application
    {
        $this->authorize('promotion_log');

        $request->merge([
            'logable_type' => 'Modules\PromotionManagement\Entities\BannerSetup',
        ]);
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
        $banners = $this->banner->trashed(['search' => $search]);

        return view('promotionmanagement::admin.banner-setup.trashed', compact('banners', 'search'));
    }

    /**
     * @param $id
     * @return RedirectResponse
     */
    public function restore($id): RedirectResponse
    {
        $this->authorize('super-admin');

        $this->banner->restore($id);

        Toastr::success(DEFAULT_RESTORE_200['message']);
        return redirect()->route('admin.promotion.banner-setup.index');

    }

    public function permanentDelete($id){
        $this->authorize('super-admin');
        $this->banner->permanentDelete(id: $id);
        Toastr::success(BANNER_DESTROY_200['message']);
        return back();
    }
}
