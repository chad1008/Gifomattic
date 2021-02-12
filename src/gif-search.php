<?php
/**
 * The GIF search script
 *
 * Powers the GIF script filter, and returns both gifs and tags
 *
 * @since 2.0
 */

require_once ( 'functions.php' );

// Initiate a new query
$input = $argv[1];
$query = new GIF_Query( $input );

//The GIF loop!
// Imitation is the sincerest form of flattery
if ( $query->have_gifs() ) {

	echo '<?xml version="1.0"?>';
	echo '<items>';
	
		while ( $query->have_gifs() ) {
			$query->the_gif();
		}

	echo '</items>';
}