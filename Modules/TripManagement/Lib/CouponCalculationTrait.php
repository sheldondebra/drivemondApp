<?php

namespace Modules\TripManagement\Lib;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\PromotionManagement\Entities\CouponSetup;
use Modules\TripManagement\Entities\TripRequest;
use Modules\TripManagement\Entities\TripRequestFee;
use Modules\TripManagement\Entities\TripRequestTime;
use Modules\UserManagement\Entities\User;

trait CouponCalculationTrait
{
    public function getCouponDiscount($user, $trip, $coupon)
    {
        $discount = 0;
        $message = DEFAULT_200;

        DB::beginTransaction();
        $coupon_apply_count = TripRequest::query()
            ->where(['customer_id' => $user->id, 'coupon_id' => $coupon->id])
            ->count();

        if ($coupon_apply_count >= $coupon->limit) {
            // maximum time applied
            return [
                'discount' => $discount,
                'message' => COUPON_USAGE_LIMIT_406
            ];
        }

        if ($coupon->rules == 'vehicle_category_wise') {
            if ($coupon->categories->contains($trip->vehicle_category_id)) {
                $discount = $this->getDiscountAmount($coupon, $user, $trip);
                if ($discount == 0) {
                    //invalid coupon
                    $message = COUPON_404;
                } else {
                    $this->updateCouponCount($coupon, $discount);
                }
            } else {
                //invalid coupon
                $message = COUPON_404;
            }
        } else {
            $discount = $this->getDiscountAmount($coupon, $user, $trip);
            if ($discount == 0) {
                //invalid coupon
                $message = COUPON_404;
            } else {
                $this->updateCouponCount($coupon, $discount);
            }
        }
        DB::commit();

        return [
            'discount' => $discount,
            'message' => $message
        ];
    }

    private function getDiscountAmount($coupon, $user, $trip)
    {
        if ($coupon->user_id == $user->id || $coupon->user_id == 'all' || $coupon->user_level_id == $user->user_level_id) {
            // apply coupon

            if ($coupon->amount_type == 'percentage') {
                $discount = ($coupon->coupon * ($trip->paid_fare-$trip->fee->tips-$trip->fee->vat_tax)) / 100;
                //if calculated discount exceeds coupon max discount amount
                if ($discount > $coupon->max_coupon_amount) {
                    return round($coupon->max_coupon_amount,2);
                }
                return round($discount,2);
            }
            $amount = $trip->paid_fare-$trip->fee->tips-$trip->fee->vat_tax;
            if ($coupon->coupon>$amount) {
                return round(min($coupon->coupon, $amount),2);
            }
            return round($coupon->coupon);
        }
        //invalid coupon
        return 0;
    }


    private function updateCouponCount($coupon, $amount)
    {
        $coupon = CouponSetup::query()->firstWhere('id', $coupon->id);
        $coupon->total_amount += $amount;
        $coupon->increment('total_used');
        $coupon->save();

    }

}
