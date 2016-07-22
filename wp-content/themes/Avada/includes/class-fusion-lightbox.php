<?php

class Fusion_Lightbox {

    public function __construct() {
        add_action( 'wp_ajax_fusion_parse_lightbox_url', array( $this, 'parse_url' ) );
        add_action( 'wp_ajax_nopriv_fusion_parse_ligtbox_url', array( $this, 'parse_url' ) );
    }

    public function parse_url( $url ) {
        $url = @rawurldecode( $url );
        $result = array();
        $url_info = parse_url( $url );

        if( $url ){
            if( $this->validate_url( $url ) ) {
                if( false === ( $lightbox_data = get_transient( 'fusion_lightbox_' . md5( $url ) ) ) ) {
                    $str = wp_remote_get("http://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20html%20where%20url%3D%22".rawurlencode($url)."%22%20and%0A%20%20%20%20%20%20xpath%3D'%2F%2Fhtml%2Fhead'%0A%20and%20compat%3D%22html5%22&format=xml&diagnostics=true&callback=");
                    $str = wp_remote_retrieve_body( $response );
                }
            }
        }
    }

    function validate_url($url) {
        $res = filter_var ($url, FILTER_VALIDATE_URL);
        if ($res) return filter_var ($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
        // Check if it has unicode chars.
        $l = mb_strlen ($url);
        if ($l !== strlen ($url)) {
            // Replace wide chars by “X”.
            $s = str_repeat (' ', $l);
            for ($i = 0; $i < $l; ++$i) {
                $ch = mb_substr ($url, $i, 1);
                $s [$i] = strlen ($ch) > 1 ? 'X' : $ch;
            }
            // Re-check now.
            $res = filter_var ($s, FILTER_VALIDATE_URL);
            if ($res) { return filter_var ($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED); }
        }
    }
}

//new Fusion_Lightbox();


// Omit closing PHP tag to avoid "Headers already sent" issues.
