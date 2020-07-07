<?php

include dirname(__FILE__) . "../../../../../../script/init_script.php";
require_once(__DIR__ . '/instagram_api.php');
$sites_app_setting = get_param_global('instagram_app_settings');

foreach ($sites_app_setting as $site => $app_setting) {
    $site_insta_setting = get_option($site . '_insta_setting');
    $token = '';
    $expired_date = 0;
    if (!empty($site_insta_setting['user_id'])) {
        $user_id = $site_insta_setting['user_id'];
    }
    if (!empty($site_insta_setting['access_token'])) {
        $token = $site_insta_setting['access_token']['token'];
        $expired_date = $site_insta_setting['access_token']['expired_date'];
    }
    $insta_params = [
        'access_token' => !empty($token) ? $token : '',
        'user_id' => !empty($user_id) ? $user_id : '',
        'state' => $site
    ];
    if (!empty($token) && (($expired_date - time()) > (10 * 24 * 60 * 60)) && !empty($user_id)) {

        $insta_api = new InstagramApi($insta_params, $app_setting['app_id'], $app_setting['app_secret']);
        $data = $insta_api->refresh_access_token();
        if (!empty($data['access_token']) && !empty($data['expires_in'])) {
            $expire = ($data['expires_in'] + time());
            $new_token = $data['access_token'];
            $option_data = [
                'access_token' => [
                    'token' => $new_token,
                    'expired_date' => $expire
                ],
                'user_id' => $user_id,
            ];
            update_option($site . '_insta_setting', $option_data);
            print_flush("\n Site : ".$site."\n");
            print_r("\n     - Old token: ".substr($token,0,10)."****\n");
            print_r("\n     - New token: ".substr($new_token,0,10)."****\n");
            print_r("\n     - Expire Date : ".date('d/M/Y',$expire)."\n");
        }
    }else{
        print_flush("\n Site : ".$site." : required new access token \n");
    }
}
    // $site = $_GET['site'];
    // $app_id = get_instagram_config($site, 'app_id');
    // $app_secret = get_instagram_config($site, 'app_secret');

    // $site_insta_setting = get_option($site . '_insta_setting');
    // if (!empty($site_insta_setting['user_id'])) {
    //     $user_id = $site_insta_setting['user_id'];
    // }
    // if (!empty($site_insta_setting['access_token'])) {
    //     $access_token = $site_insta_setting['access_token']['token'];
    // }
    // $insta_api = new InstagramApi([
    //     'access_token' => $access_token,
    //     'user_id' => $user_id
    // ], $app_id, $app_secret);
