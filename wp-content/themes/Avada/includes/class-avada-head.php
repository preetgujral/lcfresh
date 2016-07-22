<?php

class Avada_Head {

	public function __construct() {
		// add_action( 'wp_head', array( $this, 'x_ua_meta' ), 1 );
		// add_action( 'wp_head', array( $this, 'the_meta' ) );
		// add_action( 'wp_head', array( $this, 'insert_og_meta' ), 5 );
		// add_filter( 'language_attributes', array( $this, 'add_opengraph_doctype' ) );
		
		add_filter( 'document_title_separator', array( $this, 'document_title_separator' ) );
	}
	
	/**
	 * Adding the Open Graph in the Language Attributes
	 */
	public function add_opengraph_doctype( $output ) {
		return $output . ' prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb#"';
	}

	/**
	 * Avada extra OpenGraph tags
	 * These are added to the <head> of the page using the 'wp_head' action.
	 */
	public function insert_og_meta() {

		global $post;

		$settings = Avada::settings();

		// Early exit if we don't need to continue any further
		if ( $settings['status_opengraph'] ) {
			return;
		}

		// Early exit if this is not a singular post/page/cpt
		if ( ! is_singular() ) {
			return;
		}

		$image = '';
		if ( ! has_post_thumbnail( $post->ID ) ) {
			if ( $settings['logo'] ) {
				$image = $settings['logo'];
			}
		} else {
			$thumbnail_src = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'full' );
			$image = esc_attr( $thumbnail_src[0] );
		}

		?>

		<meta property="og:title" content="<?php echo strip_tags( str_replace( array( '"', "'" ), array( '&quot;', '&#39;' ), $post->post_title ) ); ?>"/>
		<meta property="og:type" content="article"/>
		<meta property="og:url" content="<?php echo get_permalink(); ?>"/>
		<meta property="og:site_name" content="<?php echo get_bloginfo( 'name' ); ?>"/>
		<meta property="og:description" content="<?php echo Avada()->blog->get_content_stripped_and_excerpted( 55, $post->post_content ); ?>"/>

		<?php if ( '' != $image ) : ?>
			<meta property="og:image" content="<?php echo $image; ?>"/>
		<?php endif;

	}

	/**
	 * Add X-UA-Compatible meta when needed.
	 */
	public function x_ua_meta() {

		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && ( false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE' ) ) ) : ?>
			<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<?php endif;

	}

	/**
	 * Set the document title separator
	 */
	public function document_title_separator() {
		return '-';
	}
}

// Omit closing PHP tag to avoid "Headers already sent" issues.
