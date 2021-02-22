<?php
/**
 * The Save GIF script
 *
 * Powers submission of the Add GIF and Edit GIF flows
 */

require_once( 'functions.php' );

// Gather details of the GIF to be added/updated.
// ID is 'edit_gif_id' if it's been set by the editing flow, otherwise the most recently selected GIF's ID
$gif = array(
	'id'   => !getenv( 'edit_gif_id' ) ? getenv( 'item_id' ) : getenv( 'edit_gif_id' ),
	'url'  => getenv( 'gif_url' ),
	'name' => getenv( 'gif_name' ),
	'date' => date( 'F d, Y' ),
);

// Connect to the database
$db = prep_db();

// The INSERT statement
$insert_stmt = $db->prepare( "INSERT INTO gifs ( url,name,date ) VALUES ( :url,:name,:date )" );
$args = array(
	':url'  => $gif['url'],
	':name' => $gif['name'],
	':date' => $gif['date'],
);
bind_values( $insert_stmt, $args );

// The UPDATE statement
$update_stmt = $db->prepare( "UPDATE gifs SET url = :url, name = :name WHERE gif_id IS :id" );
$args = array(
	':url'  => $gif['url'],
	':name' => $gif['name'],
	':id'   => $gif['id'],
);
bind_values( $update_stmt, $args );

// If this is an existing GIF ('item_type" env var will be 'gif'), execute UPDATE
if ( getenv( 'item_type' ) == 'gif') {
	$result = $update_stmt->execute();
// Otherwise execute INSERT and grab the new ID
} else {
	$result = $insert_stmt->execute();
	$gif['id'] = $db->lastInsertRowID();
}

// Create/update the icon for the GIF that was just added/updated
iconify( $gif );

$success = "GIF saved: " . $gif['name'];
$notification = popup_notice( $success );

$output = array (
	'alfredworkflow' => array(
		'arg' => '',
		'variables' => array(
			'edit_gif_id'   => $gif['id'],
			'notification'  => $notification,
			'tag_edit_mode' => 'add_tags',
		),
	),
);

echo json_encode( $output );
