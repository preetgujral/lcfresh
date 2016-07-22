<?php

class Fusion_Widget_Social_Links extends WP_Widget {

	function __construct() {

		$widget_ops  = array( 'classname' => 'social_links', 'description' => '' );
		$control_ops = array( 'id_base' => 'social_links-widget' );

		parent::__construct( 'social_links-widget', 'Avada: Social Links', $widget_ops, $control_ops );

	}

	function widget( $args, $instance ) {

		extract( $args );
		$title     = apply_filters( 'widget_title', isset( $instance['title'] ) ? $instance['title'] : '' );
		$add_class = '';
		$style     = '';
		$nofollow  = ( Avada()->settings->get( 'nofollow_social_links' ) ) ? ' rel="nofollow"' : '';

		if ( ! isset( $instance['tooltip_pos'] ) || ! $instance['tooltip_pos'] ) {
			$instance['tooltip_pos'] = Avada()->settings->get( 'social_links_tooltip_placement' );
		}

		if ( ! isset( $instance['icon_color'] ) || ! $instance['icon_color'] ) {
			$instance['icon_color'] = Avada()->settings->get( 'social_links_icon_color' );
		}

		if ( ! isset( $instance['boxed_icon'] ) || ! $instance['boxed_icon'] ) {
			$instance['boxed_icon'] = Avada()->settings->get( 'social_links_boxed' );
		}

		if ( ! isset( $instance['boxed_color'] ) || ! $instance['boxed_color'] ) {
			$instance['boxed_color'] = Avada()->settings->get( 'social_links_box_color' );
		}

		if ( ! isset( $instance['boxed_icon_radius'] ) || ! $instance['boxed_icon_radius'] ) {
			$instance['boxed_icon_radius'] = intval( Avada()->settings->get( 'social_links_boxed_radius' ) ) . 'px';
		}

		if ( ! isset($instance['linktarget']) || empty($instance['linktarget'] ) ) {
			$instance['linktarget'] = '_self';
		}

		if ( ! isset( $instance['tooltip_pos'] ) ) {
			$instance['tooltip_pos'] = 'top';
		}

		if ( isset( $instance['boxed_icon'] )  && isset( $instance['boxed_icon_radius'] ) && 'Yes' == $instance['boxed_icon'] && ( $instance['boxed_icon_radius'] || '0' === $instance['boxed_icon_radius'] ) ) {
			$instance['boxed_icon_radius'] = ( 'round' == $instance['boxed_icon_radius'] ) ? '50%' : $instance['boxed_icon_radius'];
			$style .= 'border-radius:' . $instance['boxed_icon_radius'] . ';';
		}

		if ( isset( $instance['boxed_icon'] )  && 'Yes' == $instance['boxed_icon'] && isset( $instance['boxed_icon_padding'] )  && isset( $instance['boxed_icon_padding'] ) ) {
			$style .= 'padding:' . $instance['boxed_icon_padding'] . ';';
		}

		if ( isset ( $instance['boxed_icon'] ) && 'Yes' == $instance['boxed_icon'] ) {
			$add_class .= ' boxed-icons';
		}

		if ( ! isset( $instance['icons_font_size'] ) || '' == $instance['icons_font_size'] ) {
			$instance['icons_font_size'] = '16px';
		}

		$style .= 'font-size:' . $instance['icons_font_size'] . ';';

		foreach ( $instance as $name => $value ) {
			if ( false !== strpos( $name, '_link' ) ) {
				$social_networks[ $name ] = str_replace( '_link', '', $name );
			}
		}

		if ( Avada()->settings->get( 'social_sorter' ) ) {
			$order         = Avada()->settings->get( 'social_sorter' );
			$ordered_array = explode( ',', $order );

			if ( isset( $ordered_array ) && $ordered_array && is_array( $ordered_array ) ) {
				$social_networks_old = $social_networks;
				$social_networks     = array();

				foreach ( $ordered_array as $key => $field_order ) {

					$field_order_number = str_replace( 'social_sorter_', '', $field_order );
					$find_the_field     = Avada()->settings->get( 'social_sorter_' . $field_order_number );
					$field_name         = str_replace( '_link', '', Avada()->settings->get( 'social_sorter_' . $field_order_number ) );
					$field_name         = ( 'email' == $field_name ) ? 'mail' : $field_name;
					$field_name         = ( 'facebook' == $field_name ) ? 'fb' : $field_name;
					$field_name         = $field_name . '_link';

					if ( ! isset( $social_networks_old[ $field_name ] ) ) {
						continue;
					}

					$social_networks[ $field_name ] = $social_networks_old[ $field_name ];

				}

			}

		}

		$icon_colors     = array();
		$icon_colors_max = 1;

		if ( isset( $instance['icon_color'] ) && $instance['icon_color'] ) {
			$icon_colors     = explode( '|', $instance['icon_color'] );
			$icon_colors_max = count( $icon_colors );
		}

		$box_colors     = array();
		$box_colors_max = 1;

		if ( isset( $instance['boxed_color'] ) && $instance['boxed_color'] ) {
			$box_colors     = explode( '|', $instance['boxed_color'] );
			$box_colors_max = count( $box_colors );
		}
		?>

		<?php echo $before_widget; ?>
		<?php if ( $title ) : ?>
			<?php echo $before_title . $title . $after_title; ?>
		<?php endif; ?>

		<div class="fusion-social-networks<?php echo $add_class; ?>">
			<div class="fusion-social-networks-wrapper">
				<?php $icon_color_count = 0; ?>
				<?php $box_color_count  = 0; ?>

				<?php foreach ( $social_networks as $name => $value ) : ?>

					<?php if ( $instance[ $name ] ) : ?>

						<?php $value = ( 'fb' == $value ) ? 'facebook' : $value; ?>
						<?php $value = ( 'rss' == $value ) ? 'feed' : $value; ?>
						<?php $value = ( 'google' == $value ) ? 'googleplus' : $value; ?>

						<?php $tooltip = $value; ?>
						<?php $tooltip = ( 'googleplus' == $tooltip ) ? 'Google+' : $tooltip; ?>

						<?php $icon_style = ''; ?>
						<?php $box_style  = ''; ?>

						<?php if ( isset( $icon_colors[ $icon_color_count ] ) && $icon_colors[ $icon_color_count ] ) : ?>
							<?php $icon_style = 'color:' . trim( $icon_colors[ $icon_color_count ] ) . ';'; ?>
						<?php elseif ( isset( $icon_colors[ ( $icon_colors_max - 1 ) ] ) ) : ?>
							<?php $icon_style = 'color:' . trim( $icon_colors[ ( $icon_colors_max - 1 ) ] ) . ';'; ?>
						<?php endif; ?>

						<?php if ( isset ( $instance['boxed_icon'] ) && 'Yes' == $instance['boxed_icon'] && isset( $box_colors[ $box_color_count ] ) && $box_colors[ $box_color_count ] ) : ?>
							<?php $box_style = 'background-color:' . trim( $box_colors[ $box_color_count ] ) . ';border-color:' . trim( $box_colors[ $box_color_count ] ) . ';'; ?>
						<?php elseif ( isset( $instance['boxed_icon'] ) && 'Yes' == $instance['boxed_icon'] && isset( $box_colors[ ( $box_colors_max - 1 ) ] ) && ( ! isset( $box_colors[ $box_color_count ] ) || ! $box_colors[ $box_color_count ] ) ) : ?>
							<?php $box_style = 'background-color:' . trim( $box_colors[ ( $box_colors_max - 1 ) ] ) . ';border-color:' . trim( $box_colors[ ( $box_colors_max - 1 ) ] ) . ';'; ?>
						<?php endif; ?>

						<?php if ( 'none' != strtolower( $instance['tooltip_pos'] ) ) : ?>
							<a class="fusion-social-network-icon fusion-tooltip fusion-<?php echo $value; ?> fusion-icon-<?php echo $value; ?>" href="<?php echo $instance[ $name ]; ?>" data-placement="<?php echo strtolower( $instance['tooltip_pos'] ); ?>" data-title="<?php echo ucwords( $tooltip ); ?>" data-toggle="tooltip" data-original-title="" title="<?php echo ucwords( $tooltip ); ?>" <?php echo $nofollow; ?> target="<?php echo $instance['linktarget']; ?>" style="<?php echo $style; ?><?php echo $icon_style; ?><?php echo $box_style; ?>"></a>
						<?php else : ?>
							<a class="fusion-social-network-icon fusion-tooltip fusion-<?php echo $value; ?> fusion-icon-<?php echo $value; ?>" href="<?php echo $instance[ $name ]; ?>" title="<?php echo ucwords( $tooltip ); ?>" <?php echo $nofollow; ?> target="<?php echo $instance['linktarget']; ?>" style="<?php echo $style; ?><?php echo $icon_style; ?><?php echo $box_style; ?>"></a>
						<?php endif; ?>

						<?php $icon_color_count++; ?>
						<?php $box_color_count++; ?>

					<?php endif; ?>

				<?php endforeach; ?>

				<?php if ( isset( $instance['show_custom'] ) && 'Yes' == $instance['show_custom'] && Avada()->settings->get( 'custom_icon_name' ) && Avada()->settings->get( 'custom_icon_image' ) ) : ?>
					<a class="fusion-social-network-icon fusion-tooltip" target="<?php echo $instance['linktarget']; ?>" href="<?php echo Avada()->settings->get( 'custom_icon_link' ); ?>"<?php echo $nofollow; ?> data-placement="<?php echo strtolower( $instance['tooltip_pos'] ); ?>" data-title="<?php echo Avada()->settings->get( 'custom_icon_name' ); ?>" data-toggle="tooltip" data-original-title="" title="" style="<?php echo $style; ?>"><img src="<?php echo Avada()->settings->get( 'custom_icon_image' ); ?>" alt="<?php echo Avada()->settings->get( 'custom_icon_name' ); ?>" /></a>
				<?php endif; ?>
			</div>
		</div>

		<?php echo $after_widget;

	}

