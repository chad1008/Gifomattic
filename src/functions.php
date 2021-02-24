<?php
/**
 * Autoloads classes when called
 * 
 * First sanitizes the class name to conform with file name convention
 * 
 * @param string $class The class being called
 *
 * @since 2.0
 */
function class_autoloader( $class ) {
		$class_slug = preg_replace( '/_/', '-', strtolower( $class ) );
		include_once 'classes/class-' . $class_slug . '.php';
}
spl_autoload_register( 'class_autoloader' );

/**
 * Checks if the currently selected item in Alfred is a GIF
 *
 * @since 2.0
 */
function is_gif() {
	$type = getenv( 'item_type' );
	if ( $type == 'gif' ) {
		return TRUE;
	} else {
		return FALSE;
	}
}

/**
 * Checks if the currently selected item in Alfred is a tag
 *
 * @since 2.0
 */
function is_tag() {
	$type = getenv( 'item_type' );
	if ( $type == 'tag' ) {
		return TRUE;
	} else {
		return FALSE;
	}
}

/**
 * Checks for and if needed creates the Gifomattic database
 *
 * @since 2.0
 */
function prep_db() { //TODO Remove testing conditional
	if ( isset( $_SERVER['alfred_workflow_data'] ) ) {
		$file = $_SERVER['alfred_workflow_data'] . '/gifomattic.db';
	} else {
		$file = 'gifomattic.db';
	}
	// While we're here, we should set an global icon folder
	global $icons;
	$icons = $_SERVER['alfred_workflow_data'] . '/icons/';

	// Check if a database exists. If not, set up a workflow folder to put the database in.
	if ( !file_exists( $file ) ) {
		mkdir( $_SERVER['alfred_workflow_data'] );
	}
		// Create the database and tables as needed
		$db = new sqlite3( $file );
		$create_gifs_table = 'CREATE TABLE IF NOT EXISTS gifs (
		gif_id	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
		url	TEXT NOT NULL,
		name	TEXT NOT NULL,
		selected_count	INTEGER NOT NULL DEFAULT 0,
		random_count	INTEGER NOT NULL DEFAULT 0,
		date	TEXT NOT NULL
		)';
		$create_tags_table = 'CREATE TABLE IF NOT EXISTS tags (
		tag_id	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
		tag	TEXT COLLATE NOCASE NOT NULL UNIQUE
		)';
		$create_tags_rel_table = 'CREATE TABLE IF NOT EXISTS tag_relationships (
		tag_id	INTEGER NOT NULL,
		gif_id	INTEGER NOT NULL,
		PRIMARY KEY ( tag_id, gif_id)
		)';

		$db->exec( $create_gifs_table );
		$db->exec( $create_tags_table );
		$db->exec( $create_tags_rel_table );

		return $db;
}

/**
 * Binds an array of values for a provided prepared sqlite statement
 * 
 * @param string $stmt Prepared SQL statement
 * @param array $args token => value pairs
 *
 * @since 2.0
 */
function bind_values( $stmt, $args ) {
	foreach( $args as $k => $v ) {
		$stmt->bindValue( $k, $v );
	}
}

/**
 * Generate/Update the icon file for a newly added or edited GIF
 *
 * @param array $gif Array of relevant details for the GIF in question (ID and URL, plus the name although that isn't used here)
 *
 * @since 2.0
 */

function iconify( $gif ) {
	// Create a new file from the url, read it's dimensions
	$original_gif = imagecreatefromgif( $gif['url'] );

	// Gather sizes to decide crop direction
	$original_gif_x = getimagesize( $gif['url'] )[0];
	$original_gif_y = getimagesize( $gif['url'] )[1];

	// Set a centered crop area: if landscape, center horizontally otherwise center vertically
	if ($original_gif_x > $original_gif_y) {
		$crop_x = ($original_gif_x - $original_gif_y) / 2;
		$crop_y = 0;
	} else {
		$crop_x = 0;
		$crop_y = ($original_gif_y - $original_gif_x) / 2;
	}

	// Determine which side is shorter to use as our crop value
	$crop_measure = min($original_gif_x, $original_gif_y);

	// Crop it
	$crop_vals = array(
		'x'		 => $crop_x,
		'y'		 => $crop_y,
		'width'  => $crop_measure,
		'height' => $crop_measure,
		);
	$thumbnail = imagecrop($original_gif, $crop_vals);

	// Save a new cropped thumbnail file
	global $icons;
	imagejpeg( $thumbnail, "$icons" . $gif['id'] . ".jpg" );


	// Create an image resource to scale from the cropped jpeg
	$new_jpeg = imagecreatefromjpeg( "$icons" . $gif['id'] . ".jpg") ;

	//Scale the new image to 128px, respecting aspect ratio
	$scaled_jpeg = imagescale( $new_jpeg, 128, -1, IMG_BICUBIC_FIXED );

	// Save the scaled image as a jpeg
	imagejpeg( $scaled_jpeg, "$icons" . $gif['id'] . ".jpg", 10 );

//destroy temporary and original images
	imagedestroy($new_jpeg);
	imagedestroy($scaled_jpeg);
}

/**
 * Prepare a success/failure message
 *
 * @param string $message String to be appended to the random success message
 * @raram bool   $error   Sets error state for output. Defaults to FALSE
 * @since 2.0
 * 
 * @return string
 */
function popup_notice( $message = '', $error = FALSE ) {
	// Conditionally define outputs for random selection
	if ( !$error ) {
		$messages = array(
			"Boom!",
			"Huzzah!",
			"Nailed It!",
			"You're my hero",
			"Beep Beep Boop...",
			"Mission accomplished!",
			"Oh, that's a good one!",
			"Your GIF is my command",
			"Is it hard to be so awesome?",
			"The GIF is strong with this one...",
			"I love it when a plan comes together",
			"With great GIF comes great responsibility",
		);
	} else {
		$messages = array(
			"Oops. Something went wrong.",
			"FAIL!",
			"Womp Womp",
			"Need more info. Or maybe less info. I don't know, something's borked.",
			"Are you sure you know what you're doing?",
			"You must be new here",
			"I don't know what any of those words mean",
			"Ummmmm.... no.",
			"I sense a disturbance in that gif",
		);
	}


	$rand = $messages[array_rand( $messages )];
	return $rand . "\r\n" . $message;
}

