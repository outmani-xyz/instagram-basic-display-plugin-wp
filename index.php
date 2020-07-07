<?php
require_once('instagram_api.php');

if (is_user_logged_in()) {
    instagram_callback();
    instagram_refresh_access_token();
    add_action('admin_menu', 'instagram_page_setting');
}

add_shortcode('nl_instagram', 'nl_instagram_imgs');

function instagram_page_setting()
{
    add_options_page('Instagram setting', 'Instagram setting', 'manage_options', 'instagram-setting', 'instagram_setting_admin_ui');
}
function instagram_setting_admin_ui()
{
    $sites_app_setting = get_param_global('instagram_app_settings');
?>
    <h1>Instagram Settings</h1>
    <p class="description">
        <strong>Callback Url for pour instagram App : </strong>
        <code>
            <a href='<?php echo InstagramApi::callbackURL() ?>'><?php echo InstagramApi::callbackURL() ?></a>
        </code>
    </p>
    <table border="1">
        <tr>
            <th>Site</th>
            <th>Long access Token Expiration</th>
            <th>nouveau/refresh access token</th>
            <th>Token</th>
        </tr>
        <?php
        foreach ($sites_app_setting as $site => $app_setting) :
            $site_insta_setting = get_option($site . '_insta_setting');
            $token ='';
            $expired_date =0;
            $token_visible='';
            if (!empty($site_insta_setting['user_id'])) {
                $user_id = $site_insta_setting['user_id'];
            }
            if (!empty($site_insta_setting['access_token'])) {
                $token = $site_insta_setting['access_token']['token'];
                $expired_date = $site_insta_setting['access_token']['expired_date'];
                $token_visible = substr($token,0,10).'*****';
            }
            $insta_params = [
                'access_token' => !empty($token) ? $token : '',
                'user_id' => !empty($user_id) ? $user_id : '',
                'state' => $site
            ];
            $insta_api = new InstagramApi($insta_params, $app_setting['app_id'], $app_setting['app_secret']);
        ?>
            <tr>
                <td><?php echo $site ?></td>
                <td><?php echo date('d-m-Y H:s', $expired_date) ?></td>
                <td>
                    <a href="<?php echo $insta_api->authorizationUrl ?>">Nouveau</a>
                    <?php if ($token) :?>
                        <a href="<?php echo get_home_url().'?action=instagram_refresh_access_token&site='.$site ?>">Refrech</a>
                    <?php endif;?>
                </td>
                <td><?php echo $token_visible?></td>
            </tr>
    <?php    //var_dump($site,$app_setting);
        endforeach;

        echo '</table>';
    }
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
    function get_instagram_config($site, $field)
    {
        $val = null;
        $app_settings = get_param_global('instagram_app_settings');
        if (!empty($app_settings[$site])) {
            $fields = $app_settings[$site];
        }
        if (!empty($fields[$field])) {
            $val = $fields[$field];
        }
        return $val;
    }


    function nl_instagram_imgs($atts, $content)
    {
        global $template_id;
        $site = get_post_meta($template_id, 'template_site', true);
        $site = 'Jardin';
        $app_id = get_instagram_config($site, 'app_id');
        $app_secret = get_instagram_config($site, 'app_secret');

        $site_insta_setting = get_option($site . '_insta_setting');
        if (!empty($site_insta_setting['user_id'])) {
            $user_id = $site_insta_setting['user_id'];
        }
        if (!empty($site_insta_setting['access_token'])) {
            $access_token = $site_insta_setting['access_token']['token'];
        }
        $nbr_media = (isset($atts['nb']) ? $atts['nb'] : 4);
        $insta_api = new InstagramApi([
            'access_token' => $access_token,
            'user_id' => $user_id
        ], $app_id, $app_secret);
        $medias = $insta_api->getUserMedia(); 
        if (!empty($medias['data']) && !empty($content)) {
            $all_content = '';
            $medias = array_slice($medias['data'],0,$nbr_media);
            foreach ($medias as $media) { 
                $media_src = $media['media_url'];
                $media_url = $media['permalink'];
                if ($media['media_type'] == 'VIDEO') { 
                    $media_src = $media['thumbnail_url'];
                }

                $all_content .= str_replace(array('{_nl_insta_img_}', '{_nl_insta_link_}'), array($media_src, $media_url), $content);
            }
            $content = $all_content;
        }
        return $content;
    }
function instagram_refresh_access_token(){
    
    if (!empty($_GET['action']) && $_GET['action'] == 'instagram_refresh_access_token' && !empty($_GET['site'])) {

        $site = $_GET['site'];
        $app_id = get_instagram_config($site, 'app_id');
        $app_secret = get_instagram_config($site, 'app_secret');

        $site_insta_setting = get_option($site . '_insta_setting');
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