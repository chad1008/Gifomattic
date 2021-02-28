<?php
require_once ('functions.php');

// Confirm that the database exists and exit if it doesn't
$file = $_SERVER['alfred_workflow_data'] . '/gifomattic.db';
if ( !file_exists( $file ) ) {
	die( "The database could not be found. Aborting update." );
}

// Ensure the icon folder structure is in place
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

if ( is_legacy_db() ) {
	// Connect to the database
	$file = $_SERVER['alfred_workflow_data'] . '/gifomattic.db';
	$db = new sqlite3($file);

	// Rename existing table to make way for new database format
	$rename = 'ALTER TABLE gifs RENAME TO legacydb';
	$result = $db->exec( $rename );

	// Now that the old table is moved, we set up the new ones
	$db = prep_db();

	// Gather data from legacy table
	$select_legacy = 'SELECT * FROM legacydb';
	$legacy_data = $db->query( $select_legacy );

	while ( $row = $legacy_data->fetchArray( SQLITE3_ASSOC ) ) {
		// Prep statement for the gifs table, and execute
		$gifs_insert = $db->prepare( 'INSERT INTO gifs VALUES ( :gif_id, :url, :name, :selected_count, :random_count, :date )' );
		$query_values = array(
							':gif_id' 		  => $row['id'],
							':url'			  => $row['url'],
							':name' 		  => $row['name'],
							':selected_count' => $row['selectedcount'],
							':random_count'   => $row['randomcount'],
							':date' 		  => $row['date'],
						);
		bind_values( $gifs_insert, $query_values );
		$gifs_insert->execute();

		// Create flagged icon variants
		flag_icon( $row['id'] );

		// Prep statement for the tags table
		$tags_insert = $db->prepare( 'INSERT OR IGNORE INTO tags ( tag ) VALUES ( :tag )' );
		$tag_relationships_insert = $db->prepare ( 'INSERT INTO tag_relationships VALUES ( :tag_id, :gif_id )' );

		// Separate the tag strings into individual tags
		$tags = $row['tags'];
		$split_tags = explode( ',', $tags );

		foreach ( $split_tags as $tag ) {
			if ($tag !== '' ) {
				// Insert individual tags into the tags table
				$tags_insert->bindValue( ':tag', $tag );
				$tags_insert->execute();

				// Retrieve the new tag ID
				$get_tag_id = $db->prepare( 'SELECT tag_id FROM tags WHERE tag IS :tag' );
				$get_tag_id->bindValue( ':tag', $tag );
				$result = $get_tag_id->execute();
				$tag_id = $result->fetchArray( SQLITE3_ASSOC )['tag_id'];

				// Prepare statements for the tag_relationships table and insert as tag_id->gif_id pairs
				$query_values = array(
					':tag_id' => $tag_id,
					':gif_id' => $row['id'],
				);
				bind_values( $tag_relationships_insert, $query_values );
				$tag_relationships_insert->execute();

			}
		}
	}

	echo 'Update complete!';

} else {
	die ( "Database format does not match previous version. Skipping update." );
}
$db->close();
unset($db);
