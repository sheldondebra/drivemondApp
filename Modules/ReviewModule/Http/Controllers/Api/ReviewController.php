<?php

namespace Modules\ReviewModule\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\ReviewModule\Interfaces\ReviewInterface;
use Modules\ReviewModule\Transformers\ReviewResource;
use Modules\TripManagement\Interfaces\TripRequestInterfaces;
use Modules\UserManagement\Lib\LevelHistoryManagerTrait;

class ReviewController extends Controller
{
    use LevelHistoryManagerTrait;
    public function __construct(
        private ReviewInterface $review, private TripRequestInterfaces $tripRequest
    )
    {
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required|numeric',
            'offset' => 'required|numeric',
            'is_saved' => 'in:0,1'
        ]);

        if ($validator->fails()) {

            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 400);
        }
        $attributes = [
            'column' => 'received_by',
            'value' => auth()->id(),
        ];
        if (!is_null($request->is_saved)) {
            $attributes['column_name'] = 'is_saved';
            $attributes['column_value'] = $request->is_saved;
        }
        $review = $this->review->get(limit: $request->limit, offset: $request->offset, dynamic_page: true, attributes: $attributes, relations: ['givenUser', 'trip']);
        $review = ReviewResource::collection($review);

        return response()->json(responseFormatter(constant: DEFAULT_200, content: $review, limit: $request->limit, offset: $request->offset));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $route = str_contains($request->route()?->getPrefix(), 'customer');
        $key = $route ? 'customer_review' : 'driver_review';
        if ( !businessConfig($key)->value ?? 0 ) {

            return response()->json(responseFormatter( REVIEW_SUBMIT_403), 403);
        }

        $validator = Validator::make($request->all(), [
            'ride_request_id' => 'required',
            'rating' => 'required|numeric|min:1|max:5',
            'feedback' => 'sometimes',
        ]);

        if ($validator->fails()) {

            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 403);
        }
        $tripRequest = $this->tripRequest->getBy(column:'id', value:$request['ride_request_id']);
        $user = auth('api')->user();
        if($tripRequest && ($tripRequest->customer_id == $user->id || $tripRequest->driver_id == $user->id )){
            $review = $this->review->getBy('trip_request_id',value:$tripRequest->id, attributes:['column'=>'given_by','value' => [$request->user()->id]]);
            if(!$review){
                $received_by = $user->user_type == 'driver' ? $tripRequest->customer_id : $tripRequest->driver_id;
                $request->merge([
                    'given_by' => $user->id,
                    'received_by' => $received_by,
                    'trip_type' => $tripRequest->type,
                ]);
                DB::beginTransaction();
                $this->review->store(attributes:$request->all());
                $this->reviewCountChecker($user);
                DB::commit();

                return response()->json(responseFormatter(DEFAULT_STORE_200));
            }

            return response()->json(responseFormatter(REVIEW_403));
        }

        return response()->json(responseFormatter(DEFAULT_404), 403);
    }

    /**
     * @param $id
     * @param Request $request
     * @return JsonResponse
     */
    public function update($id, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ride_request_id' => 'required',
            'rating' => 'numeric|min:1|max:5',
            'feedback' => 'sometimes',
        ]);

        if ($validator->fails()) {

            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 400);
        }
        if (empty($request->header('zoneId'))) {

            return response()->json(responseFormatter(ZONE_404), 403);
        }
        $tripRequest = $this->tripRequest->getBy(column:'id', value:$request['ride_request_id']);
        if($tripRequest){
            $review = $this->review->getBy('id',value: $id, attributes:['column'=>'trip_request_id','value' => [$tripRequest->id]]);
            if($review && $review->given_by == $request->user()->id){
                $this->review->update(attributes: $request->all(), id: $review->id);

                return response()->json(responseFormatter(DEFAULT_UPDATE_200));
            }

            return response()->json(responseFormatter(REVIEW_404), 403);
        }

        return response()->json(responseFormatter(DEFAULT_404), 403);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $review = $this->review->getBy('id',value: $id);
        if($review && $review->given_by == auth('api')->id()){
            $this->review->destroy(id: $review->id);

            return response()->json(responseFormatter(DEFAULT_DELETE_200));
        }

        return response()->json(responseFormatter(REVIEW_404), 403);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function save($id): JsonResponse
    {
        $review = $this->review->getBy('id',value: $id);
        if ($review && $review->received_by == auth('api')->id()) {
            $isSaved = $review->is_saved == 0 ? 1 : 0;
            $this->review->update(attributes: ['is_saved' => $isSaved], id: $review->id);

            return response()->json(responseFormatter(DEFAULT_UPDATE_200));
        }

        return response()->json(responseFormatter(DEFAULT_404), 403);
    }

    public function checkSubmission(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trip_request_id' => 'required',
        ]);

        if ($validator->fails()) {

            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 400);
        }
        $review = $this->review->getBy(column: 'given_by',value: auth('api')->id(), attributes:['column'=>'trip_request_id','value' => $request->trip_request_id]);

        if (!$review) {

            return response()->json(responseFormatter(DEFAULT_200));
        }

        return response()->json(responseFormatter(constant: DEFAULT_200, content: ReviewResource::collection($review)));
    }


}
