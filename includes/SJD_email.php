<?php 

/**
 * This function will connect wp_mail to your authenticated
 * SMTP server. This improves reliability of wp_mail, and 
 * avoids many potential problems.
 *
 * For instructions on the use of this script, see:
 * https://butlerblog.com/easy-smtp-email-wordpress-wp_mail/
 * 
 * !!! IMPORTANT !!!
 * Values for constants should be set in wp-config.php
 */


if ( defined('SMTP_USER') == false || defined('SMTP_PASS') == false || defined('SMTP_FROM') == false){
	die("<p>sjd_subscribe plugin not configured. Set SMTP constants in wp-config.php</p>");
}

add_filter( 'wp_mail_from', 'custom_wp_mail_from' );
function custom_wp_mail_from( $original_email_address ) {
	return SMTP_FROM;
}

add_filter( 'wp_mail_from_name', 'custom_wp_mail_from_name' );
function custom_wp_mail_from_name( $original_email_from ) {
	return SMTP_NAME;
}


add_action( 'phpmailer_init', 'send_smtp_email' );
function send_smtp_email( $phpmailer ) {
	$phpmailer->isSMTP();
	$phpmailer->Host       = SMTP_HOST;
	$phpmailer->SMTPAuth   = SMTP_AUTH;
	$phpmailer->Port       = SMTP_PORT;
	$phpmailer->Username   = SMTP_USER;
	$phpmailer->Password   = SMTP_PASS;
	$phpmailer->SMTPSecure = SMTP_SECURE;
	$phpmailer->From       = SMTP_FROM;
	$phpmailer->Sender     = $phpmailer->From;
	$phpmailer->FromName   = SMTP_NAME;
	if( 'text/plain' !== $phpmailer->ContentType && empty( $phpmailer->AltBody ) ) {
		$phpmailer->AltBody = wp_strip_all_tags( $phpmailer->Body );
	}
}

?>
