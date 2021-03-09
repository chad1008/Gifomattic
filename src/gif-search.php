<?php
/**
 * The GIF search script
 *
 * Powers the GIF script filter, and returns both gifs and tags
 *
 * @since 2.0
 */

require_once ( 'functions.php' );

// Initialize the workflow data
$flow  = new Workflow();

// Check database and prompt for update if needed
if ( is_legacy_db() ) {
	$flow->initiate_update();

	$items = fix_mods( $flow->items );

	// Encode the items array as JSON for Alfred's output
	echo json_encode( $items );
	die;
}

// Initialize the remaining data
$input = $argv[1];
$gifs  = new GIF_Query( $input );
$tags  = new Tag_Query( $input );
$trash = new GIF_Query( '','',TRUE );



// Clean up any old GIFs that have been in the trash for >30 days
trash_cleanup();

//The Gifomattic loop!
// Imitation is the sincerest form of flattery...
if ( $gifs->have_gifs() || $tags->have_tags() ) {

	// Add any tags returned by the current query to the items array
	while ( $tags->have_tags() ) {
		$the_tag = $tags->the_tag();
		
		$flow->the_tag( $the_tag, 'search' );
	}

	// Add any GIFs returned by the current query to the items array
	while ( $gifs->have_gifs() ) {
		$the_gif = $gifs->the_gif();

		$flow->the_gif( $the_gif );
	}
} else {
	$flow->no_results( 'both' );
}

// Display an option to add a new GIF
$flow->add_gif( $input );

// Using 'trash' as a keyword, display prompt to view Trash, but make sure it's invalid if the trash is empty
if ( 'trash' === $input ) {
	$flow->view_trash( $trash );
}

// Fix unused modifier keys
$items = fix_mods( $flow->items );

// Encode the items array as JSON for Alfred's output
echo json_encode( $items );
