<?php
/**
 * The Save GIF script
 *
 * Powers submission of the Add GIF and Edit GIF flows
 */

require_once( 'functions.php' );

// Gather details of the GIF to be added/updated. ID is optional, as edited GIFs will provide one, newly added GIFs will not
$gif = array(
	'id'   => !getenv( 'edit_gif_id' ) ? '' : getenv( 'edit_gif_id' ),
	'url'  => getenv( 'gif_url' ),
	'name' => getenv( 'gif_name' ),
	'date' => date( 'F d, Y' ),
);

// Connect to the database
$db = prep_db();

// The INSERT statement
$stmt = $db->prepare( "INSERT INTO gifs (url,name,date) VALUES (:url,:name,:date)" );
$args = array(
	':url'  => $gif['url'],
	':name' => $gif['name'],
	':date' => $gif['date'],
);
bind_values( $stmt, $args );

$result = $stmt->execute();

// Grab the ID of the new GIF and add it to our array of the GIFs data
$gif['id'] = $db->lastInsertRowID();

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

