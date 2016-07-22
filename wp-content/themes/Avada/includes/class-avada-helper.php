<?php

class Avada_Helper {

    /**
     * Return the value of an echo.
     * example: Avada_Helper::get_echo( 'function' );
     */
    public static function get_echo( $function, $args = '' ) {

    	// Early exit if function does not exist
    	if ( ! function_exists( $function ) ) {
    		return;
    	}

    	ob_start();
    	$function( $args );
    	$get_echo = ob_get_clean();
    	return $get_echo;

    }

   	public static function slider_name( $name ) {

		$type = '';

		switch( $name ) {
			case 'layer':
				$type = 'slider';
				break;
			case 'flex':
				$type = 'wooslider';
				break;
			case 'rev':
				$type = 'revslider';
				break;
			case 'elastic':
				$type = 'elasticslider';
				break;
		}

		return $type;

	}

	public static function get_slider_type( $post_id ) {
		return get_post_meta( $post_id, 'pyre_slider_type', true );
	}

}

// Omit closing PHP tag to avoid "Headers already sent" issues.
