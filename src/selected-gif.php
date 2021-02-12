<?php
/**
 * The GIF output script
 *
 * Powers the GIF output script node.
 * Outputs the URL of a GIF selected by the user, and updates the selected_count value
 *
 * @since 2.0
 */

require_once ( 'functions.php' );

// Set the query input to Alfred user input, and the query type to the variable provided by Alfred
$input = $argv[1];
$type = getenv('query_type');

$query = new GIF_Query( $input, $type );

// If the query is for a GIF, increment the selected_count and echo the URL
if ( $query->query_type == 'gif_by_id' ) {
	$query->increment_count( 'selected_count' );

	echo $query->gifs[0]['url'];

// Or, if the query if for a tag, select a random GIF from the GIFs array
} elseif ( $query->query_type == 'tag_by_id' ) {
	$random_gif = array_rand( $query->gifs );

	//Increment the random_count of the randomly selected GIF and echo the URL
	$query->increment_count( 'random_count', $query->gifs[$random_gif]['gif_id'] );

	echo $query->gifs[$random_gif]['url'];
}