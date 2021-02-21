<?php
/**
 * The GIFs with tag script
 *
 * Powers the GIFs with tag script filter (CMD modifier on GIF search)
 *
 * @since 2.0
 */

require_once ( 'functions.php' );

// Initialize query input details.
$input = $argv[1];
$tag = getenv('selected_tag');

// Query the database
$list_gifs = new GIF_Query( $input, $tag );

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
				'item_type' => 'gif',
			),
		);
	}

	// Encode our array as JSON for Alfred's output
	echo json_encode( $items );
}