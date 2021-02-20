<?php
/**
 * The GIF output script
 *
 * Powers the GIF output script node.
 * 
 * Outputs the URL of the GIF selected by the user, or if a tag was selected, pulls a random GIF that tag is assigned to.
 * Updates the 'selected_count' or 'random_count' value for the GIF used.
 *
 * @since 2.0
 */

require_once ( 'functions.php' );

// Set the query input to the ID passed in by Alfred, and the query type to the variable provided by Alfred
$id = $argv[1];
$item_type = getenv('item_type');

// If the item selected was a tag, run a 'gifs_with_tag' GIF_Query. The Query will pull the 'selected_tag' env var automatically
if ( $item_type == 'tag' ) {
	$gifs = new GIF_Query( '', 'gifs_with_tag', $id );

	// Reassign $id with the GIF id of a random selection from GIFs ths tag is assigned to
	$id = $gifs->random();
}

// Pull the desired GIF out of the database
$gif = new GIF( $id );

// Increment the appropriate count
if ( $item_type == 'gif' ) {
	$gif->increment_count( 'selected_count');
} elseif ( $item_type == 'tag' ) {
	$gif->increment_count( 'random_count' );
}

echo $gif->url;