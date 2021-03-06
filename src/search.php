<?php
/**
 * The GIF search script
 *
 * Powers the GIF script filter, and returns both gifs and tags
 *
 * @since 2.0
 */

require_once( 'functions.php' );

// Initialize the workflow data
$flow = new Workflow();

// Check database and prompt for update if needed
if ( is_legacy_db() ) {
	$flow->initiate_update();

// Output the update list item
	$flow->output_items();
	die;
}

// Initialize the remaining data
$input = $argv[1];
$gifs = new GIF_Query( $input );
$tags = new Tag_Query( $input );
$trash = new GIF_Query( '', '', true );

// Clean up any old GIFs that have been in the trash for >30 days
trash_cleanup();

// Using 'add' or a valid URL as a keyword, display prompt to add a new GIF
if ( 'add' === $input || is_valid_url( $input ) ) {
	$flow->add_gif( $input );
}

// Using 'trash' as a keyword, display prompt to view trash
if ( 'trash' === $input ) {
	$flow->view_trash( $trash );
}

//The Gifomattic loop!
// Imitation is the sincerest form of flattery...
if ( $gifs->have_gifs() || $tags->have_tags() ) {

	// Add any tags returned by the current query to the items array
	while ( $tags->have_tags() ) {
		$the_tag = $tags->the_tag();

		$flow->the_tag( $the_tag, $input );
	}

	// Add any GIFs returned by the current query to the items array
	while ( $gifs->have_gifs() ) {
		$the_gif = $gifs->the_gif();

		$flow->the_gif( $the_gif, $input, 'search' );
	}
} else {
	$flow->no_results( 'both' );
}

// Output the list of items
$flow->output_items();