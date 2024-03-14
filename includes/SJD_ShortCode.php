<?php


declare(strict_types=1);

define( 'NONCE_NAME','_sjd_subscribe_nonce');


class SJD_ShortCode {

    public static function init(){

        $html = '';
        
        $url = self::get_subscriber_url();
        
        update_option('subscriber_url', $url);
        
        // Handle inputs
        $submit = false;
        
        if ( isset($_POST['SUBMIT']) ) {
            // Check the nonce;
            
            if ( wp_verify_nonce( $_POST[NONCE_NAME], 'sjd_subscribe_submit' ) !== 1) {
                $nonce = $_POST[NONCE_NAME];
                $html .= "<h2>Whoops - something went wrong</h2>
                          <p>Please try again but if this problem persists please let us know. Nonce = $nonce</p>";
            }

            $submit = $_POST['SUBMIT'];

        // USER VALIDATION
        } else if ( isset($_REQUEST['validate']) && 
                    isset($_REQUEST['key']) && 
                    isset($_REQUEST['email']) ){

            $subscriber = self::validate_subscription($_REQUEST);

            if( $subscriber ){ 
                SJD_Notifications::send_new_subscriber_email($subscriber);
                return "<h2>Your subscription was validated!</h2>
                          <p>We will let you know when new content is added to the site.</p>";
                

            } else {
                return "<h2>We had a problem validating your subscription</h2>
                         <p>It is possible that the validation link in your email was split 
                         across multiple lines. If this is the case, please copy and paste into
                         notepad or other plain text editor, remove the line break and then 
                         copy and paste the full url into the browser address bar and then
                         press enter.</p>";
            }

        // USER REQUEST UNSUBSCRIBE
        // http://localhost/newsletter/?unsubscribe&id=3084&email=stephenjohndavison@gmail.com
        } else if ( isset($_REQUEST['unsubscribe']) && 
                    isset($_REQUEST['id']) && 
                    isset($_REQUEST['email']) ){

            $subscriber = self::get_subscriber($_REQUEST);
            $id = $_REQUEST['id'];
            $email = $_REQUEST['email'];

            if( $subscriber ){ 
                return "<h2>We would be sorry to see you go!</h2>
                        <p>If you are sure, please click <a href='$url?confirm_unsubscribe&id=$id&email=$email'>here</a> to confirm you want to cancel your subscription.</p>";
            } else {
                return "<h2>We had a problem finding your subscription</h2>
                        <p>Are you sure that you haven't already unsubscribed with this email?</p>";
            }

        // USER CONFIRM UNSUBSCRIBE
        } else if ( isset($_REQUEST['confirm_unsubscribe']) && 
                    isset($_REQUEST['id']) && 
                    isset($_REQUEST['email']) ){

            $subscriber = self::unsubscribe($_REQUEST);

            if( $subscriber ){ 
                return "<h2>We are sorry to see you go!</h2>
                        <p>Your subscription has been cancelled. 
                         You will no longer receive emails notifications 
                         when new content is added to the site. You may 
                         subscribe again at any time.</p>";
                SJD_Notifications::send_cancelled_subscriber_email($subscriber);
            } else {
                return "<h2>We had a problem cancelling your subscription</h2>
                        <p>Are you sure that you haven't already unsubscribed with this email?</p>";
            }
        }

        return $html . self::user_form($submit);
    }


    public static function get_subscriber_url(){
        // Save the page url where the shortcode is used for using in notification emails
        $domain = get_bloginfo('url');
        global $post;
        return "$domain/$post->post_name";
    }


