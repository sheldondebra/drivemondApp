<?php

namespace Modules\AdminModule\Http\Controllers\Web\Admin;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Session;
use Modules\AdminModule\Interfaces\AdminNotificationInterface;

class SharedController extends Controller
{
    public function __construct(
        private AdminNotificationInterface $notification
    )
    {
    }

    public function getNotifications()
    {
        $notification = $this->notification->get(limit: 999999, offset: 1);

        return response()->json(view('adminmodule::partials._notifications', compact('notification'))->render());
    }
    public function seenNotification(Request $request)
    {
        $notification = $this->notification->update([],$request->id);

        return response()->json($notification);
    }

    public function lang($locale)
    {
        $direction = 'ltr';
        $languages = businessConfig(SYSTEM_LANGUAGE)?->value??[['code'=>'en','direction'=>'ltr']];
        foreach ($languages as $data) {
            if ($data['code'] == $locale) {
                $direction = $data['direction'] ?? 'ltr';
            }
        }
        session()->put('locale', $locale);
        Session::put('direction', $direction);
        return redirect()->back();
    }
}
