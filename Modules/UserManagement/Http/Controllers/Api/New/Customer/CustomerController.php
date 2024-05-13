<?php

namespace Modules\UserManagement\Http\Controllers\Api\New\Customer;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\UserManagement\Http\Requests\CustomerProfileUpdateApiRequest;
use Modules\UserManagement\Service\Interface\CustomerServiceInterface;
use Modules\UserManagement\Transformers\CustomerResource;

class CustomerController extends Controller
{
    protected $customerService;

    public function __construct(CustomerServiceInterface $customerService)
    {
        $this->customerService = $customerService;
    }

    public function profileInfo(Request $request): JsonResponse
    {
        if ($request->user()->user_type == CUSTOMER) {
            $withAvgRelations = [['receivedReviews', 'rating']];
            $customer = $this->customerService->findOne(id: auth()->id(), withAvgRelations: $withAvgRelations, relations: ['userAccount', 'level'], withCountQuery: ['customerTrips' => []]);
            $customer = new CustomerResource($customer);
            return response()->json(responseFormatter(DEFAULT_200, $customer), 200);
        }
        return response()->json(responseFormatter(DEFAULT_401), 401);
    }

    public function updateProfile(CustomerProfileUpdateApiRequest $request): JsonResponse
    {
        $this->customerService->update(id: $request->user()->id, data: $request->validated());
        return response()->json(responseFormatter(DEFAULT_UPDATE_200), 200);
    }
}
