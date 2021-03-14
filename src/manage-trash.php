<?php
/**
 *
 * The trash management script
 */

require_once ( 'functions.php' );

$flow = new Workflow();
$trash = new GIF_Query( '', '' , TRUE );

// Initialize items array for Alfred output
$items = array(
	'items' => array(),
);

// If this is the launch_trash step, show prompts to either view or empty the trash
if ( $flow->next_step == 'launch_trash') {
	$flow->launch_trash();

// If this is the view_trash step, add any GIFs returned by the current query to the array
} elseif( $flow->next_step == 'view_trash' ) {
	// If there are GIFs, display them
	if ( $trash->have_gifs() ) {

		while ( $trash->have_gifs() ) {
			$the_gif = $trash->the_gif();

			$flow->trashed_gif( $the_gif );

		}
	// If there are no GIFs (the trash is empty), display a 'no results' message
	} else {
		$flow->no_results( 'gifs' );
	}

	// Display navigation back to Trash management
	$flow->navigate( 'launch_trash' );
}
// Output the list of items
$flow->output_items();
