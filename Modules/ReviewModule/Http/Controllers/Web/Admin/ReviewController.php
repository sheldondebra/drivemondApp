<?php

namespace Modules\ReviewModule\Http\Controllers\Web\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\ReviewModule\Interfaces\ReviewInterface;

class ReviewController extends Controller
{
    public function __construct(
        private ReviewInterface $review
    )
    {
    }

    public function driverReviewExport($id,$reviewed,Request $request)
    {
        $attributes = [];
        if ($reviewed == 'customer') {
            $attributes['column_name'] = 'received_by';
        }elseif ($reviewed == 'driver') {
            $attributes['column_name'] = 'given_by';
        }
        $attributes['column_value'] = $id;

        $roles = $this->review->get(limit: 9999999999999999, offset: 1, attributes: $attributes);
        $exportData = $roles->map(function ($item){
            return [
                'id' => $item['id'],
                'trip_id' => $item['trip_request_id'],
                'reviewer' => $item?->givenUser?->first_name . ' ' . $item?->givenUser?->last_name,
                'rating' => $item['rating'],
                'review' => $item['feedback'],
            ];
        });

        return exportData($exportData, $request['file'], 'usermanagement::admin.driver.transaction.print');
    }

    public function customerReviewExport($id,$reviewed,Request $request)
    {
        $attributes = [];

        if ($reviewed == 'customer') {
            $attributes['column_name'] = 'given_by';
        }elseif ($reviewed == 'driver') {
            $attributes['column_name'] = 'received_by';
        }
        $attributes['column_value'] = $id;


        $roles = $this->review->get(limit: 9999999999999999, offset: 1, attributes: $attributes);
        $exportData = $roles->map(function ($item){
            return [
                'id' => $item['id'],
                'trip_id' => $item['trip_request_id'],
                'rating' => $item['rating'],
                'review' => $item['feedback'],
            ];
        });

        return exportData($exportData, $request['file'], 'usermanagement::admin.driver.transaction.print');
    }


}
