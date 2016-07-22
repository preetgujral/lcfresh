<div id="sidebar" <?php Avada()->layout->add_class( 'sidebar_1_class' ); ?> <?php Avada()->layout->add_style( 'sidebar_1_style' ); ?>>
	<?php
	if (
		! Avada()->template->has_sidebar() ||
		'left' == Avada()->layout->sidebars['position'] ||
		( 'right' == Avada()->layout->sidebars['position'] && ! Avada()->template->double_sidebars() )
	) {
		echo avada_display_sidenav( Avada::c_pageID() );

		if ( class_exists( 'Tribe__Events__Main' ) && is_singular( 'tribe_events' ) ) {
			do_action( 'tribe_events_single_event_before_the_meta' );
			tribe_get_template_part( 'modules/meta' );
			do_action( 'tribe_events_single_event_after_the_meta' );
		}
	}

	if( isset( Avada()->layout->sidebars['sidebar_1'] ) && Avada()->layout->sidebars['sidebar_1'] ) {
		generated_dynamic_sidebar( Avada()->layout->sidebars['sidebar_1'] );
	}
	?>
</div>
