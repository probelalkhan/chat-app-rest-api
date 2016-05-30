<?php

class GCM
{
    function __construct()
    {

    }

    //This function will send message to the given registration ids
    //We are also passing a message that is actually an array containing the message
    public function sendMessage($registration_ids, $message) {
        $fields = array(
            'registration_ids' => $registration_ids,
            'data' => $message,
        );
        //In this function we are calling the main method responsible for sending push notification
        //it is sendPushNotification
        return $this->sendPushNotification($fields);
    }

    //This is the main method responsible for sending push notification
    //I have already explained it in previous tutorials
    private function sendPushNotification($fields){
        include_once __DIR__ . '/../../include/Config.php';

        $url = 'https://android.googleapis.com/gcm/send';

        $headers = array(
            'Authorization: key=' . GOOGLE_API_KEY,
            'Content-Type: application/json'
        );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        $result = curl_exec($ch);
        if ($result === FALSE) {
            die('Curl failed: ' . curl_error($ch));
        }
        curl_close($ch);

        return $result;
    }
}