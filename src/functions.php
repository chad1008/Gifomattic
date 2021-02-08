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
spl_autoload_register('class_autoloader');

/**
 * Checks for and if needed creates the Gifomattic database
 *
 * @since 2.0
 */
function prep_db() {
	$file = $_SERVER['alfred_workflow_data'] . '/gifomattic.db';
	
	// Check if a database exists. If not, set up a workflow folder to put the database in.
	if ( !file_exists( $file ) ) {
		mkdir($_SERVER['alfred_workflow_data']);
	}
	
	// Create the database and tables
	$db = new sqlite3( $file );
	$create_gifs_table = 'CREATE TABLE IF NOT EXISTS gifs (
		gif_id	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
		url	TEXT NOT NULL,
		name	TEXT NOT NULL,
		selectedcount	INTEGER NOT NULL DEFAULT 0,
		randomcount	INTEGER NOT NULL DEFAULT 0,
		date	TEXT NOT NULL
		)';
	$create_tags_table = 'CREATE TABLE IF NOT EXISTS tags (
		tag_id	INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
		tag	TEXT NOT NULL UNIQUE
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
function bind_values ( $stmt, $args ) {
	foreach( $args as $k => $v ) {
		$stmt->bindValue( $k, $v );
	}
}

/////////////////////
//FOR DEV USE ONLY///
/////////////////////
function clean_slate() {
	$file = $_SERVER['alfred_workflow_data'] . '/gifomattic.db';

	$db = new sqlite3( $file );
	$delete_gifs = 'DELETE FROM gifs';
	$delete_tags = 'DELETE FROM tags';
	$delete_tag_relationships = 'DELETE FROM tag_relationships';
	
	$db->exec( $delete_gifs );
	$db->exec( $delete_tags );
	$db->exec( $delete_tag_relationships );

	$db->close();
	unset($db);
}