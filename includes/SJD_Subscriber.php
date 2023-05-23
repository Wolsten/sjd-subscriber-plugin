<?php

/* 

IMPORT SUBSCRIBERS

Install the https://wordpress.org/plugins/really-simple-csv-importer/
and then choose Tools -> Import -> Import CSV

The CSV file must have the following headings on the first row:

post_title  Subscribers email address
subscriber_email Should be the same as post title
subscriber_first_name First name
subscriber_last_name Last name
post_type Must be set to "subscribers"
post_status "draft" or "publish"

Others could be included - refer to docs

EXPORT SUBSCRIBERS

@todo a simple exporter to produce a CSV file. In meantime can export using 
standard wordpress XML exporter from Tools -> Export

*/

declare(strict_types=1);

class SJD_Subscriber {

    public const POST_TYPE = 'subscribers'; // Custom post type
    public const POST_PREFIX = 'subscriber';  // Prefix for custom fields

    public const CUSTOM_FIELDS = array(
        array("name"=>"first_name", "title"=>"First name", "type"=>"text", "required"=>true),
        array("name"=>"last_name", "title"=>"Last name", "type"=>"text", "required"=>true),
        array("name"=>"email", "title"=>"Email", "type"=>"email", "required"=>true),
        array("name"=>"location", "title"=>"Location", "type"=>"text", "required"=>false),
        array("name"=>"validation_key", "title"=>"Validation key", "type"=>"text", "required"=>false),
    );

    public static function init(){
        register_post_type(self::POST_TYPE, array(
            'label' => ucfirst(self::POST_TYPE),
            'singular_label' => ucfirst(self::POST_PREFIX),
            'public' => true,
            'show_ui' => true, // UI in admin panel
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-share',
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => array("slug" => self::POST_PREFIX), // Permalinks format
            'supports' => array('title', 'editor')
        ));
        add_action('add_meta_boxes', 'SJD_Subscriber::add_meta_boxes', 10, 1 );
        add_action('save_post', 'SJD_Subscriber::save_meta_data' );
        add_filter('manage_'.self::POST_TYPE.'_posts_columns', 'SJD_Subscriber::admin_columns', 10, 1 );
        add_filter('manage_posts_custom_column',  'SJD_Subscriber::admin_column', 10, 2);
    }

    public static function add_meta_boxes($post_type){
        if ( $post_type==self::POST_TYPE ) {
            foreach( self::CUSTOM_FIELDS as $field ){
                add_meta_box(
                    $html_id=self::POST_PREFIX.'_'.$field['name'],
                    $title=$field['title'],
                    $display_callback=Array('SJD_Subscriber','display_meta_box'),
                    $screen=null, 
                    $context='normal', 
                    $priority='high',
                    $callback_args=array( $field )
                );
            }
        }
    }

    public static function display_meta_box( $post, $args){
        $field = $args['args'][0];
        $id = self::POST_PREFIX.'_'.$field['name'];
        $value = esc_attr(get_post_meta( $post->ID, $id, true ));
        echo "<label for='$id'>".$field['title']."</label>";
        echo "&nbsp;<input type='".$field['type']."' id='$id' name='$id' value='$value' size='50' />";
    }

