<?php
/**
 * The GIFs with tag script
 *
 * Powers the GIFs with tag script filter (CMD modifier on GIF search)
 *
 * @since 2.0
 */

require_once ( 'functions.php' );

// Initialize user input and query type
$input = $argv[1];
$type = 'gifs_with_tag';

// Query the database (note: 'gifs_with_tag' relies upon the 'tag_to_list' env var within the GIF_Query class
$list_gifs = new GIF_Query( $input, $type );

if ($list_gifs->have_gifs()) {

	// Create the basis of the multidimensional Items array Alfred looks for
	$items = array(
		'items' => array(),
	);

	// Add any GIFs returned by the current query to the array
	while ( $list_gifs->have_gifs() ) {
		$the_gif = $list_gifs->the_gif();

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