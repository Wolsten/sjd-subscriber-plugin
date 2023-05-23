<?php 


/* Ionos Mail Limits

The number of emails per hour per contract depends on the age of the mailbox being used:

Days    Per hour
0-7        50
8-14      100
15-30     400
30+     5,000

Therefore, need to plan in advance or consider blind copying to multiple recipients, for
which there is a limit of 200 per email. */

// show wp_mail() errors
add_action( 'wp_mail_failed', 'onMailError', 10, 1 );
function onMailError( $wp_error ) {
    echo "<pre>";
    print_r($wp_error->errors);
    // A typical error looks like this:
    // SMTP Error: The following recipients failed: leaversofburnley@gmail.com: Requested mail action not taken: mailbox unavailable
    // Mail send limit exceeded.
    echo "</pre>";
}  


class SJD_Notifications {

    // private const DEBUG_EMAIL = "stephenjohndavison@gmail.com"; // Set to empty string for normal operation
    private const DEBUG_EMAIL = "";


    public static function send($post_id, $what, $min){
        $post = get_post($post_id);
        echo "<div style='margin:2rem;'>";
        echo "<h1>Sending notifications</h1>";
        echo "<p>Sending $what notification emails for post [$post_id] <strong>$post->post_title</strong></p>";
        // Check for shortcodes in the content
        if ( $what=='PAGE' ){
            $regex = '/^\[.{5,}\]/m';
            $str = $post->post_content;
            if ( preg_match($regex, $str)==1 ){
                echo "<p>Could not send this content because it looks like it contains at least one shortcode, e.g. [name ....].</p>";
                echo "<p>You cannot send page content with embedded shortcodes as they may generate dynamic content that is not available except via the web page but you can send as a link instead.</p>";
                echo "<a href='/wp-admin/post.php?post=$post->ID&action=edit'>Back to post</a>";
                echo "</div>";
                return;
            }
        }
        // Construct the generic part of the notification
        $message = self::get_notification_message($post, $what);
        // Get all subscribers
        $subscribers = SJD_Subscriber::all();
        $i = 0;
        $skipped = 0;
        $good = 0;
        $bad = 0;
        $stop_on_first_fail = (bool) get_option('subscriber_stop_on_first_fail')=='1';
        echo "<p>Sending emails, skipping those below $min.</p>";
        echo "<ol>";
        foreach( $subscribers as $subscriber ){
            $i++;
            if ( self::DEBUG_EMAIL != "" ){
                $email = self::DEBUG_EMAIL;
            } else {
                $email=$subscriber->post_title;
            }
            $entry = "[$subscriber->ID] $subscriber->first_name $subscriber->last_name ($email)";
            if ( $i < $min ){
                $skipped ++;
                echo "<li>$entry - SKIPPED</li>";
            } else if ($bad==0 || $stop_on_first_fail==false ) {

                $status = self::send_notification_email($message, $subscriber->ID, $subscriber->first_name, $email, $post, $what);
                if ( $status ){
                    $good ++;
                    echo "<li style='color:green;'>$entry - SENT</li>";
                } else {
                    $bad ++;
                    echo "<li style='color:red;'>$entry - FAILED!</li>";
                }
            }
        }
        echo "</ol>";// 
        echo "<p>Tried to send $i emails: $good succeeded, $bad failed.</p>";
        echo "<a href='/wp-admin/post.php?post=$post->ID&action=edit'>Back to post</a>";
        echo "</div>";
    }


    public static function send_subscribe_email( $subscriber_id, $first_name, $email, $validation_key){

        $domain = get_bloginfo('url');
        $url = get_option('subscriber_url');
        $name = get_bloginfo('name');
        $subject = "Confirm your subscription to $name";
        $headers = array("Content-Type: text/html; charset=UTF-8");
        $link = "$url?validate&email=$email&key=$validation_key";
        $img = self::image('');

        $html = file_get_contents(  SJD_SUBSCRIBE_TEMPLATES_PATH . 'request_subscription_template.html');

        $html = str_replace( '$name', $name, $html);
        $html = str_replace( '$img', $img, $html);
        $html = str_replace( '$style',self::style(), $html);
        $html = str_replace( '$logo', self::logo(), $html);
        $html = str_replace( '$first_name',$first_name, $html);
        $html = str_replace( '$link',$link, $html);
        $html = str_replace( '$domain',get_bloginfo('url'), $html);

        return wp_mail( $email, $subject, $html, $headers);
    }


    public static function send_new_subscriber_email( $subscriber ){
        $name = get_bloginfo('name');
        $subject = "New subscription to $name";
        $headers = array("Content-Type: text/html; charset=UTF-8");
        $email = get_option('notify_on_subscribe_email');

        $html = file_get_contents(  SJD_SUBSCRIBE_TEMPLATES_PATH . 'new_subscriber_template.html');

        $html = str_replace( '$name', $name, $html);
        $html = str_replace( '$style', self::style(), $html);
        $html = str_replace( '$subscriber_id', $subscriber->ID, $html);

        $html = str_replace( '$logo', self::logo(), $html);
        $html = str_replace( '$subscriber_first_name', $subscriber->first_name, $html);
        $html = str_replace( '$subscriber_last_name', $subscriber->last_name, $html);
        $html = str_replace( '$subscriber_email', $subscriber->email, $html);
        $html = str_replace( '$subscriber_location', $subscriber->location ? $subscriber->location : 'UNSPECIFIED', $html);

        $html = str_replace( '$domain', get_bloginfo('url'), $html);
        
        return wp_mail( $email, $subject, $html, $headers);
    }


