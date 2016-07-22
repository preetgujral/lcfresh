<?php
/**
 * Social Icons Class
 *
 * WARNING: This file is part of the Fusion Core Framework.
 * Do not edit the core files.
 * Add any modifications necessary under a child theme.
 *
 * @package  Fusion/Framework
 * @author   ThemeFusion
 * @link	 http://theme-fusion.com
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	die;
}

class Avada_Social_Icons {

	public $args = array(
		'icon_order' => '',
		'icon_colors' => '',
		'box_colors' => '',
	);

	/**
	 * Initiate the class
	 */
	public function __construct() {

		add_filter( 'fusion_attr_social-icons-class-social-networks', array( $this, 'social_networks_attr' ) );
		add_filter( 'fusion_attr_social-icons-class-icon', array( $this, 'icon_attr' ) );

	}

	/**
	 * Renders all soical icons not belonging to shortcodes
	 *
	 * @since 3.5.0
	 * @param  array   $args Holding all necessarry data for social icons
	 * @return string  The HTML mark up for social icons, incl. wrapping container
	 */
	public function render_social_icons( $args ) {

		$this->args = $args;

		if( isset( $this->args['sharingbox'] ) && $this->args['sharingbox'] == 'yes' ) {
			$social_networks = $this->get_sharingbox_social_links_array( $this->args );
		} elseif( isset( $this->args['authorpage'] ) && $this->args['authorpage'] == 'yes' ) {
			$social_networks = $this->get_authorpage_social_links_array( $this->args );
		} else {
			$social_networks = $this->get_social_links_array();
		}

		if ( Avada()->settings->get( 'social_sorter' ) ) {
			$order = Avada()->settings->get( 'social_sorter' );
			$ordered_array = explode(',', $order);

			if( isset( $ordered_array ) && $ordered_array && is_array( $ordered_array ) ) {
				$social_networks_old = $social_networks;
				$social_networks = array();
				foreach( $ordered_array as $key => $field_order ) {
					$field_order_number = str_replace(  'social_sorter_', '', $field_order );
					$find_the_field = Avada()->settings->get( 'social_sorter_' . $field_order_number );
					$field_name = str_replace( '_link', '', Avada()->settings->get( 'social_sorter_' . $field_order_number ) );

					if( $field_name == 'google' ) {
						$field_name = 'googleplus';
					}

					if( ! isset( $social_networks_old[$field_name] ) ) {
						continue;
					}				

					$social_networks[$field_name] = $social_networks_old[$field_name];
				}
			}
		}

		if( isset( $social_networks_old['custom'] ) && $social_networks_old['custom'] ) {
			$social_networks['custom'] = $social_networks_old['custom'];
		}

		$icon_colors = explode( '|', $this->args['icon_colors'] );
		$num_of_icon_colors = count( $icon_colors );

		$box_colors = explode( '|', $this->args['box_colors'] );
		$num_of_box_colors = count( $box_colors );

		$html = $icons = '';

		for( $i = 0; $i < count( $social_networks ); $i++ ) {
			if( $num_of_icon_colors == 1 ) {
				$icon_colors[$i] = $icon_colors[0];
			}

			if( $num_of_box_colors == 1 ) {
				$box_colors[$i] = $box_colors[0];
			}
		}

		$i = 0;
		$number_of_social_networks = count( $social_networks );
		foreach ( $social_networks as $network => $link ) {
			$custom = '';
			if( $network == 'custom' ) {
				$custom = sprintf( '<img src="%s" alt="%s" />', Avada()->settings->get( 'custom_icon_image' ), Avada()->settings->get( 'custom_icon_name' ) );

				$network = 'custom_' . Avada()->settings->get( 'custom_icon_name' );

			}

			$icon_options = array(
				'social_network' 	=> $network,
				'social_link' 		=> $link,
			);

			if( isset( $icon_colors[$i] ) && $icon_colors[$i] ) {
				$icon_options['icon_color'] = $icon_colors[$i];
			} else {
				$icon_options['icon_color'] = '';
			}

			if( isset( $box_colors[$i] ) && $box_colors[$i] ) {
				$icon_options['box_color'] = $box_colors[$i];
			} else {
				$icon_options['box_color'] = '';
			}

			// Chck if are on the last social icon; $i needs to be incremented first to make it match the count() value
			$i++;

			$icon_options['last'] = FALSE;
			if ( $i == $number_of_social_networks ) {
				$icon_options['last'] = TRUE;
			}

			$icons .= sprintf( '<a %s>%s</a>', fusion_attr( 'social-icons-class-icon', $icon_options ), $custom );

		}

		if( $icons ) {
			if( isset( $this->args['position'] ) && ( $this->args['position'] == 'header' ||
				$this->args['position'] == 'footer' )
			) {
				$html = sprintf( '<div %s><div %s>%s</div></div>', fusion_attr( 'social-icons-class-social-networks' ), fusion_attr( 'fusion-social-networks-wrapper' ), $icons );
			} else {
				$html = sprintf( '<div %s><div %s>%s<div class="fusion-clearfix"></div></div></div>', fusion_attr( 'social-icons-class-social-networks' ), fusion_attr( 'fusion-social-networks-wrapper' ), $icons );
			}
		}

		return $html;
	}

	function social_networks_attr() {

		$attr['class'] = 'fusion-social-networks';

		if( $this->args['icon_boxed'] == 'Yes' ) {
			$attr['class'] .= ' boxed-icons';
		}

		return $attr;

	}

	function icon_attr( $args ) {

		$attr = array();
		$attr['class'] = '';
		$attr['style'] = '';

		if( substr( $args['social_network'], 0, 7 ) === 'custom_' ) {
			$attr['class'] .= 'custom ';
			$tooltip = str_replace( 'custom_', '', $args['social_network'] );
			$args['social_network'] = strtolower( $tooltip );
		} else {
			$tooltip = ucfirst( $args['social_network'] );
		}
		
		if ( $args['social_network'] == 'email' ) {
			$args['social_network'] = 'mail';
		}

		$attr['class'] .= sprintf( 'fusion-social-network-icon fusion-tooltip fusion-%s fusion-icon-%s', $args['social_network'], $args['social_network'] );

		if ( $args['last'] ) {
			$attr['class'] .= ' fusion-last-social-icon';
		}

		$link = $args['social_link'];
		
		if( $args['social_network'] == 'googleplus'  && strpos($args['social_link'],'share?') !== false ) {
			$attr['onclick'] = 'javascript:window.open(this.href,\'\', \'menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=600,width=600\');return false;';
		}
		
		if( $this->args['linktarget'] ) {
			$attr['target'] = '_blank';
		}

		if( $args['social_network'] == 'mail' ) {
			if ( substr(  $args['social_link'], 0, 4 ) === "http" ) {
				$link = $args['social_link'];
			} else {
				$link = 'mailto:' . str_replace( 'mailto:', '', $args['social_link'] );
			}		
			$attr['target'] = '_self';
		}

		$attr['href'] = $link;

		if( Avada()->settings->get( 'nofollow_social_links' ) ) {
			$attr['rel'] = 'nofollow';
		}

		if( $args['icon_color'] ) {
			$attr['style'] = sprintf( 'color:%s;', $args['icon_color'] );
		}

		if( strtolower( $this->args['icon_boxed'] ) == 'yes' &&
			$args['box_color']
		) {
				$attr['style'] .= sprintf( 'background-color:%s;border-color:%s;', $args['box_color'], $args['box_color'] );
		}

		if( strtolower( $this->args['icon_boxed'] ) == 'yes' &&
			$this->args['icon_boxed_radius'] || $this->args['icon_boxed_radius'] === '0'
		) {
			if( $this->args['icon_boxed_radius'] == 'round' ) {
				$this->args['icon_boxed_radius'] = '50%';
			}

			$attr['style'] .= sprintf( 'border-radius:%s;', $this->args['icon_boxed_radius'] );
		}

		if( strtolower( $this->args['tooltip_placement'] ) != 'none' ) {
			$attr['data-placement'] = strtolower( $this->args['tooltip_placement'] );
			if( $tooltip == 'Googleplus' ) {
				$tooltip = 'Google+';
			}
			$attr['data-title'] = $tooltip;
			$attr['data-toggle'] = 'tooltip';
		}

		$attr['title'] = $tooltip;

		return $attr;

	}

	/**
	 * Sets up the array for social links that don't belong to sharing box.
	 *
	 * @since 3.5.0
	 * @return array  The social links array containing the social media and links to them.
	 */
	function get_social_links_array() {

		$social_links_array = array();

		$social_links = array(
			'facebook_link'   => 'facebook',
			'twitter_link'    => 'twitter',
			'linkedin_link'   => 'linkedin',
			'dribbble_link'   => 'dribbble',
			'rss_link'        => 'rss',
			'youtube_link'    => 'youtube',
			'instagram_link'  => 'instagram',
			'pinterest_link'  => 'pinterest',
			'flickr_link'     => 'flickr',
			'vimeo_link'      => 'vimeo',
			'tumblr_link'     => 'tumblr',
			'google_link'     => 'googleplus',
			'digg_link'       => 'digg',
			'blogger_link'    => 'blogger',
			'skype_link'      => 'skype',
			'myspace_link'    => 'myspace',
			'deviantart_link' => 'deviantart',
			'yahoo_link'      => 'yahoo',
			'reddit_link'     => 'reddit',
			'forrst_link'     => 'forrst',
			'paypal_link'     => 'paypal',
			'dropbox_link'    => 'dropbox',
			'soundcloud_link' => 'soundcloud',
			'vk_link'         => 'vk',
			'email_link'      => 'email',
		);

		foreach ( $social_links as $key => $value ) {
			if ( Avada()->settings->get( $key ) ) {
				$social_links_array[$value] = Avada()->settings->get( $key );
			}
		}

		if ( Avada()->settings->get( 'custom_icon_name' ) && Avada()->settings->get( 'custom_icon_image' ) && Avada()->settings->get( 'custom_icon_link' ) ) {
			$social_links_array['custom'] = Avada()->settings->get( 'custom_icon_link' );
		}

		return $social_links_array;
	}

	/**
	 * Set up the array for sharing box social networks.
	 *
	 * @since 3.5.0
	 * @param  array  $args Holding all necessarry data for social icons
	 * @return array  The social links array containing the social media and links to them.
	 */
	function get_sharingbox_social_links_array( $args ) {

		$social_links_array = array();

			if( Avada()->settings->get( 'sharing_facebook' ) ) {
				$social_link = 'http://www.facebook.com/sharer.php?m2w&s=100&p&#91;url&#93;=' . $args['link'] . '&p&#91;images&#93;&#91;0&#93;=' . wp_get_attachment_url( get_post_thumbnail_id( get_the_ID() ) ) . '&p&#91;title&#93;=' . rawurlencode( $args['title'] );
				$social_links_array['facebook'] = $social_link;
			}

			if( Avada()->settings->get( 'sharing_twitter' ) ) {
				$social_link = 'https://twitter.com/share?text=' . rawurlencode( html_entity_decode( $args['title'], ENT_COMPAT, 'UTF-8' ) ) . '&url=' . rawurlencode( $args['link'] );
				$social_links_array['twitter'] = $social_link;
			}

			if( Avada()->settings->get( 'sharing_linkedin' ) ) {
				$social_link = 'https://www.linkedin.com/shareArticle?mini=true&url=' . $args['link'] . '&amp;title=' . rawurlencode( $args['title'] ) . '&amp;summary=' . rawurlencode( $args['description'] );
				$social_links_array['linkedin'] = $social_link;
			}

			if( Avada()->settings->get( 'sharing_reddit' ) ) {
				$social_link = 'http://reddit.com/submit?url=' . $args['link'] . '&amp;title=' . rawurlencode( $args['title'] );
				$social_links_array['reddit'] = $social_link;
			}

			if( Avada()->settings->get( 'sharing_tumblr' ) ) {
				$social_link = 'http://www.tumblr.com/share/link?url=' . rawurlencode( $args['link'] ) . '&amp;name=' . rawurlencode( $args['title'] ) .'&amp;description=' . rawurlencode( $args['description'] );
				$social_links_array['tumblr'] = $social_link;
			}

			if( Avada()->settings->get( 'sharing_google' ) ) {
				$social_link = 'https://plus.google.com/share?url=' . $args['link'];
				$social_links_array['googleplus'] = $social_link;
			}

			if( Avada()->settings->get( 'sharing_pinterest' ) ) {
				$social_link = 'http://pinterest.com/pin/create/button/?url=' . urlencode( $args['link'] ) . '&amp;description=' . rawurlencode( $args['description'] ) . '&amp;media=' . rawurlencode( $args['pinterest_image'] );
				$social_links_array['pinterest'] = $social_link;
			}

			if( Avada()->settings->get( 'sharing_vk' ) ) {
				$social_link = sprintf( 'http://vkontakte.ru/share.php?url=%s&amp;title=%s&amp;description=%s', rawurlencode( $args['link'] ), rawurlencode( $args['title'] ), rawurlencode( $args['description'] ) );
				$social_links_array['vk'] = $social_link;
			}

			if( Avada()->settings->get( 'sharing_email' ) ) {
				$social_link = 'mailto:?subject=' . $args['title'] . '&amp;body=' . $args['link'];
				$social_links_array['email'] = $social_link;
			}

			return $social_links_array;
	}

	/**
	 * Set up the array for author page social networks.
	 *
	 * @since 3.5.0
	 * @param  array  $args Holding all necessarry data for social icons
	 * @return array  The social links array containing the social media and links to them.
	 */
	function get_authorpage_social_links_array( $args ) {

		$social_links_array = array();

			if( get_the_author_meta( 'author_facebook', $args['author_id'] ) ) {
				$social_links_array['facebook'] = get_the_author_meta( 'author_facebook', $args['author_id'] );
			}

			if( get_the_author_meta( 'author_twitter', $args['author_id'] ) ) {
				$social_links_array['twitter'] = get_the_author_meta( 'author_twitter', $args['author_id'] );
			}

			if( get_the_author_meta( 'author_linkedin', $args['author_id'] ) ) {
				$social_links_array['linkedin'] = get_the_author_meta( 'author_linkedin', $args['author_id'] );
			}

			if( get_the_author_meta( 'author_dribble', $args['author_id'] ) ) {
				$social_links_array['dribbble'] = get_the_author_meta( 'author_dribble', $args['author_id'] );
			}

			if( get_the_author_meta( 'author_gplus', $args['author_id'] ) ) {
				$social_links_array['googleplus'] = get_the_author_meta( 'author_gplus', $args['author_id'] );
			}

			if( get_the_author_meta( 'email', $args['author_id'] ) ) {
				$social_links_array['email'] = 'mailto:' . get_the_author_meta( 'email', $args['author_id'] );
			}

			return $social_links_array;
	}

	/**
	 * Reorder a given array by the indices of another given array.
	 *
	 * @since 3.5.0
	 * @param  array  $to_be_ordered The array that will be reordered.
	 * @return array  $order_like The array that gives the ordering structure for $to_be_ordered.
	 */
	function order_array_like_array( Array $to_be_ordered, Array $order_like ) {
		$ordered = array();

		return $ordered;
	}

}

// Omit closing PHP tag to avoid "Headers already sent" issues.
