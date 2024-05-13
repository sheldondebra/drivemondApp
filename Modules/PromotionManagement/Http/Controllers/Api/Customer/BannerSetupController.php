<?php

namespace Modules\PromotionManagement\Http\Controllers\Api\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\PromotionManagement\Interfaces\BannerInterface;
use Modules\PromotionManagement\Transformers\BannerResource;

class BannerSetupController extends Controller
{
    private BannerInterface $banner;

    public function __construct(BannerInterface $banner)
    {
        $this->banner = $banner;
    }

    /**
     * Display a listing of the resource.
     * @return JsonResponse
     */
    public function list(Request $request):JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000'
        ]);

        if ($validator->fails()) {
            return response()->json(responseFormatter(DEFAULT_400, null, null, null, errorProcessor($validator)), 400);
        }

        $isActive = ['query' => 'is_active', 'value' => '1'];
        $banner = $this->banner->get(limit: $request['limit'], offset: $request['offset'],dynamic_page: true, attributes:$isActive);
        $data = BannerResource::collection($banner);

        return response()->json(responseFormatter(DEFAULT_200, $data,$request['limit'], $request['offset']));

    }

    public function RedirectionCount(Request $request)
    {
        $banner = $this->banner->getBy(column:'id', value:$request->banner_id);
        if(!is_null($banner)){
            $banner->total_redirection = $banner->total_redirection + 1;
            $banner->save();
            return response()->json(responseFormatter(DEFAULT_STORE_200, $banner->total_redirection));
        }

        return response()->json(responseFormatter(DEFAULT_404));
    }
}