    public static function send_cancelled_subscriber_email( $subscriber ){
        $name = get_bloginfo('name');
        $subject = "Cancelled subscription to $name";
        $headers = array("Content-Type: text/html; charset=UTF-8");
        $email = get_option('notify_on_subscribe_email');

        $html = file_get_contents(  SJD_SUBSCRIBE_TEMPLATES_PATH . 'cancelled_subscriber_template.html');

        $html = str_replace( '$name', $name, $html);
        $html = str_replace( '$style', self::style(), $html);
        $html = str_replace( '$subscriber_id', $subscriber->ID, $html);

        $html = str_replace( '$logo', self::logo(), $html);
        $html = str_replace( '$subscriber_first_name', $subscriber->first_name, $html);
        $html = str_replace( '$subscriber_last_name', $subscriber->last_name, $html);
        $html = str_replace( '$subscriber_email', $subscriber->email, $html);
        $html = str_replace( '$subscriber_location', $subscriber->location ? $subscriber->location : 'UNSPECIFIED', $html);

        $html = str_replace( '$domain', get_bloginfo('url'), $html);
        
        return wp_mail( $email, $subject, $html, $headers);
    }


    public static function image($post=''){
        // Use post thumbnail of has one
        $img = SJD_SUBSCRIBE_IMAGE; // Default image
        if ( $post && has_post_thumbnail($post) ){
            $img = get_the_post_thumbnail_url($post->ID,$size="large");
        // Fall back to image from plugin settings
        } else if ( get_option('subscriber_email_image') ) {
            $img = get_option('subscriber_email_image');
        }
        return $img;
    }


    public static function get_notification_message($post, $what){
        $img = self::image($post);
        $message = '';
        $from = get_bloginfo('name');
        $name = $post->post_title;
        $domain = get_bloginfo('url');
        if ( $what === 'PAGE' ){
            $content = self::format($post->post_content);
            $message ="<p class='excerpt'>$post->post_excerpt</p>$content";
        } else {
            $message = 
                "<p>$post->post_excerpt</p>
                 <p><a href='$domain/$post->post_name'>Click here to find out more</a>.</p>";
        }

        $html = file_get_contents(  SJD_SUBSCRIBE_TEMPLATES_PATH . 'new_content_notification_template.html' );

        $html = str_replace( '$name', $name, $html);
        $html = str_replace( '$from', $from, $html);
        $html = str_replace( '$img', $img, $html);
        $html = str_replace( '$style', self::style(), $html);
        $html = str_replace( '$logo', self::logo(), $html);
        $html = str_replace( '$message', $message, $html);
        $html = str_replace( '$twitter', self::twitter(), $html);
        $html = str_replace( '$contact_email',self::contact_email(), $html);

        return $html;
    }


    public static function send_notification_email($html, $subscriber_id, $first_name, $email){
        $headers = array("Content-Type: text/html; charset=UTF-8"); // Send in html format
        $subject = "New content added to ".get_bloginfo('name');
        // Subscriber specific details
        $html = str_replace( '$first_name', $first_name, $html);
        $html = str_replace( '$subscriber_url', get_option('subscriber_url'), $html);
        $html = str_replace( '$subscriber_id', $subscriber_id, $html);
        $html = str_replace( '$subscriber_email', $email, $html);
        return wp_mail( $email, $subject, $html, $headers);
    }


    private static function logo(){
        $logo = "";
        if ( function_exists('sjd_config') ){ 
            $logo = sjd_config('logo');
            if ( str_contains($logo,"img") === false ){
                $logo = "";
            } else {
                $logo = "<div class='site-logo'>$logo</div>";
            }
        }
        return $logo;
    }


    private static function style(){
        $primary_colour = get_option('subscriber_email_primary_colour');
        $excerpt_font_colour = get_option('subscriber_email_excerpt_font_colour');
        $styling = file_get_contents(  SJD_SUBSCRIBE_TEMPLATES_PATH . 'template_styles.css' );
        $styling = str_replace('$primary_colour',$primary_colour,$styling);
        $styling = str_replace('$excerpt_font_colour',$excerpt_font_colour,$styling);
        return "<style>$styling</style>";
    }


    private static function twitter(){
        $twitter = '';
        if ( function_exists('sjd_config') ){ 
            $twitter = sjd_config('twitter-handle');
            if ( str_contains($twitter,"@") === false ){
                $twitter = "";
            } else {
                $slug = str_replace('@','',$twitter);
                $twitter = "You may also want to follow us on Twitter at <a href='https://twitter.com/$slug'>$twitter</a>.";
            }
        }
        return $twitter;
    }

    private static function contact_email(){
        $email = get_option('contact_email');
        if ( $email ){
            $email = "Contact us via email at <a href='mailto:$email'>$email</a>.";
        }
        return $email;
    }


    private static function format($content){
        // Wrap all none tagged lines and ones that don't start with a link in 
        // paragraph tags
        $lines = explode(PHP_EOL,$content);
        $html = '';
        foreach( $lines as $line ){
            if ( str_starts_with( $line, '<') &&
                 str_starts_with( $line, '<A') == false && 
                 str_starts_with( $line, '<a') == false){
                $html .= $line;
            } else if ( $line != '' ) {
                $html .= "<p>$line</p>";
            }
        }
        return $html;
    }


}