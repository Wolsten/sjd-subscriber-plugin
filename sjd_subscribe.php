<?php
/**
 * Plugin Name: SJD Subscribe
 * Version: 0.0.6
 * Author: Steve Davison
 * Description: Provide simple subscription solution to register subscribers and manage 
 * email notifications for when new content is added
 */

DEFINE( "SJD_SUBSCRIBE_VERSION", '0.0.6');
DEFINE( "SJD_SUBSCRIBE_IMAGE", plugins_url('sjd_subscribe_plugin/images/email.jpg'));
DEFINE( 'SJD_SUBSCRIBE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
DEFINE( 'SJD_SUBSCRIBE_TEMPLATES_PATH', SJD_SUBSCRIBE_PLUGIN_PATH . 'templates/' );

REQUIRE_ONCE (SJD_SUBSCRIBE_PLUGIN_PATH . 'includes/SJD_email.php');
REQUIRE_ONCE (SJD_SUBSCRIBE_PLUGIN_PATH . 'includes/SJD_ShortCode.php');
REQUIRE_ONCE (SJD_SUBSCRIBE_PLUGIN_PATH . 'includes/SJD_Subscriber.php');
REQUIRE_ONCE (SJD_SUBSCRIBE_PLUGIN_PATH . 'includes/SJD_Notifications.php');
REQUIRE_ONCE (SJD_SUBSCRIBE_PLUGIN_PATH . 'includes/SJD_Settings.php');





add_action( 'init', 'sjd_subscribe_init');
function sjd_subscribe_init(){
    wp_enqueue_style('sjd_subscribe_form', plugins_url("styles.css", __FILE__), [], SJD_SUBSCRIBE_VERSION);
    add_shortcode('sjd_subscribe_form', 'SJD_ShortCode::init');
    SJD_Subscriber::init();
    SJD_Settings::init();
}



// Add send notifications button to post edit pages
add_action('post_submitbox_start','sjd_post_notify_button');
function sjd_post_notify_button(){
    // Check for subscriber url
    $subscriber_url = get_option('subscriber_url');
    if ( $subscriber_url == '' ){ ?>
        <div style="margin-bottom:0.5rem;padding:0.5rem;background-color:red;color:white;">
            <p>You must add the sjd_subscribe_form shortcode to a post or page and view that page before you can begin sending notifications.</p>
        </div>
        <?php return;
    }
    // Add notification functionality to the meta publish box
    global $post;
    if ( $post->post_status !== 'publish' ) return;
    if ( $post->post_type === SJD_Subscriber::POST_TYPE ) return; ?>
    <style>
        .sjd-post-notify {
            margin:1.5rem 0;
            padding:0.5rem;
            background-color:#62c753;
            border-radius:0.3rem;
        }
        .sjd-post-notify label {
            margin-top:0.5rem;
            color:white;
            display:block;
            padding-bottom:0.3rem;
        }
        .sjd-post-notify p {
            color:white;
        }
    </style>
    <div class="sjd-post-notify">
        <label for="sjd-notify-subscribers">Notify subscribers on save?</label>
        <select name="sjd-notify-subscribers" id="sjd-notify-subscribers">
            <option value="false">Do not notify</option>
            <option value="LINK">Send links</option>
            <option value="PAGE">Send full page</option>
        </select>
        <label for="sjd-min-list-number">Start from list no (debugging only)</label>
        <input type="number" name="sjd-min-list-number" id="sjd-min-list-number" value="1" min="1" max="1000"/>
    </div>
<?php }


// On post save check for sending notifications
add_action('save_post', 'sjd_post_notify_subscribers');
function sjd_post_notify_subscribers(){
    global $post;
        if ( $post ) {
        if ( $post->post_status !== 'publish' ) return;
        if ( $post->post_type === SJD_Subscriber::POST_TYPE ) return;
        if ( isset($_POST['sjd-notify-subscribers']) == false ) return;
        if ( isset($_POST['sjd-min-list-number']) == false ) ;
        $min = isset($_POST['sjd-min-list-number']) ? intval($_POST['sjd-min-list-number']) : 1;
        $what = $_POST['sjd-notify-subscribers'];
        if ( $what == 'LINK' || $what == 'PAGE' ){
            SJD_Notifications::send($post->ID, $what, $min);
            die();
        }
    }
}



?>
