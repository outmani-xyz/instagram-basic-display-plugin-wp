<?php

/**
 * Plugin Name: Instagram basic display
 * Plugin URI:        http://outmani.xyz
 * Description:       ...
 * Author:            outmani
 * Author URI:        http://outmani.xyz
 */
require_once('instagram_api.php');
define('INSTA_OPTION', 'instagram_app_setting');
define('INSTA_SAVE', 'instagram_save');
define('INSTA_PAGE_SETTING', '/options-general.php?page=instagram-setting');
add_action('init', function () {
    if (is_user_logged_in()) {
        instagram_callback();
        instagram_refresh_access_token();
        instagram_save();
    }
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
    $user_id = get_instagram_config('user_id');
    $params = [
        'access_token' => !empty($token) ? $token : '',
        'state'=>'instagram_callback'
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

            <a target="_blank" href="<?php echo $insta_api->authorizationUrl ?>">Nouveau</a>
            <?php if ($token) : ?>
                <a href="<?php echo get_home_url() . '?action=instagram_refresh_access_token&site=' . $site ?>">Refrech</a>
            <?php endif; ?>
        </div>
        <div class="input-group">

        <div class="form-group">
                <label>User ID </label>
                <input type="text" readonly name="token" value="<?php echo $user_id ?>">
            </div>
            <div class="form-group">
                <label>Token </label>
                <input type="text" readonly name="token" value="<?php echo substr($token,0,10).(!empty($token)?'*****':'') ?>">
            </div>
            <div class="form-group">
                <label>Token Experation date</label>
                <input type="text" readonly name="expired" value="<?php echo $expired ?>">
            </div>
            <form method="post">
                <div class="form-group">
                    <label>APP ID</label>
                    <input type="text" name="app_id" value="<?php echo $app_id ?>">
                </div>
                <div class="form-group">
                    <label>APP secrect</label>
                    <input type="password" name="app_secret" value="<?php echo $app_secret ?>">
                    <input type="hidden" name="action" value="<?php echo INSTA_SAVE ?>">
                </div>
                <button type="submit">save</button>
            </form>
        </div>

    </div>
<?php    }
function instagram_callback()
{
    if (!empty($_GET['code']) && !empty($_GET['state']) && $_GET['state'] == 'instagram_callback') {
        $short_token = $_GET['code'];
        $expired = 0;
        $app_id = get_instagram_config('app_id');
        $app_secret = get_instagram_config('app_secret');
        $insta_api = new InstagramApi(['get_code' => $short_token], $app_id, $app_secret);
        $user_id = $insta_api->userId;
        $token = $insta_api->getUserAccessToken();
        $expired = $insta_api->getUserAccessTokenExpires() + time();
        $option_data = get_option(INSTA_OPTION);
        $option_data['token'] = $token;
        $option_data['expired'] = ($expired + time());
        $option_data['user_id'] = $user_id;
        update_option(INSTA_OPTION,$option_data);
        wp_redirect(admin_url(INSTA_PAGE_SETTING));
    }
}
function get_instagram_config($field)
{
    $val = null;
    $fields = get_option(INSTA_OPTION);
    if (!empty($fields[$field])) {
        $val = $fields[$field];
    }
    return $val;
}

function instagram_refresh_access_token()
{

    if (!empty($_GET['action']) && $_GET['state'] == 'instagram_refresh_access_token') {

        $app_id = get_instagram_config('app_id');
        $app_secret = get_instagram_config('app_secret');

        $option_data = get_option(INSTA_OPTION);
        if (!empty($option_data['user_id'])) {
            $user_id = $option_data['user_id'];
        }
        if (!empty($option_data['token'])) {
            $access_token = $option_data['token'];
        }
        $insta_api = new InstagramApi([
            'access_token' => $access_token,
            'user_id' => $user_id
        ], $app_id, $app_secret);

        $data = $insta_api->refresh_access_token();
        if (!empty($data['access_token']) && !empty($data['expires_in'])) {
            $option_data['token'] = $data['access_token'];
            $option_data['expired'] = ($data['expires_in'] + time());
            $option_data['user_id'] = $user_id;
            update_option(INSTA_OPTION, $option_data);
            wp_redirect(admin_url(INSTA_PAGE_SETTING));
        }
    }
}
function instagram_save()
{
    if (!empty($_POST['action']) && $_POST['action'] == 'instagram_save') {
        $app_id = !empty($_POST['app_id']) ? $_POST['app_id'] : '';
        $app_secret = !empty($_POST['app_secret']) ? $_POST['app_secret'] : '';
        $insta_setting = get_option(INSTA_OPTION);
        $insta_setting['app_id'] = $app_id;
        $insta_setting['app_secret'] = $app_secret;
        $n = update_option(INSTA_OPTION, $insta_setting);
        wp_redirect(admin_url(INSTA_PAGE_SETTING));
    }
}
