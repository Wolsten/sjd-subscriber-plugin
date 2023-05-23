<?php


    private static function admin_functions() { 

        $submit = false;
        if ( isset($_POST['SUBMIT']) ) {

            $submit=$_POST['SUBMIT'];

            // Send notifications
            if ( $submit == 'LINK' || $submit == 'PAGE' ){
                
                $post_id = intval($_POST['post_id']);
                // echo "<p>SHORTCODE: Sending email for post id = $post_id, send $submit</p>";
                Notifications::send($post_id, $submit);

            // Check whether to display import subscribers form
            } else if ( $submit == 'IMPORT_SUBSCRIBERS' ){
                $submit = false;
                self::file_form($submit);
                return;

            // Upload subscribers
            } else if ( $submit == 'CONFIRM_IMPORT_SUBSCRIBERS' ){
                self::file_form($submit);

            } else if ( $submit == 'DELETE_SUBSCRIBERS' ){
                $submit = false;
                self::delete_form();
                return;

            } else if ( $submit == 'CONFIRM_DELETE_SUBSCRIBERS' ){
                Subscriber::delete_subscribers($submit);

            } else if ( $submit == 'EXPORT_SUBSCRIBERS' ){
                Subscriber::export_subscribers();
            }

        } else if ( isset($_REQUEST['post_id']) ){

            $post_id = intval($_REQUEST['post_id']);
            self::notifications_form($submit, $post_id);

        } 
        
        self::admin_form();
    }



    private static function file_form($submit){ 
        $file = '';
        $file_error = '';
        if ( $submit ){
            if ( isset($_FILES['csv']) == false ){
                $file_error = "No file selected";
            } 
            if ( $file_error == '' ){
                $file_name = $_FILES['csv']['name'];
                $parts = explode('.',$file_name);
                $extension = $parts[count($parts)-1];
                if ( $extension !== 'csv'){
                    $file_error = "You must choose a csv file";
                }
            }
            if ( $file_error == '' ){
                $file = $_FILES['csv']['tmp_name'];
                Subscriber::import_subscribers($file);
            }
        }
        if ( $submit==false || $file_error != '' ){ ?>
            <p>Choose file and then press Upload.</p>
            <form id="import" method="post" enctype="multipart/form-data">
                <label for="csv" > 
                    <span>CSV file</span>
                    <input type="file" name="csv" value="<?=$file?>" class="<?=$file_error?'error':'';?>"/>
                    <?php if ( $file_error ) { ?>
                    <div class="error"><?= $file_error ?></div>
                <?php } ?>
                </label>
                <label for="submit"> 
                    <button type="submit" name="SUBMIT" id="SUBMIT" value="CONFIRM_IMPORT_SUBSCRIBERS">Upload</button>
                </label>           
                <?php wp_nonce_field('sjd_subscribe_submit','_sjd_subscribe_nonce'); ?>      
            </form>
        <?php }
    }


    public static function is_super_user(){
        if ( is_user_logged_in() ){
            $user = wp_get_current_user();
            if ( in_array( 'editor', (array) $user->roles ) || in_array( 'administrator', (array) $user->roles )) {
                return true;
            }
        }
        return false;
    }

    private static function admin_form(){ ?>
        <h2>Admin options</h2>
        <form id="admin" method="post">
            <button type="submit" name="SUBMIT" value="IMPORT_SUBSCRIBERS">Import Subscribers</button>
            <button type="submit" name="SUBMIT" value="DELETE_SUBSCRIBERS">Delete Subscribers</button>
            <button type="submit" name="SUBMIT" value="EXPORT_SUBSCRIBERS">Export Subscribers</button>
            <?php wp_nonce_field('sjd_subscribe_submit','_sjd_subscribe_nonce'); ?> 
        </form>
    <?php }

    private static function delete_form(){ ?>
        <h2>Delete subscribers</h2>
        <p>Click the button below to delete all subscribers. WARNING: This operation cannot be reversed other than by re-importing.</p>
        <form id="admin" method="post">
            <button type="submit" name="SUBMIT" id="SUBMIT" value="CONFIRM_DELETE_SUBSCRIBERS">Delete Subscribers</button>
            <?php wp_nonce_field('sjd_subscribe_submit','_sjd_subscribe_nonce'); ?> 
        </form>
    <?php }

public static function request_confirmation(){
    $url = get_option('subscriber_url');
    echo "<p>You are about to send import subscribers. Click the button below to confirm.</p>";
    echo "<button><a href='$url?import&confirm'>Import</a></button>";
}