    public static function save_meta_data( $post_id ) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ){
            return;
        }
        $post_type=get_post_type($post_id);
        if ( $post_type==self::POST_TYPE ) {
            foreach( self::CUSTOM_FIELDS as $field ){
                $id = self::POST_PREFIX.'_'.$field['name'];
                if ( array_key_exists( $id, $_POST ) ){
                    $data = self::sanitise_field($field['type'], $_POST[$id]);
                    update_post_meta( $post_id, $id, $data );
                }
            }
        }
    }

    public static function sanitise_field($name,$value){
        if ( $name == 'email' ){
            return sanitize_email( $value );
        }
        return sanitize_text_field( $value );
    }

    public static function validate_fields($inputs){
        $clean = array();
        $errors = array();
        $status = true;
        foreach( self::CUSTOM_FIELDS as $field ){
            if ( isset($inputs[$field['name']])){
                $clean[$field['name']] = self::sanitise_field($field,$inputs[$field['name']]);
                $errors[$field['name']] = '';
                if ( $field['required'] && $clean[$field['name']] == ''){
                    $errors[$field['name']] = "This value is required";
                    $status = false;
                }
            }
        }
        return array('clean'=>$clean, 'errors'=>$errors, 'status'=>$status);
    }
    
    public static function admin_columns($columns){
        unset($columns['date']);
        foreach( self::CUSTOM_FIELDS as $field ){
            if ( $field['name'] !== 'email'){
                $columns[self::POST_PREFIX.'_'.$field['name']] = $field['title'];
            }
        }
        $columns['date'] = 'Date';
        return $columns;
    }

    public static function admin_column($column_id, $post_id){
        echo get_post_meta( $post_id, $column_id, $single=true);
    }

    public static function get( $email ){
        $post = get_page_by_title($title=$email,$output='OBJECT',$post_type=self::POST_TYPE);
        if ( $post ){
            // Add meta data to the post object
            $meta = get_post_meta( $post->ID );
            if ( $meta ){
                foreach( $meta as $key=>$value ){
                    $name = str_replace(self::POST_PREFIX.'_', '', $key);
                    $post->$name = $value[0];
                }
                return $post;
            }
        }
        return false;
    }

    public static function create( $fields ){
        $new_subscriber = array(
            'post_title' => $fields['email'],
            'post_status' => 'draft',
            'post_type' => self::POST_TYPE,
        );
        $post_id = wp_insert_post($new_subscriber);
        // echo "<p>post id $post_id</p>";
        $success = true;
        $validation_key = '';
        if ( $post_id > 0 ){
            foreach( self::CUSTOM_FIELDS as $field ){
                if ( $field['name'] == 'validation_key' ){
                    $validation_key = self::random_string(32);
                    $value = $validation_key;
                } else {
                    $value = $fields[$field['name']];
                }
                $meta_id = update_post_meta($post_id, self::POST_PREFIX.'_'.$field['name'], $value, $unique=true);
                if ( $meta_id === false ){
                    $success = false;
                }
            }
        }
        if ( $success ){
            $new_subscriber['ID'] = $post_id;
            $new_subscriber['first_name'] = $fields['first_name'];
            $new_subscriber['last_name'] = $fields['last_name'];
            $new_subscriber['email'] = $fields['email'];
            $new_subscriber['location'] = $fields['location'];
            $new_subscriber['validation_key'] = $validation_key;
            return (object) $new_subscriber;
        }
        return false;
    }

    public static function validate( $post_id ){
        // Unset validation key
        $status = update_post_meta($post_id, self::POST_PREFIX.'_validation_key', $value='');
        // Update post status
        if ( $status ){
            // echo "<p>wp_update_post for post id $post_id:</p>";
            $status = wp_update_post( array(
                'ID'=>$post_id, 
                'post_status'=>'publish'
            ));
            if ( is_wp_error($status) ){
                return false;
            }
            return true;
        }
        return false;
    }

    public static function all(){
        $subscribers = get_posts(array(
            'numberposts' => -1,
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish'
        ));
        foreach( $subscribers as $subscriber ){
            $subscriber->first_name = get_post_meta( $subscriber->ID, self::POST_PREFIX.'_first_name', $single=true);
            $subscriber->last_name = get_post_meta( $subscriber->ID, self::POST_PREFIX.'_last_name', $single=true);
        }
        return $subscribers;
    }




    // https://hughlashbrooke.com/2012/04/23/simple-way-to-generate-a-random-password-in-php/
    private static function random_string( $length = 64) {
        // Need to be careful with choice of characters so that all are valid for urls
        // i.e. no ? or #
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@()_";
        return substr( str_shuffle( $chars ), 0, $length );
    }

    public static function delete_subscribers(){
        $subscribers = get_posts(array(
            'numberposts' => -1,
            'post_type' => self::POST_TYPE
        ));
        echo "<p>Deleted the following subscribers:</p><ol>";
        foreach( $subscribers as $subscriber ){
            if ( wp_delete_post($subscriber->ID) ){
                echo "<li>Deleted subscriber $subscriber->post_title</li>";
            }
        }
        echo "</ol>";
    }


    public static function import_subscribers($filename){
        $handle = fopen($filename,'r') or die('Unable to open file $filename');
        while (($buffer = fgets($handle, 4096)) !== false) {
            $parts = explode(',',$buffer);
            // Ignore first line of titles
            if ( $parts[0] !== 'email' ){
                $email = sanitize_email($parts[0]);
                $first_name = sanitize_text_field($parts[1]);
                $last_name = sanitize_text_field($parts[2]);
                $validation_key = '';
                if ( isset($parts[3]) ){
                    $validation_key = sanitize_text_field($parts[3]);
                }
                $newSubscriber = array(
                    'post_title' => $email,
                    'post_status' => 'publish',
                    'post_type' => self::POST_TYPE,
                );
                $post_id = wp_insert_post($newSubscriber);
                if ( $post_id ){
                    $meta_id = update_post_meta($post_id, self::POST_PREFIX.'_email', $email, $unique=true);
                    $meta_id = update_post_meta($post_id, self::POST_PREFIX.'_first_name', $first_name, $unique=true);
                    $meta_id = update_post_meta($post_id, self::POST_PREFIX.'_last_name', $last_name, $unique=true);
                    $meta_id = update_post_meta($post_id, self::POST_PREFIX.'_location', $location, $unique=true);
                    $meta_id = update_post_meta($post_id, self::POST_PREFIX.'_validation_key', $validation_key, $unique=true);
                    echo "<p>Added subscriber $first_name $last_name ($email)</p>";
                }
            }
        }
        fclose($handle);
    }

    public static function export_subscribers(){
        $subscribers = get_posts(array(
            'numberposts' => -1,
            'post_type' => self::POST_TYPE
        ));
        if ( $subscribers ){

            $handle = fopen("downloaded_subscribers.csv", "w") or die("Unable to open file for writing!");

            $title = "email,first_name,last_name,validation_key\n";
            fwrite($handle,$title);

            foreach( $subscribers as $subscriber){
                $email = get_post_meta( $subscriber->ID, self::POST_PREFIX.'_email', $single=true);
                $first_name = get_post_meta( $subscriber->ID, self::POST_PREFIX.'_first_name', $single=true);
                $last_name = get_post_meta( $subscriber->ID, self::POST_PREFIX.'_last_name', $single=true);
                $location = get_post_meta( $subscriber->ID, self::POST_PREFIX.'_location', $single=true);
                $validation_key = get_post_meta( $subscriber->ID, self::POST_PREFIX.'_validation_key', $single=true);

                $line = "$email,$first_name,$last_name,$locations,$validation_key\n";
                fwrite($handle,$line);
            }

            $domain = get_bloginfo('url');
            echo "<p>Exported subscribers - click the following link below to download:</p>";
            echo "<a href='$domain/downloaded_subscribers.csv' download>Download file</a>";

            fclose($handle);
        }

    }

}