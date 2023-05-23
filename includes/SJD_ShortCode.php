<?php

declare(strict_types=1);


class SJD_ShortCode {

    public static function init(){

        update_option('subscriber_url', self::get_subscriber_url());

        // Handle inputs
        $submit = false;

        if ( isset($_POST['SUBMIT']) ) {
            // Check the nonce;
            if ( !isset($_POST['_sjd_subscribe_nonce']) ||
                 wp_verify_nonce( $_POST['_sjd_subscribe_nonce'], 'sjd_subscribe_submit' ) !== 1) {
                echo "<p>Whoops - something went wrong. Please try again but if this problem 
                         persists please let us know.</p>";
                return;
            }

            $submit = $_POST['SUBMIT'];

        // USER VALIDATION
        } else if ( isset($_REQUEST['validate']) && 
                    isset($_REQUEST['key']) && 
                    isset($_REQUEST['email']) ){

            $subscriber = self::validate_subscription($_REQUEST);
            if( $subscriber ){ 
                echo "<p>Your subscription was validated! We will let you know when new content 
                         is added to the site.</p>";
                SJD_Notifications::send_new_subscriber_email($subscriber);
            } else {
                echo "<p>We had a problem validating your subscription. 
                         It is possible that the validation link in your email was split 
                         across multiple lines. If this is the case, please copy and paste into
                         notepad or other plain text editor, remove the line break and then 
                         copy and paste the full url into the browser address bar and then
                         press enter.</p>";
            }
            return;

        // USER UNSUBSCRIBE
        } else if ( isset($_REQUEST['unsubscribe']) && 
                    isset($_REQUEST['id']) && 
                    isset($_REQUEST['email']) ){

            $subscriber = self::unsubscribe($_REQUEST);
            if( $subscriber ){ 
                echo "<h2>We are sorry to see you go!</h2>";
                echo "<p>Your subscription has been cancelled. 
                         You will no longer receive emails notifications 
                         when new content is added to the site. You may 
                         subscribe again at any time.</p>";
                SJD_Notifications::send_cancelled_subscriber_email($subscriber);
            } else {
                echo "<p>We had a problem cancelling your subscription.</p>";
            }
            return;
        }

        $subscriber = self::user_form($submit);
    }


    public static function get_subscriber_url(){
        // Save the page url where the shortcode is used for using in notification emails
        $domain = get_bloginfo('url');
        global $post;
        return "$domain/$post->post_name";
    }

    private static function user_form( $submitted ){ 
        // $clean = array( "first_name"=>"Steve", "last_name"=>"Davison", "email"=>"stephenjohndavison@gmail.com" );
        // $errors = array( "first_name"=>"", "last_name"=>"", "email"=>"" );
        $location = get_option("subscriber_location");
        $clean = array( "first_name"=>"", "last_name"=>"", "email"=>"" );
        $errors = array( "first_name"=>"", "last_name"=>"", "email"=>"" );
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
                    self::confirmation( $subscriber );
                    return;
                }
            }

        } ?>
        <p>Enter details below and then click Register. All fields are required<?= $location?" (apart from location)":""?>.</p>
        <form id="sjd-subscribe" method="post">
            <?php foreach( $clean as $key => $value) { 
                $label = str_replace('_',' ',$key);
                $type = $key=='email' ? 'email' : 'text'; ?>

                <?php if ( $location && $key === 'location' ) { ?>
                    <p class="sjd-form-advice">If you want to be put in touch with like minded people in your area please provide a location to whatever level of detail you feel comfortable with, e.g. North West England or Liverpool.</p>
                <?php } ?>

                <div class="form-field">
                    <label for="<?= $key ?>"><?= $label ?></label>
                    <input type="<?=$type?>" name="<?=$key?>" value="<?=$value?>" class="<?=$errors[$key]?'error':'';?>"/>
                </div>
                <?php if ( $errors[$key] ) { ?>
                    <div class="form-field error"><?= $errors[$key] ?></div>
                <?php } ?>
            <?php } ?>
            <?php if ( $error ) { ?>
                <div class="form-field error"><?= $error ?></div>
            <?php } ?>
            <div class="form-field submit">
                <?php if ( $resend ){ ?>
                    <button type="submit" name="SUBMIT" value="RESEND" style="margin-right:1rem;">Resend</button>  
                <?php } ?>
                <button type="submit" name="SUBMIT" value="REGISTER">Register</button>
            </div>           
            <?php wp_nonce_field('sjd_subscribe_submit','_sjd_subscribe_nonce'); ?>      
        </form>

        <h2>We respect your privacy</h2>
        <p>Please note that as per our privacy policy your data will NOT be shared with or sold to third-parties and will be used solely to keep you up to data with our news.</p>

    <?php 
    }



    static function confirmation($subscriber){ 
        $status = SJD_Notifications::send_subscribe_email(
            $subscriber->ID, 
            $subscriber->first_name, 
            $subscriber->email, 
            $subscriber->validation_key
        );
        if ( !is_wp_error( $status) ){
            echo "<h2>Nearly there $subscriber->first_name!</h2>";
            echo "<p>We've sent you an email to $subscriber->email - please click on the link inside to confirm your subscription.</p>";
            echo "<p>If you don't receive the message in the next few minutes please check your spam folder.</p>";
            $send_email = str_replace('@', ' AT ', SMTP_USER);
            echo "<p>You can help by adding the email address <strong>'$send_email'</strong> to your address book. Replace the ' AT ' with the usual '@'.</p>";
        }
    }

    private static function resend_form(){ ?>
        <form id="notify" method="post">
            <p>
                <button type="submit" name="SUBMIT" id="SUBMIT" value="RESEND">Resend link</button> 
            </p>   
            <?php wp_nonce_field('sjd_subscribe_submit','_sjd_subscribe_nonce'); ?>      
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