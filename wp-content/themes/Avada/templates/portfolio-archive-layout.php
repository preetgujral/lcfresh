<?php
global $wp_query;

// Set the portfolio main classes
$portfolio_classes[] = 'fusion-portfolio';

$portfolio_layout_setting = strtolower( Avada()->settings->get( 'portfolio_archive_layout' ) );
$portfolio_layout = explode( ' ', $portfolio_layout_setting );
$portfolio_columns = $portfolio_layout[1];
$portfolio_layout = sprintf( 'fusion-portfolio-%s', $portfolio_columns );
$portfolio_classes[] = $portfolio_layout;

// If one column text layout is used, add special class
if ( strpos( $portfolio_layout_setting, 'one' ) &&
	 ! strpos( $portfolio_layout_setting, 'text' )
) {
	$portfolio_classes[] = ' fusion-portfolio-one-nontext';
}

// Add the text class, if a text layout is used
if ( strpos( $portfolio_layout_setting, 'text' ) ||
	 strpos( $portfolio_layout_setting, 'one' )
) {
	$portfolio_classes[] = 'fusion-portfolio-text';
}

// For text layouts add the class for boxed/unboxed
if ( strpos( $portfolio_layout_setting, 'text' ) ) {
	$portfolio_text_layout = Avada()->settings->get( 'portfolio_text_layout' );
	$portfolio_classes[] = sprintf( 'fusion-portfolio-%s', $portfolio_text_layout );
} else {
	$portfolio_text_layout = 'unboxed';
}

// Set the correct image size
if ( Avada()->settings->get( 'portfolio_featured_image_size' ) == 'full' ||
	 $portfolio_layout == 'fusion-portfolio-grid'
) {
	$portfolio_image_size = 'full';
} else {
	$portfolio_image_size = sprintf( 'portfolio-%s', $portfolio_columns );
}

$post_featured_image_size_dimensions = avada_get_image_size_dimensions( $portfolio_image_size );

// Get the column spacing
$column_spacing_class = $column_spacing = '';
if ( ! strpos( $portfolio_layout_setting, 'one' ) ) {
	$column_spacing_class = ' fusion-col-spacing';
	$column_spacing = sprintf( ' style="padding:%spx;"', str_replace( 'px', '', Avada()->settings->get( 'portfolio_column_spacing' ) ) / 2 );
}

// Get the correct ID of the archive
$archive_id = get_queried_object_id();

