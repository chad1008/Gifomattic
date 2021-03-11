<?php
/**
 * The Save GIF script
 * Powers submission of the tags (new or existing) to be assigned to an individual GIF
 */

require_once( 'functions.php' );

/**
 * Script vars
 *
 * @var $mode string Passed from script filter. Used to determine if tags are to be added or removed
 * @var $is_new_tag bool Passed from script filter. Establishes if a new tag should be created, or if an existing tag is being assigned
 * @var $gif_id int Passed from script filter. Contains the ID of the GIF tags should be added to
 * @var $input mixed Passed from script filter. Either the name of the tag being created, or ID of a tag selected for assignment/removal
 * @var $selected_tag string Passed from script filter. Name of the existing tag that was selected for this GIF
 * @var $db object The Gifomattic database connection
 */

$mode		  = getenv( 'next_step' );
$is_new_tag	  = getenv( 'is_new_tag' );
$gif_id		  = getenv( 'item_id' );
$input		  = $argv[1];
$selected_tag = getenv( 'selected_tag' );
$db			  = prep_db();

// If this is a new tag, insert the user-input name into the database and use the new ID
if ( $mode == 'add_tags' ) {
	if ( $is_new_tag == 'true' ) {
		$stmt = $db->prepare( "INSERT INTO tags ( tag ) VALUES ( :tag )" );
		$stmt->bindValue( ':tag', $input );
		$stmt->execute();

		// Grab the ID of the new tag
		$tag_id = $db->lastInsertRowID();

		// Save the user's input as the name of the tag for later use
		$tag_name = $input;

	} else {
		// If this isn't a new tag, the ID needed will be passed from the workflow argument
		$tag_id = $input;

		// If this isn't a new tag, the recently selected tag name should be used instead of user input
		$tag_name = $selected_tag;
	}

	// Update the tag_relationships table to assign the chosen tag to the GIF
	$stmt = $db->prepare( "INSERT INTO tag_relationships ( tag_id,gif_id ) VALUES ( :tag_id,:gif_id )" );
	$args = array(
		':tag_id' => $tag_id,
		':gif_id' => $gif_id,
	);
	bind_values( $stmt, $args );
	$stmt->execute();

	// Set the mode for the next step
	$mode = 'add_tags';

	// Prepare success message
	$success = 'GIF tagged as "' . $selected_tag . '"';
	$notification = popup_notice( $success );

} elseif ( $mode == 'remove_tags' ) {
	// Prepare DELETE statement to remove record from the tag relationships table
	$stmt = $db->prepare( "DELETE FROM tag_relationships WHERE tag_id IS :tag_id AND gif_id IS :gif_id" );
	$args = array(
		':tag_id' => $input,
		':gif_id' => $gif_id,
	);
	bind_values( $stmt, $args );
	$stmt->execute();

	// Prepare success message
	$success = '"' . $selected_tag . '" removed from this GIF';
	$notification = popup_notice( $success );

} else {
	// In case of emergency, break glass
	$error = 'Error: GIF could not be updated...';
	$notification = popup_notice( $error, TRUE );
}

// Set up the next step
$output = array (
	'alfredworkflow' => array(
		'arg'		 => '',
		'variables'	 => array(
			'item_id'   => $gif_id,
			'next_step' => $mode,
			'notification'  => $notification
		),
	),
);

echo json_encode( $output );
