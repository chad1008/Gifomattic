<?php
/**
 * The Save GIF script
 *
 * Powers submission of the Add GIF and Edit GIF flows
 */

require_once( 'functions.php' );

// If the current item is an existing GIF, query it from the database
if ( is_gif() ) {
	$the_gif = new GIF( getenv( 'item_id' ) );
	
} else {
	// Otherwise, initialize a new, empty GIF object
	$the_gif = new GIF();
	
	// Stage the date for saving later
	$the_gif->new_props['date'] = date( 'F d, Y' );
}

// Stage new/updated values for saving later, skipping any that haven't been provided
if ( getenv( 'gif_url' ) ) {
	$the_gif->new_props['url'] = getenv( 'gif_url' );
}
if ( getenv( 'gif_name' ) ) {
	$the_gif->new_props['name'] = getenv( 'gif_name' );
}

// Save the GIF
$the_gif->save();

// Set up the next step
$output = array (
	'alfredworkflow' => array(
		'arg'		 => '',
		'variables'	 => array(
			'item_id'   => $the_gif->new_props['id'],
			'tag_edit_mode' => 'add_tags',
		),
	),
);

echo json_encode( $output );
