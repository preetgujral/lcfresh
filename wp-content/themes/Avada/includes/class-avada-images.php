<?php

class Avada_Images {

    public function __construct() {
    	global $smof_data;

        if ( ! $smof_data['status_lightbox'] ) {
        	add_filter( 'wp_get_attachment_link', array( $this, 'prepare_lightbox_links' ) );
        }

        add_filter( 'jpeg_quality', array( $this, 'set_jpeg_quality' ) );
        add_filter( 'wp_editor_set_quality', array( $this, 'set_jpeg_quality' ) );
        add_filter( 'max_srcset_image_width', array( $this, 'set_max_srcset_image_width' ) );
        add_filter( 'wp_calculate_image_srcset', array( $this, 'set_largest_image_size' ), '10', '5' );
    }

    /**
     * Adds lightbox attributes to links
     */
    public function prepare_lightbox_links( $content ) {

		preg_match_all('/<a[^>]+href=([\'"])(.+?)\1[^>]*>/i', $content, $matches );
        $attachment_id = self::get_attachment_id_from_url( $matches[2][0] );
        $title = get_post_field( 'post_title', $attachment_id );
        $caption = get_post_field('post_excerpt', $attachment_id );

        $content = preg_replace( "/<a/", '<a data-rel="iLightbox[postimages]" data-title="' . $title . '" data-caption="' . $caption . '"' , $content, 1 );

        return $content;
    }

    /**
     * Modify the image quality and set it to chosen Theme Options value.
     * @since 3.9
     *
     * @param string $quality Image quality.
     *
     * @return string The new image quality.
     */
    public function set_jpeg_quality( $quality ) {
    	return Avada()->settings->get( 'pw_jpeg_quality' );
    }
    
    /**
     * Modify the maximum image width to be included in srcset attribute.
     * @since 3.9
     *
     * @param int   $max_width  The maximum image width to be included in the 'srcset'. Default '1600'.
     *
     * @return int 	The new max width.
     */
    public function set_max_srcset_image_width( $max_width ) {
    	return 1920;
    }    
    
    /**
     * Add the fullsize image to the scrset attribute.
     * @since 3.9
     *
	 * @param array  $sources {
	 *     One or more arrays of source data to include in the 'srcset'.
	 *
	 *     @type array $width {
	 *         @type string $url        The URL of an image source.
	 *         @type string $descriptor The descriptor type used in the image candidate string,
	 *                                  either 'w' or 'x'.
	 *         @type int    $value      The source width if paired with a 'w' descriptor, or a
	 *                                  pixel density value if paired with an 'x' descriptor.
	 *     }
	 * }     
	 * @param array  $size_array    Array of width and height values in pixels (in that order).
	 * @param string $image_src     The 'src' of the image.
	 * @param array  $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
 	 * @param int    $attachment_id Image attachment ID or 0.
     *
	 * @return array $sources 		One or more arrays of source data to include in the 'srcset'.
     */
    public function set_largest_image_size( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		$cropped_image = false;

		foreach( $sources as $source => $details ) {
			if ( $details['url'] == $image_src ) {
				$cropped_image = true;
			}
		}

		if ( ! $cropped_image ) {
			$full_image_src = wp_get_attachment_image_src( $attachment_id, 'full' );

			$full_size = array( 
				'url' => $full_image_src[0],
				'descriptor' => 'w',
				'value' => $image_meta['width']
			);


			$sources[$image_meta['width']] = $full_size;
		}
    	
    	return $sources;
    }     
    
    /**
     * Gets the attachment ID from the url
     *
     * @param string $attachment_url The url of the attachment
     *
     * @return string The attachment ID
     */
	public static function get_attachment_id_from_url( $attachment_url = '' ) {
		global $wpdb;
		$attachment_id = false;

		if ( $attachment_url == '' ) {
			return;
		}

		$upload_dir_paths = wp_upload_dir();

		// Make sure the upload path base directory exists in the attachment URL, to verify that we're working with a media library image
		if ( false !== strpos( $attachment_url, $upload_dir_paths['baseurl'] ) ) {

			// If this is the URL of an auto-generated thumbnail, get the URL of the original image
			$attachment_url = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $attachment_url );

			// Remove the upload path base directory from the attachment URL
			$attachment_url = str_replace( $upload_dir_paths['baseurl'] . '/', '', $attachment_url );

			// Run a custom database query to get the attachment ID from the modified attachment URL
			$attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value = '%s' AND wposts.post_type = 'attachment'", $attachment_url ) );
		}

		return $attachment_id;
	}

	// WIP ITEM FOR #746
	function get_image_size_width( $site_width = false, $sidebar_1_width = false, $sidebar_2_width = false, $gutter_width = false, $columns = 1 ) {

		if ( $site_width ) {
            // Site width is using %
			if ( false !== strpos( $site_width, '%' ) ) {
				$site_width = self::percent_to_pixels( $site_width );
			}
            // Site width is using ems
            elseif ( false !== strpos( $site_width, 'em' ) ) {
                $site_width = self::ems_to_pixels( $site_width );
			}
		}

		if ( $sidebar_1_width ) {
			if ( false !== strpos( $sidebar_1_width, '%' ) ) {
				$sidebar_1_width = self::percent_to_pixels( $sidebar_1_width );
			} elseif ( false !== strpos( $sidebar_1_width, 'em' ) ) {
				$sidebar_1_width = self::ems_to_pixels( $sidebar_1_width );
			}
		}

		if ( $sidebar_2_width ) {
			if ( false !== strpos( $sidebar_2_width, '%' ) ) {
                $sidebar_2_width = self::percent_to_pixels( $sidebar_2_width );
			} elseif ( false !== strpos( $sidebar_2_width, 'em' ) ) {
                $sidebar_2_width = self::ems_to_pixels( $sidebar_2_width );
			}
		}

        if ( false === $gutter_width ) {
            // assume a gutter of 30px
            $gutter_width = 30;
        } else {
            $gutter_width = intval( $gutter_width );
        }

		if ( $site_width && $sidebar_1_width && $sidebar_2_width ) {
			$gutter = 2 * $gutter_width;
		} elseif ( $site_width && $sidebar_1_width ) {
			$gutter = $gutter_width;
		}
		$extra_gutter = ( $columns - 1 ) * $gutter_width;

		$sidebar_1_width = ( $sidebar_1_width ) ? $sidebar_1_width : 0;
		$sidebar_2_width = ( $sidebar_2_width ) ? $sidebar_2_width : 0;

		$content_width = $site_width - $sidebar_1_width - $sidebar_2_width;
		$image_width = ( $content_width / $columns ) - $extra_gutter;

		return $image_width;

	}

    public static function percent_to_pixels( $percent, $max_width = 2000 ) {
        return intval( ( intval( $percent ) * $max_width ) / 100 );
    }

    public static function ems_to_pixels( $ems, $font_size = 14 ) {
        return intval( filter_var( $ems, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ) * $font_size );
    }

}

// Omit closing PHP tag to avoid "Headers already sent" issues.
