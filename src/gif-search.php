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

//The Gifomattic loop!
// Imitation is the sincerest form of flattery...
if ( $query->have_gifs() || $query->have_tags() ) {

	// Create the basis of the multidimensional Items array Alfred looks for
	$items = array(
		'items' => array(),
	);

	// Add any tags returned by the current query to the array
	while ( $query->have_tags() ) {
		$items['items'][] = $query->the_tag();
	}

	// Add any GIFs returned by the current query to the array
	while ( $query->have_gifs() ) {
		$the_gif = $query->the_gif();

		$items['items'][] = array(
			'title'		=> $the_gif->name,
			'subtitle'  => $the_gif->url,
			'arg'	    => $the_gif->id,
			'icon'		=> array(
				'path'  => $the_gif->icon,
			),
			'variables' => array(
				'query_type' => 'gif_by_id',
			),
		);

	}

	// Encode our array as JSON for Alfred's output
	echo json_encode( $items );
}