<?php
/**
 * The GIFs with tag script
 *
 * Powers the GIFs with tag script filter (CMD modifier on GIF search)
 *
 * @since 2.0
 */

require_once ( 'functions.php' );

$input = $argv[1];

if ( $input == '' ) {

	$tag = getenv( 'tag_to_list' );
	$type = 'tag_by_id';

	$list_gifs = new GIF_Query( $tag, $type );

	if ($list_gifs->have_gifs()) {

		// Create the basis of the multidimensional Items array Alfred looks for
		$items = array(
			'items' => array(),
		);

		// Add any GIFs returned by the current query to the array
		while ($list_gifs->have_gifs()) {
			$items['items'][] = $list_gifs->the_gif();
		}

		// Encode our array as JSON for Alfred's output
		echo json_encode( $items );
	}

} else {

	$type = 'gifs_with_tag';
	$query = new GIF_Query ( $input, $type );


	if ($query->have_gifs() ) {

		// Create the basis of the multidimensional Items array Alfred looks for
		$items = array(
			'items' => array(),
		);

		// Add any GIFs returned by the current query to the array
		while ($query->have_gifs()) {
			$items['items'][] = $query->the_gif();
		}

		// Encode our array as JSON for Alfred's output
		echo json_encode($items);
	}
}