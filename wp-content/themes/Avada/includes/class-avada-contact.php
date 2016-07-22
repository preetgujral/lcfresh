<?php

// class Avada_Contact {

// 	public function __construct() {

// 		// No need to proceed any further if we're not on the contact page
// 		if ( ! is_page_template( 'contact.php' ) ) {
// 			return;
// 		}

// 		// Send the email from the contact form.
// 		$this->send_email();

// 	}

// 	/**
// 	 * Gets the recaptcha object
// 	 */
// 	public function get_recaptcha() {

// 		$re_captcha = '';

// 		// Setup reCaptcha
// 		if ( Avada()->settings->get( 'recaptcha_public' ) && Avada()->settings->get( 'recaptcha_private' ) && ! function_exists( 'recaptcha_get_html' ) ) {

// 			require_once( trailingslashit( get_template_directory() ) . 'framework/recaptchalib.php' );
// 			// Instantiate ReCaptcha object
// 			$re_captcha = new ReCaptcha( Avada()->settings->get( 'recaptcha_private' ) );

// 		}

// 		return $re_captcha;

// 	}

// 	/**
// 	 * detect if there are errors in the submitted form.
// 	 * @return  boolean
// 	 */
// 	public function has_error() {

// 		// No need to proceed any further if the form has not been submitted
// 		if ( ! isset( $_POST['submit'] ) ) {
// 			return false;
// 		}

// 		/**
// 		 * Check to make sure that the name field is not empty.
// 		 * If empty then return true, no need to proceed any further.
// 		 */
// 		if ( '' == trim( $_POST['contact_name'] ) || 'Name (required)' == trim( $_POST['contact_name'] ) ) {
// 			return true;
// 		}

// 		/**
// 		 * Validate the email address.
// 		 * If empty then return true. No need to procees any further.
// 		 */
// 		if ( '' == trim( $_POST['email'] ) || 'Email (required)' == trim( $_POST['email'] ) ) {
// 			return true;
// 		} else if ( filter_var( $_POST['email'], FILTER_SANITIZE_EMAIL ) ) {
// 		} else {
// 			return true;
// 		}

// 		/**
// 		 * Check to make sure comments were entered.
// 		 * If none were entered then return true. No need to proceed any further.
// 		 */
// 		if ( '' == trim( $_POST['msg'] ) || 'Message' == trim( $_POST['msg'] ) ) {
// 			return true;
// 		}

// 		// Check if recaptcha is used
// 		if ( '' != $this->get_recaptcha() && $this->get_recaptcha() ) {

// 			$re_captcha_response = null;

// 			// Was there a reCAPTCHA response?
// 			if ( $_POST["g-recaptcha-response"] ) {
// 				$re_captcha_response = $re_captcha->verifyResponse(
// 					$_SERVER["REMOTE_ADDR"],
// 					$_POST["g-recaptcha-response"]
// 				);
// 			}

// 			*
// 			 * Check the reCaptcha response.
// 			 * If there was an error then return true. No need to proceed any further.

// 			if ( null == $re_captcha_response || ! $re_captcha_response->success ) {
// 				return true;
// 			}

// 		}

// 		/**
// 		 * If all of the above tests have passed, there was no error.
// 		 * return false.
// 		 */
// 		 return false;

// 	}

// 	/**
// 	 * Process the email form.
// 	 * Takes care of actually sending the emails
// 	 */
// 	function send_email() {

// 		// No need to proceed any further if the form has not been submitted
// 		if ( ! isset( $_POST['submit'] ) ) {
// 			return false;
// 		}

// 		// No need to proceed any further if there was an error in the form.
// 		if ( $this->has_error() ) {
// 			return false;
// 		}

// 		// The contact name
// 		$name = trim( $_POST['contact_name'] );
// 		// Subject field is not required
// 		$subject = ( function_exists( 'stripslashes' ) ) ? stripslashes( trim( $_POST['url'] ) ) : trim( $_POST['url'] );
// 		// Validates and sanitizes the email address
// 		$email = filter_var( $_POST['email'], FILTER_SANITIZE_EMAIL );
// 		// The comment
// 		$comments = ( function_exists( 'stripslashes' ) ) ? stripslashes( trim( $_POST['msg'] ) ) : trim( $_POST['msg'] );

// 		// Some more sanitization, you can never be too safe.
// 		$name     = wp_filter_kses( $name );
// 		$email    = wp_filter_kses( $email );
// 		$subject  = wp_filter_kses( $subject );
// 		$comments = wp_filter_kses( $comments );

// 		if ( function_exists( 'stripslashes' ) ) {
// 			$subject  = stripslashes( $subject );
// 			$comments = stripslashes( $comments );
// 		}

// 		$emailTo = Avada()->settings->get( 'email_address' ); //Put your own email address here

// 		$body    = __( 'Name:',     'Avada') . " $name \n\n";
// 		$body   .= __( 'Email:',    'Avada') . " $email \n\n";
// 		$body   .= __( 'Subject:',  'Avada') . " $subject \n\n";
// 		$body   .= __( 'Comments:', 'Avada') . "\n $comments";

// 		$headers = 'Reply-To: ' . $name . ' <' . $email . '>' . "\r\n";

// 		// Send the email
// 		$mail = wp_mail( $emailTo, $subject, $body, $headers );

// 		$emailSent = true;

// 		if ( isset( $emailSent ) && $emailSent ) {
// 			$_POST['contact_name'] = '';
// 			$_POST['email']        = '';
// 			$_POST['url']          = '';
// 			$_POST['msg']          = '';

// 			return true;
// 		} else {
// 			return false;
// 		}

// 	}

// }

// // Omit closing PHP tag to avoid "Headers already sent" issues.
