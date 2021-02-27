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
 * Determine if the database requires an update
 *
 * @raram bool   $error   Sets error state for output. Defaults to FALSE
 * @since 2.0
 *
 * @return bool
 */

function is_legacy_db() {
	$file = $_SERVER['alfred_workflow_data'] . '/gifomattic.db';
	$db = new sqlite3( $file );
	$query = 'PRAGMA table_info( gifs )';
	$result = $db->query( $query );
	$columns = array();
	$legacy = array(
		'id',
		'url',
		'name',
		'tags',
		'selectedcount',
		'randomcount',
		'date',
	);

	// Build an array of current database's column names, then compare to that the legacy structure
	while ($column = $result->fetchArray()) {
		$columns[] = $column['name'];
	}
	$columns_count = count($columns);
	$legacy_match = count(array_intersect_assoc($legacy, $columns));

	if ($legacy_match == 7 && $columns_count == 7) {
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
	// While we're here, we should set up the icons folders
	global $icons;
	$icons = $_SERVER['alfred_workflow_data'] . '/icons/';
	$folders = array(
		$icons,
		$icons . 'view/',
		$icons . 'edit/',
	);
	foreach( $folders as $folder ) {
		if (!file_exists( $folder ) )
			mkdir( $folder, 0777, true );
	}

	// Check if a database exists. If not, set up a workflow folder to put the database in.
	if ( !file_exists( $file ) ) {
		mkdir( $_SERVER['alfred_workflow_data'] );
	}
		// Connect to the database
		$db = new sqlite3( $file );

		// Create the database and tables as needed
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
	imagejpeg( $thumbnail, $icons . $gif['id'] . ".jpg" );

	// Create an image resource to scale from the cropped jpeg
	$new_jpeg = imagecreatefromjpeg( "$icons" . $gif['id'] . ".jpg") ;

	//Scale the new image to 128px, respecting aspect ratio
	$scaled_jpeg = imagescale( $new_jpeg, 128, -1, IMG_BICUBIC_FIXED );

	// Save the scaled image as a jpeg
	imagejpeg( $scaled_jpeg, $icons . $gif['id'] . ".jpg", 10 );

	// Create the view/edit flagged icon variants
	$flags = imagecreatefrompng('img/flags.png');

	// Generate "View" icon
	imagecopymerge($scaled_jpeg, $flags, 64, 64, 0, 0, 64, 64, 100);
	imagejpeg($scaled_jpeg, $icons . "view/" . $gif['id'] . ".jpg", 10);

	// Generate "Edit" icon
	imagecopymerge($scaled_jpeg, $flags, 64, 64, 0, 64, 64, 64, 100);
	imagejpeg($scaled_jpeg, $icons . "edit/" . $gif['id'] . ".jpg", 10);

	imagedestroy($new_jpeg);
	imagedestroy($scaled_jpeg);
	imagedestroy($flags);
}

/**
 * Prepare a dynamic string to convey the number of GIFs available for a given request
 *
 * @param array $args      Defines the parameters of the statement to be prepared
 * 		 int    [number]   Defines the value to be checked against
 * 		 mixed    [zero]   A string or array of strings to be used when the value is zero
 * 		 mixed     [one]   A string or array of strings to be used when the value is one
 * 		 mixed    [many]   A string or array of strings to be used when the value is more than one
 * 		 string [format]   The message that strings should be inserted into
 *
 * @since 2.0
 *
 * @return string
 */

function gif_quantity( array $args ) {
	// Determine what kind of statement is needed
	if ( $args['number'] == 0 ) {
		$case = $args['zero'];
	} elseif ( $args['number'] == 1 ) {
		$case = $args['one'];
	} else {
		$case = $args['many'];
	}

	// Assign the provided string or array values to an array of values for the end statement
	$values = array();
	if ( is_array( $case ) ) {
		foreach ( $case as $value ) {
			$values[] = $value;
		}
	} else {
		$values[] = $case;
	}

	// Initialize the statement
	$format = $args['format'];

	return vsprintf( $format, $values );
//	return $values;
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
