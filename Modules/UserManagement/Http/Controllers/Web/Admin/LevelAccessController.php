<?php

namespace Modules\UserManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\UserManagement\Repositories\LevelAccessRepository;

class LevelAccessController extends Controller
{

    public function __construct(
        private LevelAccessRepository $levelAccessRepository
    )
    {
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
           'value' => 'required',
           'name' => 'required',
           'id' => 'required',
           'user_type' => 'required',
        ]);

        return response()->json($this->levelAccessRepository->update(attributes: $validated, id: $request->id));
    }
}
