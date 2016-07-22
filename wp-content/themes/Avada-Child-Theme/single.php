<?php get_header(); ?>
	<div id="content" <?php Avada()->layout->add_style( 'content_style' ); ?>>
		<?php if( ( ! Avada()->settings->get( 'blog_pn_nav' ) && get_post_meta($post->ID, 'pyre_post_pagination', true) != 'no' ) ||
				  ( Avada()->settings->get( 'blog_pn_nav' ) && get_post_meta($post->ID, 'pyre_post_pagination', true) == 'yes' ) ): ?>
		<div class="single-navigation clearfix">
			<?php previous_post_link('%link', __('Previous', 'Avada')); ?>
			<?php next_post_link('%link', __('Next', 'Avada')); ?>
		</div>
		<?php endif; ?>
		<?php while( have_posts() ): the_post(); ?>

		<div id="post-<?php the_ID(); ?>" <?php post_class('post'); ?>>
			<?php
			$full_image = '';
			if( ! post_password_required($post->ID) ): // 1
			if(Avada()->settings->get( 'featured_images_single' )): // 2
			if( avada_number_of_featured_images() > 0 || get_post_meta( $post->ID, 'pyre_video', true ) ): // 3
			?>
			<div class="fusion-flexslider flexslider fusion-flexslider-loading post-slideshow fusion-post-slideshow">
				<ul class="slides">
					<?php if(get_post_meta($post->ID, 'pyre_video', true)): ?>
					<li>
						<div class="full-video">
							<?php echo get_post_meta($post->ID, 'pyre_video', true); ?>
						</div>
					</li>
					<?php endif; ?>
					<?php if( has_post_thumbnail() && get_post_meta( $post->ID, 'pyre_show_first_featured_image', true ) != 'yes' ): ?>
					<?php $attachment_image = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full'); ?>
					<?php $full_image = wp_get_attachment_image_src(get_post_thumbnail_id(), 'full'); ?>
					<?php $attachment_data = wp_get_attachment_metadata(get_post_thumbnail_id()); ?>
					<li>
						<?php if( ! Avada()->settings->get( 'status_lightbox' ) && ! Avada()->settings->get( 'status_lightbox_single' ) ): ?>
						<a href="<?php echo $full_image[0]; ?>" data-rel="iLightbox[gallery<?php the_ID(); ?>]" title="<?php echo get_post_field('post_excerpt', get_post_thumbnail_id()); ?>" data-title="<?php echo get_post_field('post_title', get_post_thumbnail_id()); ?>" data-caption="<?php echo get_post_field('post_excerpt', get_post_thumbnail_id()); ?>"><img src="<?php echo $attachment_image[0]; ?>" alt="<?php echo get_post_meta(get_post_thumbnail_id(), '_wp_attachment_image_alt', true); ?>" /></a>
						<?php else: ?>
						<img src="<?php echo $attachment_image[0]; ?>" alt="<?php echo get_post_meta(get_post_thumbnail_id(), '_wp_attachment_image_alt', true); ?>" />
						<?php endif; ?>
					</li>
					<?php endif; ?>
					<?php
					$i = 2;
					while($i <= Avada()->settings->get( 'posts_slideshow_number' )):
					$attachment_new_id = kd_mfi_get_featured_image_id('featured-image-'.$i, 'post');
					if($attachment_new_id):
					?>
					<?php $attachment_image = wp_get_attachment_image_src($attachment_new_id, 'full'); ?>
					<?php $full_image = wp_get_attachment_image_src($attachment_new_id, 'full'); ?>
					<?php $attachment_data = wp_get_attachment_metadata($attachment_new_id); ?>
					<li>
						<?php if( ! Avada()->settings->get( 'status_lightbox' ) && ! Avada()->settings->get( 'status_lightbox_single' ) ): ?>
						<a href="<?php echo $full_image[0]; ?>" data-rel="iLightbox[gallery<?php the_ID(); ?>]" title="<?php echo get_post_field('post_excerpt', $attachment_new_id); ?>" data-title="<?php echo get_post_field( 'post_title', $attachment_new_id ); ?>" data-caption="<?php echo get_post_field('post_excerpt', $attachment_new_id ); ?>"><img src="<?php echo $attachment_image[0]; ?>" alt="<?php echo get_post_meta($attachment_new_id, '_wp_attachment_image_alt', true); ?>" /></a>
						<?php else: ?>
						<img src="<?php echo $attachment_image[0]; ?>" alt="<?php echo get_post_meta($attachment_new_id, '_wp_attachment_image_alt', true); ?>" />
						<?php endif; ?>
					</li>
					<?php endif; $i++; endwhile; ?>
				</ul>
			</div>
			<?php endif; // 3 ?>
			<?php endif; // 2 ?>
			<?php endif; // 1 ?>
			<?php if(Avada()->settings->get( 'blog_post_title' )): ?>
			<?php echo avada_render_post_title( $post->ID, FALSE, '', '2' ); ?>
			<?php elseif( ! Avada()->settings->get( 'disable_date_rich_snippet_pages' ) ): ?>
			<span class="entry-title" style="display: none;"><?php the_title(); ?></span>
			<?php endif; ?>
			<div class="post-content">
        <?php if( is_singular( 'opeds' )) : ?>
          <p id='original-pub'>
            <a href="<?php echo get_post_meta($post->ID, 'Link', true); ?>" target=_blank>Originally Published on <?php echo get_post_meta($post->ID, 'Date Published', true); ?>.</a>
          </p>
        <?php endif; ?>
        <?php the_content(); ?>
        <?php avada_link_pages(); ?>
			</div>
			<?php if( ! post_password_required($post->ID) ): ?>
			<?php echo avada_render_post_metadata( 'single' ); ?>
			<?php avada_render_social_sharing(); ?>
			<?php if( ( Avada()->settings->get( 'author_info' ) && get_post_meta($post->ID, 'pyre_author_info', true) != 'no' ) ||
					  ( ! Avada()->settings->get( 'author_info' ) && get_post_meta($post->ID, 'pyre_author_info', true) == 'yes' ) ): ?>
			<div class="about-author">
				<?php
					ob_start();
					the_author_posts_link();
					$title = sprintf( '%s %s', __( 'About the Author:', 'Avada' ), ob_get_clean() );
					echo Avada()->template->title_template( $title, '3' );
				?>
				<div class="about-author-container">
					<div class="avatar">
						<?php echo get_avatar(get_the_author_meta('email'), '72'); ?>
					</div>
					<div class="description">
						<?php the_author_meta("description"); ?>
					</div>
				</div>
			</div>
			<?php endif; ?>

			<?php
			// Render Related Posts
			echo avada_render_related_posts();
			?>

			<?php if( ( Avada()->settings->get( 'blog_comments' ) && get_post_meta($post->ID, 'pyre_post_comments', true ) != 'no' ) ||
					  ( ! Avada()->settings->get( 'blog_comments' ) && get_post_meta($post->ID, 'pyre_post_comments', true) == 'yes' ) ): ?>
				<?php
				wp_reset_query();
				comments_template();
				?>
			<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php endwhile; ?>
		<?php wp_reset_query(); ?>
	</div>
	<?php do_action( 'fusion_after_content' ); ?>
<?php get_footer();

// Omit closing PHP tag to avoid "Headers already sent" issues.
