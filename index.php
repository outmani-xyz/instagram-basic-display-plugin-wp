<?php

/**
 * Plugin Name: Instagram basic display
 * Plugin URI:        http://outmani.xyz
 * Description:       ...
 * Author:            Donate
 * Author URI:        https://www.patreon.com/outmani
 */
require_once('instagram_api.php');
define('INSTA_OPTION', 'instagram_app_setting');
define('INSTA_SAVE', 'instagram_save');
define('INSTA_PAGE_SETTING', admin_url('/options-general.php?page=instagram-setting'));
add_action('init', function () {
    if (is_user_logged_in()) {
        instagram_callback();
        instagram_refresh_access_token();
        instagram_save();
    }
    instagram_refresh_access_token_cron();
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
        'state' => 'instagram_callback',
        'user_id' => $user_id
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
                <a href="<?php echo get_home_url() . '?action=instagram_refresh_access_token' ?>">Refrech</a>
            <?php endif; ?>
        </div>
        <div class="input-group">

            <div class="form-group">
                <label>User ID </label>
                <input type="text" readonly name="token" value="<?php echo $user_id ?>">
            </div>
            <div class="form-group">
                <label>Token </label>
                <input type="text" readonly name="token" value="<?php echo substr($token, 0, 10) . (!empty($token) ? '*****' : '') ?>">
            </div>
            <div class="form-group">
                <label>Token Experation date</label>
                <input type="text" readonly name="expired" value="<?php echo date('d/m/Y H:i:s', $expired) ?>">
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
        $option_data['expired'] = $expired;
        $option_data['user_id'] = $user_id;
        update_option(INSTA_OPTION, $option_data);

        wp_redirect(INSTA_PAGE_SETTING);
        exit;
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
function instagram_refresh_access_token_cron()
{
    $expired = get_instagram_config('expired');
    if (($expired + time() - 10 * 24 * 60 * 60) < (time())) {
        $app_id = get_instagram_config('app_id');
        $app_secret = get_instagram_config('app_secret');
        $user_id = get_instagram_config('user_id');
        $token = get_instagram_config('token');
        $insta_api = new InstagramApi([
            'access_token' => $token,
            'user_id' => $user_id
        ], $app_id, $app_secret);
        if ($insta_api->refresh_access_token()) {
            $option_data = get_option(INSTA_OPTION);
            $option_data['token'] = $insta_api->getUserAccessToken();
            $option_data['expired'] = $insta_api->getUserAccessTokenExpires() +  time();
            $option_data['user_id'] = $user_id;
            update_option(INSTA_OPTION, $option_data);
        }
    }
}
function instagram_refresh_access_token()
{

    if (!empty($_GET['action']) && $_GET['action'] == 'instagram_refresh_access_token') {

        $app_id = get_instagram_config('app_id');
        $app_secret = get_instagram_config('app_secret');
        $user_id = get_instagram_config('user_id');
        $token = get_instagram_config('token');
        $insta_api = new InstagramApi([
            'access_token' => $token,
            'user_id' => $user_id
        ], $app_id, $app_secret);
        if ($insta_api->refresh_access_token()) {
            $option_data = get_option(INSTA_OPTION);
            $option_data['token'] = $insta_api->getUserAccessToken();
            $option_data['expired'] = $insta_api->getUserAccessTokenExpires() +  time();
            $option_data['user_id'] = $user_id;
            update_option(INSTA_OPTION, $option_data);
        }
        wp_redirect(INSTA_PAGE_SETTING);
        exit;
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

        wp_redirect(INSTA_PAGE_SETTING);
        exit;
    }
}


add_shortcode('instagram_feed', 'instagram_feed_callback');

function instagram_feed_callback($atts, $content)
{
    $nbr_media = (isset($atts['number']) ? $atts['number'] : 4);
    $class = (isset($atts['class']) ? $atts['cless'] : 'insta_feed');

    $app_id = get_instagram_config('app_id');
    $app_secret = get_instagram_config('app_secret');
    $user_id = get_instagram_config('user_id');
    $token = get_instagram_config('token');
    $insta_api = new InstagramApi([
        'access_token' => $token,
        'user_id' => $user_id
    ], $app_id, $app_secret);

    $medias = $insta_api->getUserMedia();
    if (empty($content)) {
        $content = '
        <a class="' . $class . '" href="{media_link}">
            <img src="{media_thumbnail}" />
        </a>
        ';
    }
    if (!empty($medias['data']) && !empty($content)) {
        $all_content = '';
        $medias = array_slice($medias['data'], 0, $nbr_media);
        foreach ($medias as $media) {
            $media_src = $media['media_url'];
            $media_url = $media['permalink'];
            if ($media['media_type'] == 'VIDEO') {
                $media_src = $media['thumbnail_url'];
            }
            $all_content .= str_replace(array('{media_thumbnail}', '{media_link}'), array($media_src, $media_url), $content);
        }
        $content = $all_content;
    }
    return $content;
}
