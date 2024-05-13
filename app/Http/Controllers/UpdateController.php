<?php

namespace App\Http\Controllers;

use App\Traits\ActivationClass;
use App\Traits\UnloadedHelpers;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Mockery\Exception;
use Modules\BusinessManagement\Entities\FirebasePushNotification;
use Modules\BusinessManagement\Service\Interface\BusinessSettingServiceInterface;
use Modules\UserManagement\Entities\User;
use Illuminate\Support\Facades\Schema;

class UpdateController extends Controller
{
    use UnloadedHelpers;
    use ActivationClass;

    protected $businessSetting;

    public function __construct(BusinessSettingServiceInterface $businessSetting)
    {
        $this->businessSetting = $businessSetting;
    }

    public function update_software_index()
    {
        Artisan::call('module:enable');
        return view('update.update-software');
    }

    public function update_software(Request $request)
    {
        $this->setEnvironmentValue('SOFTWARE_ID', 'MTAwMDAwMDA=');
        $this->setEnvironmentValue('BUYER_USERNAME', $request['username']);
        $this->setEnvironmentValue('PURCHASE_CODE', $request['purchase_key']);
        $this->setEnvironmentValue('SOFTWARE_VERSION', '1.3');
        $this->setEnvironmentValue('APP_ENV', 'local');
        $this->setEnvironmentValue('APP_MODE', 'live');
        $this->setEnvironmentValue('APP_URL', url('/'));
        $this->setEnvironmentValue('PUSHER_APP_ID', 'drivemond');
        $this->setEnvironmentValue('PUSHER_APP_KEY', 'drivemond');
        $this->setEnvironmentValue('PUSHER_APP_SECRET', 'drivemond');
        $this->setEnvironmentValue('PUSHER_HOST', getMainDomain(url('/')));
        $this->setEnvironmentValue('PUSHER_PORT', 6001);
        $this->setEnvironmentValue('PUSHER_APP_CLUSTER', 'mt1');
        $this->setEnvironmentValue('PUSHER_SCHEME', 'https');

        $data = $this->actch();
        try {
            if (!$data->getData()->active) {
                $remove = array("http://", "https://", "www.");
                $url = str_replace($remove, "", url('/'));

                $activation_url = base64_decode('aHR0cHM6Ly9hY3RpdmF0aW9uLmRyaXZlbW9uZC5hcHAv');
                $activation_url .= '?username=' . $request['username'];
                $activation_url .= '&purchase_code=' . $request['purchase_key'];
                $activation_url .= '&domain=' . $url . '&';

                return redirect($activation_url);
            }
        } catch (Exception $exception) {
            Toastr::error('verification failed! try again');
            return back();
        }


        Artisan::call('migrate', ['--force' => true]);

        $previousRouteServiceProvider = base_path('app/Providers/RouteServiceProvider.php');
        $newRouteServiceProvider = base_path('app/Providers/RouteServiceProvider.txt');
        copy($newRouteServiceProvider, $previousRouteServiceProvider);

        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('config:cache');
        Artisan::call('config:clear');
        Artisan::call('optimize:clear');
        if (FirebasePushNotification::where(['name' => 'identity_image_approved'])->first() == false) {
            FirebasePushNotification::updateOrCreate(['name' => 'identity_image_approved'], [
                'value' => 'Your identity image has been successfully reviewed and approved.',
                'status' => 1
            ]);
        }
        if (FirebasePushNotification::where(['name' => 'identity_image_rejected'])->first() == false) {
            FirebasePushNotification::updateOrCreate(['name' => 'identity_image_rejected'], [
                'value' => 'Your identity image has been rejected during our review process.',
                'status' => 1
            ]);
        }
        if (FirebasePushNotification::where(['name' => 'review_from_customer'])->first() == false) {
            FirebasePushNotification::updateOrCreate(['name' => 'review_from_customer'], [
                'value' => 'New review from a customer! See what they had to say about your service.',
                'status' => 1
            ]);
        }
        if (FirebasePushNotification::where(['name' => 'review_from_driver'])->first() == false) {
            FirebasePushNotification::updateOrCreate(['name' => 'review_from_driver'], [
                'value' => 'New review from a driver! See what he had to say about your trip.',
                'status' => 1
            ]);
        }
        return redirect(env('APP_URL'));
    }
}
