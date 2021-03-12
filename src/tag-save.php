<?php
/**
 * The Save GIF script
 * Powers submission of the tags (new or existing) to be assigned to an individual GIF
 */

require_once( 'functions.php' );

// Initialize all the data
$flow		  = new Workflow();
$input		  = $argv[1];
$db			  = prep_db();

// If this is a new tag, insert the user-input name into the database and use the new ID
if ( $flow->next_step == 'add_tags' ) {
	if ( $flow->is_new_tag == 'true' ) {
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
		$tag_name = $flow->selected_tag;
	}

	// Update the tag_relationships table to assign the chosen tag to the GIF
	$stmt = $db->prepare( "INSERT INTO tag_relationships ( tag_id,gif_id ) VALUES ( :tag_id,:gif_id )" );
	$args = array(
		':tag_id' => $tag_id,
		':gif_id' => $flow->item_id,
	);
	bind_values( $stmt, $args );
	$stmt->execute();

	// Output workflow configuration
	$flow->output_config( 'add_tag' );


} elseif ( $flow->next_step == 'remove_tags' ) {
	// Prepare DELETE statement to remove record from the tag relationships table
	$stmt = $db->prepare( "DELETE FROM tag_relationships WHERE tag_id IS :tag_id AND gif_id IS :gif_id" );
	$args = array(
		':tag_id' => $input,
		':gif_id' => $flow->item_id,
	);
	bind_values( $stmt, $args );
	$stmt->execute();

	// Output workflow configuration
	$flow->output_config( 'remove_tag' );

} else {
	// Output workflow error configuration
	$flow->output_config( 'error' );

}