    private static function user_form( $submitted ){ 

        $clean = array( "first_name"=>"", "last_name"=>"", "email"=>"" );
        $errors = array( "first_name"=>"", "last_name"=>"", "email"=>"" );
        // $clean = array( "first_name"=>"Steve", "last_name"=>"Davison", "email"=>"stephenjohndavison@gmail.com" );
        // $errors = array( "first_name"=>"", "last_name"=>"", "email"=>"" );

        $location = get_option("subscriber_location");
        if ( $location ){
            $clean['location'] = "";
            $errors['location'] = "";
        } 
        $resend = false;
        $error = '';
        
        if ( $submitted ){

            $results = SJD_Subscriber::validate_fields($_POST);
            $status = $results['status'];
            $clean = $results['clean'];
            $errors = $results['errors'];

            if ( $results['status'] == '1' ){

                // Already subscribed?
                $subscriber = SJD_Subscriber::get($clean['email']);
                if ( $subscriber ){
                    // If the record fully matched let them know that already subscribed
                    if ( $clean['first_name'] === $subscriber->first_name && 
                         $clean['last_name']  === $subscriber->last_name ){

                        if ( $subscriber->post_status == 'draft' ){
                            $resend = true;
                            $error = 
                                "You have already asked to subscribe - please check your email for 
                                 our validation message. Alternatively, click Resend to send a new 
                                 validation email.";
                        } else {
                            $error = "You are already subscribed. No further action is required.";
                        }
                    // Phishing?
                    } else {
                        $error = "Whoops something went wrong sending our confirmation email.";
                    }
                } 
                
                if ( $subscriber == false && $submitted == "REGISTER" ){
                    $subscriber = SJD_Subscriber::create($clean);
                }

                if ( ($error=='' && $subscriber) || $submitted == "RESEND"){
                    return self::confirmation( $subscriber );
                }
            }

        } // End Submitted

        $loc = $location ? ' (apart from location)' : '';
        $html = "<p>Enter details below and then click Register. All fields are required$loc.</p>
                 <form id='sjd-subscribe' method='post'>";

        foreach( $clean as $key => $value) { 
            $label = str_replace('_',' ',$key);
            $type = $key=='email' ? 'email' : 'text';

            if ( $location && $key === 'location' ) {
                $html .= "<p class='sjd-form-advice'>If you want to be put in touch with like minded people in your area please provide a location to whatever level of detail you feel comfortable with, e.g. North West England or Liverpool.</p>";
            }

            $errorString = $errors[$key] ? 'error' : '';
            $html .= "
                <div class='form-field'>
                    <label for='$key'>$label</label>
                    <input type='$type' name='$key' value='$value' class='$errorString'/>
                </div>";

            if ( $errors[$key] ) {
                $html .= "<div class='form-field error'>$errors[$key]</div>";
            } 
        }

        if ( $error ) { 
            $html .= "<div class='form-field error'>$error</div>";
        }
        $html .= "
            <div class='form-field submit'>";

        if ( $resend ){
            $html .= "<button type='submit' name='SUBMIT' value='RESEND' style='margin-right:1rem;'>Resend</button>";
        }

        $html .= "
                <button type='submit' name='SUBMIT' value='REGISTER'>Register</button>
            </div>";
        $html .= wp_nonce_field($action_name='sjd_subscribe_submit',$name=NONCE_NAME,$referrer=true,$display=false);
        $html .= "
            </form>
            <h2>We respect your privacy</h2>
            <p>Please note that as per our privacy policy your data will NOT be shared with or sold to third-parties and will be used solely to keep you up to data with our news.</p>";

        return $html;
    }



    static function confirmation($subscriber){ 
        $status = SJD_Notifications::send_subscribe_email(
            $subscriber->ID, 
            $subscriber->first_name,
            $subscriber->post_title, // This is their email
            $subscriber->validation_key
        );
        if ( is_wp_error( $status ) ){
            return "<p>There was an error sending your email</p>";
        }
        $send_email = str_replace('@', ' AT ', SMTP_USER);
        return "<h2>Nearly there $subscriber->first_name!</h2>
            <p>We've sent you an email to $subscriber->post_title - please click on the link inside to confirm your subscription.</p>
            <p>If you don't receive the message in the next few minutes please check your spam folder.</p>
            <p>You can help by adding the email address <strong>$send_email</strong> to your address book. Replace the ' AT ' with the usual '@'.</p>";
    }

    private static function resend_form(){ ?>
        <form id="notify" method="post">
            <p>
                <button type="submit" name="SUBMIT" id="SUBMIT" value="RESEND">Resend link</button> 
            </p>   
            <?php wp_nonce_field('sjd_subscribe_submit',NONCE_NAME); ?>      
        </form>
    <?php }


    static function validate_subscription($request){
        $clean = array(
            'key' => $request['key'],
            'email' => sanitize_email($request['email'])
        );

        // If have values then check against registered subscriber
        if ( $clean['email'] && $clean['key'] ){

            $subscriber = SJD_Subscriber::get($clean['email']);

            if ( $subscriber ){
                // Get the validation key form the user meta data
                // If match then set the user as validated by setting role to subscriber
                // echo "<p>User Key = $subscriber->validation_key</p>";
                // echo "<p>Email Key = ". $clean['key'] ."</p>";
                if ( $subscriber->validation_key == $clean['key']){
                    // echo "Keys matched";
                    if ( SJD_Subscriber::validate($subscriber->ID) ){
                        return $subscriber;
                    }
                }
            }
        }
        return false;
    }   

    private static function get_subscriber($request){
        $email = sanitize_email($request['email']);
        return SJD_Subscriber::get($email);
    }

    private static function unsubscribe($request){
        $clean = array(
            'user_id' => $request['id'],
            'email' => sanitize_email($request['email'])
        );
        // If have values then check against registered subscriber
        if ( $clean['email'] && $clean['user_id'] ){
            $subscriber = SJD_Subscriber::get($clean['email']);
            if ( $subscriber ){
                if( wp_delete_post($subscriber->ID, $force_delete=true) ){
                    return $subscriber;
                }
            }
        }
        return false;
    }

    public static function print($label,$value){
        echo "<p>$label:<br>";
        print_r($value);
        echo "</br></p>";
    }

}