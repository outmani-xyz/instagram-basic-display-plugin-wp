<?php

/**
 * Plugin Name: Instagram basic display
 * Plugin URI:        http://outmani.xyz
 * Description:       ...
 * Author:            outmani
 * Author URI:        http://outmani.xyz
 */
require_once('instagram_api.php');
define('INSTA_OPTION','instagram_app_setting');
define('INSTA_SAVE','?action=instagram_save');
add_action('init', function () {
    // instagram_callback();
    // instagram_refresh_access_token();
});
add_action('admin_menu', 'instagram_page_setting');

function instagram_page_setting()
{
    add_options_page('Instagram setting', 'Instagram setting', 'manage_options', 'instagram-setting', 'instagram_setting_admin_ui');
}
function instagram_setting_admin_ui()
{
    $app_id = get_instagram_config('app_id');
    $app_secret = get_instagram_config('app_secret');
    $token = get_instagram_config('token');
    $expired = get_instagram_config('expired');
$params = [
    'get_code'=> !empty($token)?$token:''
];
    $insta_api = new InstagramApi($params, $app_id, $app_secret);
    
?>
<div class="inst">
    <h1>Instagram Settings</h1>
    <p class="description">
        <strong>Callback Url for pour instagram App : </strong>
        <code>
            <a href='<?php echo InstagramApi::callbackURL() ?>'><?php echo InstagramApi::callbackURL() ?></a>
        </code>
    </p>
    <div class="form-group">
        
    <a href="<?php echo $insta_api->authorizationUrl ?>">Nouveau</a>
                    <?php if ($token) : ?>
                        <a href="<?php echo get_home_url() . '?action=instagram_refresh_access_token&site=' . $site ?>">Refrech</a>
                <?php endif; ?>
    </div>
<form action="<?php echo INSTA_SAVE?>" method="post">
<div class="form-group">
        <label>APP ID</label>
        <input type="text"  name="app_id" value="<?php echo $app_id?>">
    </div>
    <div class="form-group">
        <label>APP secrect</label>
        <input type="password"  name="app_secret" value="<?php echo $app_secret?>">
    </div>
    <div class="form-group">
        <label>Token </label>
        <input type="text" readonly name="token" value="<?php echo $token?>">
    </div>
    <div class="form-group">
        <label>Token Experation date</label>
        <input type="text" readonly name="expired" value="<?php echo $expired?>">
    </div>
    <button type="submit">save</button>
</form>

</div>
<?    }
    function instagram_callback()
    {
        if (!empty($_GET['code']) && !empty($_GET['state'])) {

            $short_token = $_GET['code'];
            $site = $_GET['state'];
            $app_id = get_instagram_config($site, 'app_id');
            $app_secret = get_instagram_config($site, 'app_secret');
            $insta_api = new InstagramApi(['get_code' => $short_token], $app_id, $app_secret);
            $user_id = $insta_api->userId;
            $long_token = $insta_api->getUserAccessToken();
            $expired_date = $insta_api->getUserAccessTokenExpires() + time();
            $option_data = [
                'access_token' => [
                    'token' => $long_token,
                    'expired_date' => $expired_date
                ],
                'user_id' => $user_id,
            ];

            //var_dump($option_data,$site . '_insta_setting');die;
            update_option($site . '_insta_setting', $option_data);
            wp_redirect(admin_url('/options-general.php?page=instagram-setting'));
        }
    }
    function get_instagram_config($field)
    {
        $val = null;
        $fields = get_option('instagram_app_settings');
        if (!empty($fields[$field])) {
            $val = $fields[$field];
        }
        return $val;
    }

    function instagram_refresh_access_token()
    {

        if (!empty($_GET['action']) && $_GET['action'] == 'instagram_refresh_access_token') {

            $app_id = get_instagram_config('app_id');
            $app_secret = get_instagram_config('app_secret');

            $site_insta_setting = get_option($site . 'instagram_setting');
            if (!empty($site_insta_setting['user_id'])) {
                $user_id = $site_insta_setting['user_id'];
            }
            if (!empty($site_insta_setting['access_token'])) {
                $access_token = $site_insta_setting['access_token']['token'];
            }
            $insta_api = new InstagramApi([
                'access_token' => $access_token,
                'user_id' => $user_id
            ], $app_id, $app_secret);

            $data = $insta_api->refresh_access_token();
            if (!empty($data['access_token']) && !empty($data['expires_in'])) {

                $option_data = [
                    'access_token' => [
                        'token' => $data['access_token'],
                        'expired_date' => ($data['expires_in'] + time())
                    ],
                    'user_id' => $user_id,
                ];
                update_option($site . '_insta_setting', $option_data);
                wp_redirect(admin_url('/options-general.php?page=instagram-setting'));
            }
        }
    }
