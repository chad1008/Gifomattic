<?php
/**
 * The Save GIF script
 *
 * Powers submission of the Add GIF and Edit GIF flows
 */

require_once( 'functions.php' );

$flow = new Workflow();

// If the current item is a GIF, enter the GIF saving flow
if ( is_gif() ) {
	// If the selected item is an existing GIF, query it using the ID
	if ( false != $flow->item_id ) {
		$the_gif = new GIF( $flow->item_id );

	// Otherwise, initialize a new, empty GIF object
	} else {
		$the_gif = new GIF();

		// Stage the date for saving later
		$the_gif->new_props['date'] = date('F d, Y');
	}

	// If this GIF was marked to be trashed, trash it
	if ( 'true' === $flow->trash_mode ) {
		// Trash the GIF
		$the_gif->trash();
		
	// Otherwise, prep and save the new GIF info
	} else {
		// Stage new/updated values for saving, skipping any that haven't been provided
		if ( $flow->gif_url ) {
			$the_gif->new_props['url'] = $flow->gif_url;
		}
		if ( $flow->gif_name ) {
			$the_gif->new_props['name'] = $flow->gif_name;
		}

		// Save the GIF
		$the_gif->save();

		// Output workflow configuration
		$flow->output_config( 'save_gif', $the_gif );
	}

// If the current item is a tag, enter the tag saving flow
} elseif ( is_tag() ) {
	$the_tag = new Tag( $flow->item_id );

	// If tag deletion is confirmed, delete the current tag
	if ( 'true' === $flow->confirmed_delete ) {

		$the_tag->delete();

	// Otherwise, proceed with updating the tag
	} else {
		// Set the tag's new name
		$the_tag->new_name = $flow->tag_name;

		// Save the tag with it's new name
		$the_tag->save();

	}

	// Output workflow configuration
	$flow->output_config( 'save_tag' );

// If the selected item was neither a GIF or a tag, abandon all hope
} else {
	// Output workflow error configuration
	$flow->output_config( 'error' );
}
