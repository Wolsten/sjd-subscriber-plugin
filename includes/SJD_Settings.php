<?php

class SJD_Settings {

    private const SETTINGS = array(
        array(
            'name'=>'subscriber_stop_on_first_fail',
            'default' => 1
        ),
        array(
            'name'=>'contact_email',
            'default' => ''
        ),
        array(
            'name'=>'subscriber_email_image',
            'default' => SJD_SUBSCRIBE_IMAGE
        ),
        array(
            'name'=>'subscriber_email_primary_colour',
            'default' => 'gray'
        ),
        array(
            'name'=>'subscriber_email_excerpt_font_colour',
            'default' => 'white'
        ),
        array(
            'name'=>'subscriber_url',
            'default' => ''
        ),
        array(
            'name'=>'notify_on_subscribe_email',
            'default' => ''
        ),
        array(
            'name'=>'subscriber_location',
            'default' => 0
        )
    );

    public static function init(){
        add_action('admin_menu', 'SJD_Settings::admin_menu');
        add_action('admin_init', 'SJD_Settings::register_settings');
    }

    public static function admin_menu(){
        add_menu_page('Subscriber Plugin','Subscriber', 'manage_options', 'subscriber_menu', 'SJD_Settings::page' ); 
    }


    public static function register_settings(){
        foreach ( self::SETTINGS as $setting ){
            register_setting('subscriber-settings-group', $setting['name'], array(
                'default' => $setting['default'],
                'show_in_rest' => false
            ));
        }
    }

    public static function page(){ 
        $subscriber_stop_on_first_fail = get_option('subscriber_stop_on_first_fail') == '1';
        $subscriber_email_image = get_option('subscriber_email_image');
        $subscriber_email_primary_colour = get_option('subscriber_email_primary_colour');
        $subscriber_email_excerpt_font_colour = get_option('subscriber_email_excerpt_font_colour');
        $notify_on_subscribe_email = get_option('notify_on_subscribe_email');
        $contact_email = get_option('contact_email');
        $subscriber_location = get_option('subscriber_location');

        if ( isset($_POST['subscriber_stop_on_first_fail']) ){
            $subscriber_stop_on_first_fail = $_POST['subscriber_stop_on_first_fail'] == '1';
            update_option('subscriber_stop_on_first_fail', $subscriber_stop_on_first_fail);
        }

        if ( isset($_POST['subscriber_location']) ){
            $subscriber_location = $_POST['subscriber_location'] == '1';
            update_option('subscriber_location', $subscriber_location);
        }

        if ( isset($_POST['subscriber_email_image']) ){
            $subscriber_email_image = sanitize_text_field($_POST['subscriber_email_image']);
            update_option('subscriber_email_image', $subscriber_email_image);
        }

        if ( isset($_POST['notify_on_subscribe_email']) ){
            $notify_on_subscribe_email = sanitize_text_field($_POST['notify_on_subscribe_email']);
            update_option('notify_on_subscribe_email', $notify_on_subscribe_email);
        }

        if ( isset($_POST['contact_email']) ){
            $contact_email = sanitize_text_field($_POST['contact_email']);
            update_option('contact_email', $contact_email);
        }

        if ( isset($_POST['subscriber_email_primary_colour']) ){
            $subscriber_email_primary_colour = sanitize_text_field($_POST['subscriber_email_primary_colour']);
            update_option('subscriber_email_primary_colour', $subscriber_email_primary_colour);
        }

        if ( isset($_POST['subscriber_email_excerpt_font_colour']) ){
            $subscriber_email_excerpt_font_colour = sanitize_text_field($_POST['subscriber_email_excerpt_font_colour']);
            update_option('subscriber_email_excerpt_font_colour', $subscriber_email_excerpt_font_colour);
        }
        
        ?>

        <style>
            .subscriber-settings p label{
                display:inline-block;
                width: 200px;
            }
            .subscriber-settings input[name=subscriber_email_image],
            .subscriber-settings input[name=notify_on_subscribe_email],
            .subscriber-settings input[name=contact_email] {
                width:600px;
            }
            .subscriber-settings img {
                width:100%;
                height:auto;
            }
            .subscriber-settings table th {
                text-align:right;
            }
            .subscriber-settings .error {
                color:red;
            }
        </style>

        <div class="wrap subscriber-settings">
            <h1>Subscriber Administration</h1>
            <form method="post">
            <?php settings_fields('subscriber-settings-group'); ?>

                <!-- HANDLE FAIL SETTING -->
                <h2>Handle failures</h2>
                <p>Choose whether to stop on first email failure or keep going.</p>
                <p>
                    <label for="subscriber_stop_on_first_fail">Stop on first fail</label>
                    <input type="radio" name="subscriber_stop_on_first_fail" 
                           value="1" <?=$subscriber_stop_on_first_fail ? 'checked' : ''?>/> On
                    <input type="radio" name="subscriber_stop_on_first_fail" 
                           value="0" <?=$subscriber_stop_on_first_fail==false ? 'checked' : ''?>/> Off
                </p>

                <!-- CONTENT SETTINGS -->
                <h2>Email content settings</h2>

                <p>Choose which email to provide for contact. If none specified it is assumed that this will not be displayed to the visitor.</p>
                <p>
                    <label for="contact_email">Contact email</label>
                    <input type="text" name="contact_email" value="<?=$contact_email?>"/>
                </p>

                <?php // @todo See https://jeroensormani.com/how-to-include-the-wordpress-media-selector-in-your-plugin/ for hpow to add the media selector?>
                <p>Choose an image. Needs to be a full valid url. You can copy a url from the Media library.</p>
                <p>
                    <label for="subscriber_email_image">Emails image <img src="<?=$subscriber_email_image?>" alt="email image"></label>
                    <input type="text" name="subscriber_email_image" value="<?=$subscriber_email_image?>"/>
                </p>

                <p>Choose a primary colour for titles and the post excerpt background. Can be any valid html colour name or value.</p>
                <p>
                    <label for="subscriber_email_primary_colour">Primary colour</label>
                    <input type="text" name="subscriber_email_primary_colour" value="<?=$subscriber_email_primary_colour?>"/>
                </p>

                <p>Choose a font colour for full post excerpts. Can be any valid html colour name or value.</p>
                <p>
                    <label for="subscriber_email_excerpt_font_colour">Excerpt font colour</label>
                    <input type="text" name="subscriber_email_excerpt_font_colour" value="<?=$subscriber_email_excerpt_font_colour?>"/>
                </p>

                <p>Choose a font colour for post excerpts</p>
                @todo Something

                <!-- ADDITIONAL DATA COLLECTION SETTINGS -->
                <h2>Additional data collection settings</h2>
                <p>Choose whether to (try to) collect the subscriber location</p>
                <p>
                <label for="subscriber_location">Subscriber location</label>
                    <input type="radio" name="subscriber_location" 
                           value="1" <?=$subscriber_location ? 'checked' : ''?>/> Yes
                    <input type="radio" name="subscriber_location" 
                           value="0" <?=$subscriber_location==false ? 'checked' : ''?>/> No
                </p>

                <!-- ADMIN SETTINGS -->
                <h2>Notify Admin Settings</h2>
                <p>Choose which email to notify when a new contact subscribes or an existing one unsubscribes. By default this will be the admin email if one is not set here.</p>
                <p>
                    <label for="notify_on_subscribe_email">Notification for new subscriber email</label>
                    <input type="text" name="notify_on_subscribe_email" value="<?=$notify_on_subscribe_email?>"/>
                </p>

                <p>
                    <input type="submit" value="Save Changes"/>
                </p>
            </form>


        </div>
    <?php }
}