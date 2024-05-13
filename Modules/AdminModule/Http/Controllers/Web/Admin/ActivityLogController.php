<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AdminModule\Entities\ActivityLog;
use Modules\UserManagement\Entities\UserLevel;

class ActivityLogController extends Controller
{

    public function log($request)
    {

        return ActivityLog::query()
            ->where('logable_type', $request['logable_type'])
            ->when(array_key_exists('user_type', $request), function ($query) use ($request) {
                $query->where('user_type', $request['user_type']);
            })
            ->when(array_key_exists('logable_id', $request), function ($query) use ($request) {
                $query->where('logable_id', $request['logable_id']);
            })
            ->paginate(10);
    }
}
