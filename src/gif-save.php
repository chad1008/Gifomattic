<?php
/**
 * The Save GIF script
 *
 * Powers submission of the Add GIF and Edit GIF flows
 */

require_once( 'functions.php' );

$id = getenv( 'item_id' );

// If the current item is a GIF, enter the GIF saving flow
if ( is_gif() ) {

	// If the selected item is an existing GIF, query it using the ID
	if ( $id != false ) {
		$gif = new GIF( getenv('item_id') );

	// Otherwise, initialize a new, empty GIF object
	} else {
		$gif = new GIF();

		// Stage the date for saving later
		$gif->new_props['date'] = date('F d, Y');
	}

// Stage new/updated values for saving later, skipping any that haven't been provided
	if (getenv('gif_url')) {
		$gif->new_props['url'] = getenv('gif_url');
	}
	if (getenv('gif_name')) {
		$gif->new_props['name'] = getenv('gif_name');
	}

// Save the GIF
	$gif->save();

// Set up the next step
	$output = array(
		'alfredworkflow' => array(
			'arg' => '',
			'variables' => array(
				'item_id' => $gif->new_props['id'],
				'tag_edit_mode' => $gif->is_new ? 'add_tags' : '',
			),
		),
	);

// If the current item is a tag, enter the tag saving flow
} elseif ( is_tag() ) {
	
	// Query the tag in the database and set it's new name
	$tag = new Tag( $id );
	$tag->new_name = getenv( 'tag_name' );
	
	// Save the tag with it's new name
	$tag->save();

	// Set the output array to exit the workflow
	$output = array(
		'alfredworkflow' => array(
			'variables'  => array(
				'exit'   => 'true',
			),
		),
	);
// If the selected item was neither a GIF or a tag, abandon all hope
} else {
	$output = array(
		'alfredworkflow' => array(
			'arg' => 'Something unexpected happened. Please try again.',
		),
	);
}

echo json_encode( $output );
