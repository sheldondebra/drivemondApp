<?php


use Modules\UserManagement\Entities\AppNotification;

if (!function_exists('sendDeviceNotification')) {
    function sendDeviceNotification($fcm_token, $title, $description, $image = null, $ride_request_id = null, $type = null, $action = null, $user_id = null, $user_name = null): bool|string
    {
        if ($user_id) {
            $notification = new AppNotification();
            $notification->user_id = $user_id;
            $notification->ride_request_id = $ride_request_id ?? null;
            $notification->title = $title?? 'Title Not Found';
            $notification->description = $description?? 'Description Not Found';
            $notification->type = $type ?? null;
            $notification->action = $action ?? null;
            $notification->save();
        }
        $config = businessConfig('server_key');

        $url = "https://fcm.googleapis.com/fcm/send";
        $header = array("authorization: key=" . $config->value ?? null,
            "content-type: application/json"
        );
        $image = asset('storage/app/public/push-notification') . '/' . $image;
        $postdata = '{
            "to" : "' . $fcm_token . '",
            "notification" : {
                    "title":"' . $title . '",
                    "body" : "' . $description . '",
                    "ride_request_id": "' . $ride_request_id . '",
                    "type": "' . $type . '",
                    "title_loc_key": "' . $ride_request_id . '",
                    "body_loc_key": "' . $type . '",
                    "user_name" : "'. $user_name. '",
                    "image": "' . $image . '",
                    "action": "' . $action . '",
                    "sound": "notification.wav",
                    "android_channel_id": "hexa-ride"
                },
                "data": {
                    "title":"' . $title . '",
                    "body" : "' . $description . '",
                    "ride_request_id": "' . $ride_request_id . '",
                    "type": "' . $type . '",
                    "title_loc_key": "' . $ride_request_id . '",
                    "body_loc_key": "' . $type . '",
                    "user_name" : "'. $user_name. '",
                    "image": "' . $image . '",
                    "action": "' . $action . '",
                    "sound": "notification.wav",
                    "android_channel_id": "hexa-ride"
                },
                "priority":"high"
             }';

        return sendCurlRequest($url, $postdata, $header);
    }
}

if (!function_exists('sendTopicNotification')) {
    function sendTopicNotification($topic, $title, $description, $image = null, $ride_request_id = null, $type = null): bool|string
    {
        $config = businessConfig('server_key');

        $url = "https://fcm.googleapis.com/fcm/send";
        $header = ["authorization: key=" . $config->value ?? null,
            "content-type: application/json",
        ];

        $image = asset('storage/app/public/push-notification') . '/' . $image;
        $topic_str = "/topics/" . $topic;

        $postdata = '{
             "to":"' . $topic_str . '",
             "notification" : {
                    "title":"' . $title . '",
                    "body" : "' . $description . '",
                    "ride_request_id": "' . $ride_request_id . '",
                    "type": "' . $type . '",
                    "title_loc_key": "' . $ride_request_id . '",
                    "body_loc_key": "' . $type . '",
                    "image": "' . $image . '",
                    "sound": "notification.wav",
                    "android_channel_id": "hexa-ride"
                },
                "data": {
                    "title":"' . $title . '",
                    "body" : "' . $description . '",
                    "ride_request_id": "' . $ride_request_id . '",
                    "type": "' . $type . '",
                    "title_loc_key": "' . $ride_request_id . '",
                    "body_loc_key": "' . $type . '",
                    "image": "' . $image . '",
                    "sound": "notification.wav",
                    "android_channel_id": "hexa-ride"
                },
                "priority":"high"
              }';

        return sendCurlRequest($url, $postdata, $header);
    }
}

/**
 * @param string $url
 * @param string $postdata
 * @param array $header
 * @return bool|string
 */
function sendCurlRequest(string $url, string $postdata, array $header): string|bool
{
    $ch = curl_init();
    $timeout = 120;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

    // Get URL content
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}
