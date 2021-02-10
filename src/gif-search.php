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
$query = new GIF_Query();

//The GIF Loop!
// Imitation is the sincerest form of flattery
if ( $query->have_gifs() || $query->have_tags() ) {

	echo '<?xml version="1.0"?>';
	echo '<items>';

	while ( $query->have_tags() ) {
		$query->the_tag();
	}

	while ( $query->have_gifs() ) {
		$query->the_gif();
	}

	echo '</items>';
}