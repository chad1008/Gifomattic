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

// Just for fun, update the workflow's icon
update_icon();

// Set the query input to the ID passed in by Alfred
$id = $argv[1];

// If the item selected was a tag, query the database for GIFs based on the tag ID
if ( is_tag() ) {
	$gifs = new GIF_Query( '', $id );

	// Reassign $id with the GIF id of a random selection from GIFs ths tag is assigned to
	$id = $gifs->random();

	// Define the count to be increased as 'random_count'
	$count = 'random_count';
} elseif ( is_gif() ) {
	// If the item selected was a GIF, define the count to be incremented as 'selected_count'
	$count = 'selected_count';
}

// Pull the desired GIF out of the database
$the_gif = new GIF( $id );

// Increment the appropriate counter
$the_gif->increment_count( $count );

// Output the GIF's URL
echo $the_gif->url;