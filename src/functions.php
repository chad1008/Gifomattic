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
 * Prepare a success/failure message
 *
 * @param string $message A customized message to be output
 *
 * @since 2.0
 */

function popup_notice( $message='' ) {
	//define success outputs for random selection
	$wins = array(
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
	
	$rand = $wins[array_rand( $wins )];
	echo $rand . "\r\n" . $message;
}