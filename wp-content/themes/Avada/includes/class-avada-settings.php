<?php

class Avada_Settings {

    public static $options_with_id = array();

    /**
     * Get all settings
     */
    public function get_all() {

        global $smof_data;
        return $smof_data;

    }

    /**
     * Gets the value of a single setting
     */
    public function get( $setting = null, $subset = false ) {

        if ( is_null( $setting ) ) {
            return null;
        }

        $settings = $this->get_all();
        
        if ( isset( $settings[$setting] ) ) {
            return ( ! $subset ) ? $settings[$setting] : $settings[$setting][$subset];
        } else {
            return null;
        }

    }

    /**
     * Gets the default value of a single setting
     */
    public function get_default( $setting = null, $subset = false ) {

        if( ! self::$options_with_id ) {
            $options = of_options_array();

            foreach( $options as $key => $option ) {
                self::$options_with_id[ $option['id'] ] = $option;
            }
        }

        if ( is_null( $setting ) ) {
            return null;
        }

        if ( isset( self::$options_with_id[ $setting ] ) ) {
            return ( ! $subset ) ? self::$options_with_id[ $setting ]['std'] : self::$options_with_id[ $setting ]['std'][ $subset ];
        } else {
            return null;
        }

    }

}

// Omit closing PHP tag to avoid "Headers already sent" issues.