	function update( $new_instance, $old_instance ) {

		$instance = $old_instance;

		$instance['title']              = $new_instance['title'];
		$instance['linktarget']         = $new_instance['linktarget'];
		$instance['icons_font_size']    = $new_instance['icons_font_size'];
		$instance['icon_color']         = $new_instance['icon_color'];
		$instance['boxed_icon']         = $new_instance['boxed_icon'];
		$instance['boxed_color']        = $new_instance['boxed_color'];
		$instance['boxed_icon_radius']  = $new_instance['boxed_icon_radius'];
		$instance['boxed_icon_padding'] = $new_instance['boxed_icon_padding'];
		$instance['tooltip_pos']        = $new_instance['tooltip_pos'];
		$instance['show_custom']        = $new_instance['show_custom'];
		$instance['fb_link']            = $new_instance['fb_link'];
		$instance['flickr_link']        = $new_instance['flickr_link'];
		$instance['rss_link']           = $new_instance['rss_link'];
		$instance['twitter_link']       = $new_instance['twitter_link'];
		$instance['vimeo_link']         = $new_instance['vimeo_link'];
		$instance['youtube_link']       = $new_instance['youtube_link'];
		$instance['instagram_link']     = $new_instance['instagram_link'];
		$instance['pinterest_link']     = $new_instance['pinterest_link'];
		$instance['tumblr_link']        = $new_instance['tumblr_link'];
		$instance['google_link']        = $new_instance['google_link'];
		$instance['dribbble_link']      = $new_instance['dribbble_link'];
		$instance['digg_link']          = $new_instance['digg_link'];
		$instance['linkedin_link']      = $new_instance['linkedin_link'];
		$instance['blogger_link']       = $new_instance['blogger_link'];
		$instance['skype_link']         = $new_instance['skype_link'];
		$instance['forrst_link']        = $new_instance['forrst_link'];
		$instance['myspace_link']       = $new_instance['myspace_link'];
		$instance['deviantart_link']    = $new_instance['deviantart_link'];
		$instance['yahoo_link']         = $new_instance['yahoo_link'];
		$instance['reddit_link']        = $new_instance['reddit_link'];
		$instance['paypal_link']        = $new_instance['paypal_link'];
		$instance['dropbox_link']       = $new_instance['dropbox_link'];
		$instance['soundcloud_link']    = $new_instance['soundcloud_link'];
		$instance['vk_link']            = $new_instance['vk_link'];

		return $instance;

	}

