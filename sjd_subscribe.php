<?php
/**
 * Plugin Name: SJD Subscribe
 * Version: 0.11
 * Modified: 19 March 2024
 * Author: Steve Davison
 * Description: Provide simple subscription solution to register subscribers and manage 
 * email notifications for when new content is added
 */

DEFINE( "SJD_SUBSCRIBE_VERSION", '0.0.11');
DEFINE( "SJD_SUBSCRIBE_IMAGE", plugins_url('/sjd-subscriber-plugin/images/email.jpg'));
DEFINE( 'SJD_SUBSCRIBE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
DEFINE( 'SJD_SUBSCRIBE_TEMPLATES_PATH', SJD_SUBSCRIBE_PLUGIN_PATH . 'templates/' );

REQUIRE_ONCE (SJD_SUBSCRIBE_PLUGIN_PATH . 'includes/SJD_email.php');
REQUIRE_ONCE (SJD_SUBSCRIBE_PLUGIN_PATH . 'includes/SJD_ShortCode.php');
REQUIRE_ONCE (SJD_SUBSCRIBE_PLUGIN_PATH . 'includes/SJD_Subscriber.php');
REQUIRE_ONCE (SJD_SUBSCRIBE_PLUGIN_PATH . 'includes/SJD_Notifications.php');
REQUIRE_ONCE (SJD_SUBSCRIBE_PLUGIN_PATH . 'includes/SJD_Settings.php');

add_action( 'init', 'sjd_subscribe_init');
function sjd_subscribe_init(){
    // wp_enqueue_script('test', plugins_url("includes/test.js", __FILE__));
    wp_enqueue_style('sjd_subscribe_form', plugins_url("styles.css", __FILE__), [], SJD_SUBSCRIBE_VERSION);
    add_shortcode('sjd_subscribe_form', 'SJD_ShortCode::init');
    SJD_Subscriber::init();
    SJD_Settings::init();
}


// Add Subscriber Tools menu to the standard admin Tools menu
add_action( 'admin_menu', 'sjd_subscriber_tools_admin_menu' );
function sjd_subscriber_tools_admin_menu(){
    add_management_page( 'Subscriber Tools', 'Subscriber Tools', 'administrator', 'sjd_subscriber_tools', 'sjd_subscriber_tools_do_page' );
}
function sjd_subscriber_tools_do_page(){ ?>
    <style>
        h3 { margin-top:2rem }
    </style>
    <h2>Manage subscribers</h2>
    <h3>Export CSV file</h3>
    <form method="POST" action="">
        <input type="hidden" name="export" value="export" />
        <input type="submit" value="Export subscribers" />
    </form>
    <?php if (isset($_POST['export'])) {
        echo "<p>Running export...</p>";
        SJD_Subscriber::export_subscribers();
    } ?>
    <?php if ( SJD_Subscriber::download_file_exists() ){ ?>
        <h3>Remove temporary download file (in wp-amin folder)</h3>
        <form method="POST" action="">
            <input type="hidden" name="remove" value="remove" />
            <input type="submit" value="Remove download file" />
        </form>
        <?php if ( isset($_POST['remove']) ){
            if ( SJD_Subscriber::remove_download_file() ) {
                echo "<p>Removed download file</p>";
            } else {
                echo "<p>No download file found</p>";
            }
        } ?>
    <?php } ?>
<?php } ?>
