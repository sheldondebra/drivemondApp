<?php

namespace Modules\PromotionManagement\Http\Controllers\Api\Customer;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\PromotionManagement\Entities\CouponSetup;
use Modules\PromotionManagement\Interfaces\CoupounInterface;
use Modules\PromotionManagement\Transformers\CouponResource;
use Modules\TripManagement\Interfaces\TripRequestInterfaces;
use Modules\UserManagement\Interfaces\CustomerInterface;
use Modules\ZoneManagement\Entities\Area;

class CouponSetupController extends Controller
{
    private CoupounInterface $coupon;
    private TripRequestInterfaces $trip;

    public function __construct(CoupounInterface $coupon, TripRequestInterfaces $trip)
    {
        $this->coupon = $coupon;
        $this->trip = $trip;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric|min:1|max:200',
            'offset' => 'required|numeric|min:1|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json(responseFormatter(DEFAULT_400, null, null, null, errorProcessor($validator)), 400);
        }

        $user = auth('api')->user();

        $contents = $this->coupon->userCouponList([
            'user_id' => $user->id,
            'level_id' => $user->level->id,
            'is_active' => 1,
            'date' => date('Y-m-d'),
            'limit' => $request->limit,
            'offset' => $request->offset,
        ]);

        $data = CouponResource::collection($contents);

        return response()->json(responseFormatter(constant:DEFAULT_200, content:$data));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function apply(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required',
            'pickup_coordinates' => 'required',
            'vehicle_category_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(responseFormatter(constant: DEFAULT_400,errors: errorProcessor($validator)), 400);
        }

        $couponQuery = $this->coupon->getBy(column:'coupon_code', value:$request->coupon_code);
        $coupon = CouponResource::make($couponQuery);
        $user = auth('api')->user();

        if (!$coupon->is_active) {
            return response()->json(responseFormatter(constant: DEFAULT_NOT_ACTIVE), 200);
        }

        if (empty($request->header('zoneId'))) {

            return response()->json(responseFormatter(ZONE_404), 200);
        }

        $zoneId = $request->header('zoneId');

        $startDate = Carbon::parse($coupon->start_date);
        $endDate = Carbon::parse($coupon->end_date);
        $today = Carbon::now()->startOfDay();

        if($startDate->gt($today) || $endDate->lt($today))
        {
            return response()->json(responseFormatter(constant: DEFAULT_EXPIRED_200), 200); //coupon expire
        }

        $ruleValidation = $this->couponRuleValidate($coupon, $request->pickup_coordinates, $request->vehicle_category_id);
        if (!is_null($ruleValidation)) {
            return response()->json(responseFormatter(constant: $ruleValidation), 200); //coupon expire
        }

        if($coupon->coupon_type == 'first_order')
        {
            $total = $this->trip->get(limit:1, offset:1, attributes:['column' => 'customer_id', 'value' => $user->id]);
            if ($total < $coupon->limit) {
                return response()->json(responseFormatter(constant: DEFAULT_200, content:$coupon), 200);
            }

            return response()->json(responseFormatter(constant: COUPON_USAGE_LIMIT_406, content:$coupon), 200);//Limite orer
        }

        if ($coupon->limit == null) {
            return response()->json(responseFormatter(constant: DEFAULT_200, content:$coupon), 200);
        }

        $attributes = [
            'column' => 'customer_id',
            'value' => $user->id,
            'column_name' => 'coupon_id',
            'column_value' => [$coupon->id],
            'type' => 'ride_request'
        ];
        $total = $this->trip->get(limit:100, offset:1,attributes:$attributes)->count();
        if ($total < $coupon->limit) {
            return response()->json(responseFormatter(constant: DEFAULT_200, content:$coupon), 200);
        }

        return response()->json(responseFormatter(constant: COUPON_USAGE_LIMIT_406, content:$coupon), 200);//Limite orer
    }

    /**
     * @param $coupon
     * @param $pickupCoordinates
     * @param $vehicleCategoryId
     * @return array|null
     */
    private function couponRuleValidate($coupon, $pickupCoordinates, $vehicleCategoryId): ?array
    {
        if ($coupon->rules == 'area_wise') {
            $pickupCoordinates = json_decode($pickupCoordinates, true);
            $checkArea = $coupon->areas->filter(function($area) use($pickupCoordinates){
                return haversineDistance(
                    latitudeFrom:$area->latitude,
                    longitudeFrom:$area->longitude,
                    latitudeTo:$pickupCoordinates[0],
                    longitudeTo:$pickupCoordinates[1]
                    ) < $area->radius && $area->is_active == 1;
            });
            if ($checkArea->isEmpty()) {
                return COUPON_AREA_NOT_VALID_403;
            }
        }elseif ($coupon->rules == 'vehicle_category_wise') {
            $checkCategory = $coupon->categories->filter(function($query)use($vehicleCategoryId){
                return $query->id == $vehicleCategoryId && $query->is_active == 1;
            });

            if ($checkCategory->isEmpty()) {
                return COUPON_VEHICLE_CATEGORY_NOT_VALID_403;
            }
        }

        return null;
    }

}
