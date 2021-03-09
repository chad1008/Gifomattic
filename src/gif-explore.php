<?php
/**
 * The GIFs with tag script
 *
 * Powers the GIFs with tag script filter (CMD modifier on GIF search)
 *
 * @since 2.0
 */

require_once ( 'functions.php' );

// Initialize the data
$input = $argv[1];
$flow = new Workflow();

if ( is_tag() ) {
	// Query the database for GIFs with names that match input and an assigned tag that matches the provided ID
	$gifs = new GIF_Query( $input, $flow->item_id) ;

	if ( $gifs->have_gifs() ) {

		// Add any GIFs returned by the current query to the items array
		while ( $gifs->have_gifs() ) {
			$the_gif = $gifs->the_gif();

			$flow->the_gif( $the_gif );
		}
	}
} elseif ( is_gif() ) {
	// Query the requested GIF
	$the_gif = new GIF( $flow->item_id );
	
	// Display the various GIF details
	$flow->display_gif_name( $the_gif );
	$flow->display_gif_selected_count( $the_gif );
	$flow->display_gif_random_count( $the_gif );
	$flow->display_gif_total_count( $the_gif );
	$flow->display_gif_date( $the_gif );
	$flow->display_gif_tags( $the_gif );
}

// Fix unused modifier keys
$items = fix_mods( $flow->items );

// Encode the items array as JSON for Alfred's output
echo json_encode( $items );