echo sprintf( '<div class="%s">', implode( ' ', $portfolio_classes ) );
	// Render category description if it is set
	if ( category_description() ) {
		ob_start();
		post_class('post');
		echo sprintf( '<div id="post-%s" %s>', get_the_ID(), ob_get_clean() );
			echo '<div class="post-content">';
				echo category_description();
			echo '</div>';
		echo '</div>';
	}

	// Set picture size as data attribute; needed for resizing placeholders
	$data_picture_size = 'auto';
	if ( $portfolio_image_size != 'full' ) {
		$data_picture_size = 'fixed';
	}

	echo sprintf( '<div class="fusion-portfolio-wrapper" data-picturesize="%s" data-pages="%s">', $data_picture_size, $wp_query->max_num_pages );

		while( have_posts() ): the_post();

			if ( Avada()->settings->get( 'featured_image_placeholder' ) ||
				 has_post_thumbnail()
			) {

				echo sprintf( '<div class="fusion-portfolio-post post-%s %s"%s>', get_the_ID(), $column_spacing_class, $column_spacing );

					// Open portfolio-item-wrapper for text layouts
					if ( strpos( $portfolio_layout_setting, 'text' ) ) {
						echo '<div class="fusion-portfolio-content-wrapper">';
					}

						// On one column layouts render the video set in page options if no featured image is present
						if ( ! has_post_thumbnail() &&
							 fusion_get_page_option( 'video', $post->ID )
						) {
							// For the portfolio one column layout we need a fixed max-width
							if ( $portfolio_layout == 'fusion-portfolio-one' && ! strpos( $portfolio_layout_setting, 'text' ) ) {
								$video_max_width = '540px';
							// For all other layouts get the calculated max-width from the image size
							} else {
								$video_max_width = $post_featured_image_size_dimensions['width'];
							}

							printf( '<div class="fusion-image-wrapper fusion-video" style="max-width:%s;">', $video_max_width );
								echo fusion_get_page_option( 'video', $post->ID );
							echo '</div>';
							
						// On every other other layout render the featured image
						} else {
							echo avada_render_first_featured_image_markup( $post->ID, $portfolio_image_size, get_permalink( $post->ID ), TRUE );
						}

						// If we don't have a text layout and not a one column layout only render rich snippets
						if ( ! strpos( $portfolio_layout_setting, 'text' ) &&
							 ! strpos( $portfolio_layout_setting, 'one' )
						) {
							echo avada_render_rich_snippets_for_pages();
						// If we have a text layout render its contents
						} else {
						echo '<div class="fusion-portfolio-content">';
							// Render the post title
							echo avada_render_post_title( $post->ID );

							// Render the post categories
							echo sprintf( '<h4>%s</h4>', get_the_term_list( $post->ID, 'portfolio_category', '', ', ', '') );
							echo avada_render_rich_snippets_for_pages( false );

							$post_content = '';
							ob_start();
							/**
							 * avada_portfolio_post_content hook
							 *
							 * @hooked avada_get_portfolio_content - 10 (outputs the post content)
							 */
							do_action( 'avada_portfolio_post_content', $archive_id );
							$post_content = ob_get_clean();

							// For boxed layouts add a content separator if there is a post content
							if ( $portfolio_text_layout == 'boxed' &&
								 $post_content
							) {
								echo '<div class="fusion-content-sep"></div>';
							}

							echo '<div class="fusion-post-content">';

								// Echo the post content
								echo $post_content;

								// On one column layouts render the "Learn More" and "View Project" buttons
								if ( strpos( $portfolio_layout_setting, 'one' ) ) {
									echo '<div class="fusion-portfolio-buttons">';
										// Render "Learn More" button
										echo sprintf( '<a href="%s" class="fusion-button fusion-button-small fusion-button-default fusion-button-%s fusion-button-%s">%s</a>',
													  get_permalink( $post->ID ), strtolower( Avada()->settings->get( 'button_shape' ) ), strtolower( Avada()->settings->get( 'button_type' ) ), __( 'Learn More', 'Avada' ) );

										// Render the "View Project" button only is a project url was set
										if ( fusion_get_page_option( 'project_url', $post->ID ) ) {
											echo sprintf( '<a href="%s" class="fusion-button fusion-button-small fusion-button-default fusion-button-%s fusion-button-%s">%s</a>', fusion_get_page_option( 'project_url', $post->ID ),
														  strtolower( Avada()->settings->get( 'button_shape' ) ), strtolower( Avada()->settings->get( 'button_type' ) ), __(' View Project', 'Avada' ) );
										}
									echo '</div>';
								}

							echo '</div>'; // end post-content

							// On unboxed one column layouts render a separator at the bottom of the post
							if ( strpos( $portfolio_layout_setting, 'one' ) &&
								 $portfolio_text_layout == 'unboxed'
							) {
								echo '<div class="fusion-clearfix"></div>';
								echo '<div class="fusion-separator sep-double"></div>';
							}

						echo '</div>'; // end portfolio-content
					} // end template check

					// Close portfolio-item-wrapper for text layouts
					if ( strpos( $portfolio_layout_setting, 'text' ) ) {
						echo '</div>';
					}

				echo '</div>';  // end portfolio-post
			} // placeholders or featured image
		endwhile;
	echo '</div>'; // end portfolio-wrapper

	// Render the pagination
	fusion_pagination($pages = '', $range = 2);

	// If infinite scroll with "load more" button is used
	if ( Avada()->settings->get( 'grid_pagination_type' ) == 'load_more_button' ) {
		echo sprintf( '<div class="fusion-load-more-button fusion-clearfix">%s</div>', apply_filters( 'avada_load_more_posts_name', __( 'Load More Posts', 'Avada' ) ) );
	}

	wp_reset_query();
echo '</div>'; // end fusion-portfolio

// Omit closing PHP tag to avoid "Headers already sent" issues.
