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
$gifs = new GIF_Query( $input );
$tags = new Tag_Query( $input );

//The Gifomattic loop!
// Imitation is the sincerest form of flattery...
if ( $gifs->have_gifs() || $tags->have_tags() ) {

	// Create the basis of the multidimensional Items array Alfred looks for
	$items = array(
		'items' => array(),
	);

	// Add any tags returned by the current query to the array
	while ( $tags->have_tags() ) {
		$the_tag = $tags->the_tag();

		$items['items'][] = array(
			'title' => $the_tag->name,
			'subtitle' => 'Insert a randomly selected ' . $the_tag->tag . ' GIF (' . $the_tag->gifs_with_tag . ' available)',
			'arg' => $the_tag->id,
			'icon' => array(
				'path' => '',
			),
			'variables' => array(
				'item_type' => 'tag',
				'item_id'   => $the_tag->id,
			),
		);
	}

	// Add any GIFs returned by the current query to the array
	while ( $gifs->have_gifs() ) {
		$the_gif = $gifs->the_gif();

		$items['items'][] = array(
			'title'		=> $the_gif->name,
			'subtitle'  => $the_gif->url,
			'arg'	    => $the_gif->id,
			'icon'		=> array(
				'path'  => $the_gif->icon,
			),
			'variables' => array(
				'item_type'    => 'gif',
				'item_id'   => $the_gif->id,
			),
		);

	}

	// Encode our array as JSON for Alfred's output
	echo json_encode( $items );
}
