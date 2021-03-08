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
 * Validates the URL entered by the user
 *
 * @param string $url The URL entered by the user
 * 
 * @since 2.0
 * 
 * @return bool
 */
function is_valid_url ( $url ) {
	if( preg_match( '/^(https?:\/\/).*\..*\.(gif|jpe?g|png)$/i', $url ) ) {
		return TRUE;
	} else {
		return FALSE;
	}
}


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
		date	TEXT NOT NULL,
		in_trash INTEGER NOT NULL DEFAULT 0 CHECK ( in_trash IN ( 0,1 ) ),
		trash_date INTEGER
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
 * Automatically remove an GIFs that have been in the trash for >30 days
 *
 * @since 2.0
 */
function trash_cleanup() {
	
	$gifs = new GIF_Query( '','',TRUE,TRUE );
	
	if ( $gifs->have_gifs() ) {
		while ( $gifs->have_gifs() ) {
			
			$gif = $gifs->the_gif();
			
			$gif->delete();
		}
	}
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
 * Generate/Update various flagged version of a GIF icon
 *
 * @param string $id The ID of the GIF whose icon needs to be flagged
 *
 * @since 2.0
 */
function flag_icon( $id ) {
	// Prepare the flag sprite
	$flags = imagecreatefrompng( 'img/flags.png' );

	// Prepare the icon and save path
	global $icons;
	$icon = imagecreatefromjpeg( $icons . $id . '.jpg');

	// Generate "View" icon
	imagecopymerge( $icon, $flags, 64, 64, 0, 0, 64, 64, 100 );
	imagejpeg( $icon, $icons . "view/" . $id . ".jpg", 10 ) ;

	// Generate "Edit" icon
	imagecopymerge( $icon, $flags, 64, 64, 0, 64, 64, 64, 100 );
	imagejpeg( $icon, $icons . "edit/" . $id . ".jpg", 10 );

	// Release images from memory
	imagedestroy( $flags );
	imagedestroy ( $icon );
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
			"It's GIF. Not JIF.",
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
			"FAIL!",
			"Womp Womp",
			"Ummmmm.... no.",
			"You must be new here",
			"Oops. Something went wrong.",
			"I sense a disturbance in that gif",
			"Are you sure you know what you're doing?",
			"I don't know what any of those words mean",
			"Need more info. Or maybe less info. I don't know, something's borked.",
		);
	}

	$rand = $messages[array_rand( $messages )];
	return $rand . "\r\n" . $message;
}

/**
 * Randomze the workflow icon
 *
 * Resets the workflow icon to a randomly selected color variant whenever the workflow is activated
 *
 * @since 2.0
 *
 * @return string
 */
function update_icon() {
	
	// List possible source file numbers
	for ( $i = 1; $i<=12; ++$i ) {
		$logos[] = $i;
	}

	// Select a random file number
	$number = array_rand( $logos );
	
	// Grab the sourece file using the randomly generated number
	$source = 'img/logos/logo' . $logos[$number] . '.png';
	
	// Set destination path and filename
	$destination = 'icon.png';
	
	// Copy the source file over the destination file
	copy( $source, $destination );
}