	function form( $instance ) {

		$defaults = array(
			'title'              => __( 'Get Social', 'Avada' ),
			'linktarget'         => '',
			'icons_font_size'    => '16px',
			'icon_color'         => '',
			'boxed_icon'         => 'No',
			'boxed_color'        => '',
			'boxed_icon_radius'  => '4px',
			'boxed_icon_padding' => '8px',
			'tooltip_pos'        => 'top',
			'rss_link'           => '',
			'fb_link'            => '',
			'twitter_link'       => '',
			'dribbble_link'      => '',
			'google_link'        => '',
			'linkedin_link'      => '',
			'blogger_link'       => '',
			'tumblr_link'        => '',
			'reddit_link'        => '',
			'yahoo_link'         => '',
			'deviantart_link'    => '',
			'vimeo_link'         => '',
			'youtube_link'       => '',
			'pinterest_link'     => '',
			'digg_link'          => '',
			'flickr_link'        => '',
			'forrst_link'        => '',
			'myspace_link'       => '',
			'skype_link'         => '',
			'instagram_link'     => '',
			'vk_link'            => '',
			'dropbox_link'       => '',
			'soundcloud_link'    => '',
			'paypal_link'        => '',
			'show_custom'        => 'No',
		);

		$instance = wp_parse_args((array) $instance, $defaults);
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'Avada' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'linktarget' ); ?>"><?php _e( 'Link Target:', 'Avada' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'linktarget' ); ?>" name="<?php echo $this->get_field_name( 'linktarget' ); ?>" value="<?php echo $instance['linktarget']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'icons_font_size' ); ?>"><?php _e( 'Icons Font Size:', 'Avada' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'icons_font_size' ); ?>" name="<?php echo $this->get_field_name( 'icons_font_size' ); ?>" value="<?php echo $instance['icons_font_size']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'icon_color' ); ?>"><?php _e( 'Icons Color Hex Code:', 'Avada' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'icon_color' ); ?>" name="<?php echo $this->get_field_name( 'icon_color' ); ?>" value="<?php echo $instance['icon_color']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'boxed_icon' ); ?>"><?php _e( 'Icons Boxed:', 'Avada' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'boxed_icon' ); ?>" name="<?php echo $this->get_field_name( 'boxed_icon' ); ?>" class="widefat" style="width:100%;">
				<option value="No" <?php if ( 'No' == $instance['boxed_icon'] ) echo 'selected="selected"'; ?>><?php _e( 'No', 'Avada' ); ?></option>
				<option value="Yes" <?php if ( 'Yes' == $instance['boxed_icon'] ) echo 'selected="selected"'; ?>><?php _e( 'Yes', 'Avada' ); ?></option>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'boxed_color' ); ?>"><?php _e( 'Boxed Icons Background Color Hex Code:', 'Avada' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'boxed_color' ); ?>" name="<?php echo $this->get_field_name( 'boxed_color' ); ?>" value="<?php echo $instance['boxed_color']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'boxed_icon_radius' ); ?>"><?php _e( 'Boxed Icons Radius:', 'Avada' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'boxed_icon_radius' ); ?>" name="<?php echo $this->get_field_name( 'boxed_icon_radius' ); ?>" value="<?php echo $instance['boxed_icon_radius']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'boxed_icon_padding' ); ?>"><?php _e( 'Boxed Icons Padding:', 'Avada' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'boxed_icon_padding' ); ?>" name="<?php echo $this->get_field_name( 'boxed_icon_padding' ); ?>" value="<?php echo $instance['boxed_icon_padding']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'tooltip_pos' ); ?>"><?php _e( 'Tooltip Position:', 'Avada' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'tooltip_pos' ); ?>" name="<?php echo $this->get_field_name( 'tooltip_pos' ); ?>" class="widefat" style="width:100%;">
				<option value="Top" <?php if ( 'Top' == $instance['tooltip_pos']) echo 'selected="selected"'; ?>><?php _e( 'Top', 'Avada' ); ?></option>
				<option value="Right" <?php if ( 'Right' == $instance['tooltip_pos']) echo 'selected="selected"'; ?>><?php _e( 'Right', 'Avada' ); ?></option>
				<option value="Bottom" <?php if ( 'Bottom' == $instance['tooltip_pos']) echo 'selected="selected"'; ?>><?php _e( 'Bottom', 'Avada' ); ?></option>
				<option value="Left" <?php if ( 'Left' == $instance['tooltip_pos']) echo 'selected="selected"'; ?>><?php _e( 'Left', 'Avada' ); ?></option>
				<option value="None" <?php if ( 'None' == $instance['tooltip_pos']) echo 'selected="selected"'; ?>><?php _e( 'None', 'Avada' ); ?></option>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'show_custom' ); ?>"><?php _e( 'Show Custom Icon:', 'Avada' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'show_custom' ); ?>" name="<?php echo $this->get_field_name( 'show_custom' ); ?>" class="widefat" style="width:100%;">
				<option value="No" <?php if ( 'No' == $instance['show_custom'] ) echo 'selected="selected"'; ?>><?php _e( 'No', 'Avada' ); ?></option>
				<option value="Yes" <?php if ( 'Yes' == $instance['show_custom'] ) echo 'selected="selected"'; ?>><?php _e( 'Yes', 'Avada' ); ?></option>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'rss_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'RSS' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'rss_link' ); ?>" name="<?php echo $this->get_field_name( 'rss_link' ); ?>" value="<?php echo $instance['rss_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'fb_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'Facebook' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'fb_link' ); ?>" name="<?php echo $this->get_field_name( 'fb_link' ); ?>" value="<?php echo $instance['fb_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'twitter_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'Twitter' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'twitter_link' ); ?>" name="<?php echo $this->get_field_name( 'twitter_link' ); ?>" value="<?php echo $instance['twitter_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'dribbble_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'Dribbble' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'dribbble_link' ); ?>" name="<?php echo $this->get_field_name( 'dribbble_link' ); ?>" value="<?php echo $instance['dribbble_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'google_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'Google+' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'google_link' ); ?>" name="<?php echo $this->get_field_name( 'google_link' ); ?>" value="<?php echo $instance['google_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'linkedin_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'LinkedIn' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'linkedin_link' ); ?>" name="<?php echo $this->get_field_name( 'linkedin_link' ); ?>" value="<?php echo $instance['linkedin_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'blogger_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'Blogger' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'blogger_link' ); ?>" name="<?php echo $this->get_field_name( 'blogger_link' ); ?>" value="<?php echo $instance['blogger_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'tumblr_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'Tumblr' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'tumblr_link' ); ?>" name="<?php echo $this->get_field_name( 'tumblr_link' ); ?>" value="<?php echo $instance['tumblr_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'reddit_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'Reddit' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'reddit_link' ); ?>" name="<?php echo $this->get_field_name( 'reddit_link' ); ?>" value="<?php echo $instance['reddit_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'yahoo_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'Yahoo' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'yahoo_link' ); ?>" name="<?php echo $this->get_field_name( 'yahoo_link' ); ?>" value="<?php echo $instance['yahoo_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'deviantart_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'Deviantart' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'deviantart_link' ); ?>" name="<?php echo $this->get_field_name( 'deviantart_link' ); ?>" value="<?php echo $instance['deviantart_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'vimeo_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'Vimeo' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'vimeo_link' ); ?>" name="<?php echo $this->get_field_name( 'vimeo_link' ); ?>" value="<?php echo $instance['vimeo_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'youtube_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'Youtube' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'youtube_link' ); ?>" name="<?php echo $this->get_field_name( 'youtube_link' ); ?>" value="<?php echo $instance['youtube_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'pinterest_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'Pinterest' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'pinterest_link' ); ?>" name="<?php echo $this->get_field_name( 'pinterest_link' ); ?>" value="<?php echo $instance['pinterest_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'digg_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'Digg' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'digg_link' ); ?>" name="<?php echo $this->get_field_name( 'digg_link' ); ?>" value="<?php echo $instance['digg_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'flickr_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'Flickr' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'flickr_link' ); ?>" name="<?php echo $this->get_field_name( 'flickr_link' ); ?>" value="<?php echo $instance['flickr_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'forrst_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'Forrst' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'forrst_link' ); ?>" name="<?php echo $this->get_field_name( 'forrst_link' ); ?>" value="<?php echo $instance['forrst_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'myspace_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'Myspace' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'myspace_link' ); ?>" name="<?php echo $this->get_field_name( 'myspace_link' ); ?>" value="<?php echo $instance['myspace_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'skype_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'Skype' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'skype_link' ); ?>" name="<?php echo $this->get_field_name( 'skype_link' ); ?>" value="<?php echo $instance['skype_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'instagram_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'Instagram' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'instagram_link' ); ?>" name="<?php echo $this->get_field_name( 'instagram_link' ); ?>" value="<?php echo $instance['instagram_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'vk_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'VK' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'vk_link' ); ?>" name="<?php echo $this->get_field_name( 'vk_link' ); ?>" value="<?php echo $instance['vk_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'paypal_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'PayPal' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'paypal_link' ); ?>" name="<?php echo $this->get_field_name( 'paypal_link' ); ?>" value="<?php echo $instance['paypal_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'dropbox_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'Dropbox' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'dropbox_link' ); ?>" name="<?php echo $this->get_field_name( 'dropbox_link' ); ?>" value="<?php echo $instance['dropbox_link']; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'soundcloud_link' ); ?>"><?php printf( __( '%s Link:', 'Avada' ), 'Soundcloud' ); ?></label>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'soundcloud_link' ); ?>" name="<?php echo $this->get_field_name( 'soundcloud_link' ); ?>" value="<?php echo $instance['soundcloud_link']; ?>" />
		</p>
		<?php

	}

}

// Omit closing PHP tag to avoid "Headers already sent" issues.
