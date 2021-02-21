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
$type = getenv( 'item_type' );
$id = getenv( 'item_id' );

// Create the basis of the multidimensional Items array Alfred looks for
$items = array(
	'items' => array(),
);

if ( $type == 'tag' ) {
	// Query the database
	$list_gifs = new GIF_Query($input, $id);

	if ($list_gifs->have_gifs()) {

		// Add any GIFs returned by the current query to the array
		while ($list_gifs->have_gifs()) {
			$the_gif = $list_gifs->the_gif();

			$items['items'][] = array(
				'title' => $the_gif->name,
				'subtitle' => $the_gif->url,
				'arg' => $the_gif->id,
				'icon' => array(
					'path' => $the_gif->icon,
				),
				'variables' => array(
					'item_type' => 'gif',
				),
			);
		}
	}
} elseif (  $type == 'gif' ) {
	// Query the requested GIF
	$gif = new GIF( $id );

	// Add the GIF's details to the output array
	$items['items'] = array(
		// Name and URL
		array(
			'title'    => $gif->name,
			'subtitle' => $gif->url,
			'valid'    => 'false',
			'icon'	   => array(
				'path' => $gif->icon,
			)
		),
		// Selected count
		array(
			'title'    => $gif->selected_count_statement,
			'subtitle' => '',
			'valid'    => 'false',
		),
		// Random count
		array(
			'title'    => $gif->random_count_statement,
			'subtitle' => '(when choosing randomly from one of the tags assigned to this GIF)',
			'valid'    => 'false',
		),
		// Total count
		array(
			'title'    => $gif->total_count_statement['title'],
			'subtitle' => $gif->total_count_statement['subtitle'],
			'valid'    => 'false',
		),
		// Date (conditional values for legacy users with GIFs saved before dates were tracked in the database)
		array(
			'title'    => $gif->date == '' ? "This GIF is so old, I don't even know when you saved it!" : "This GIF was saved on $gif->date",
			'subtitle' => $gif->date == '' ? "That means you've been using Gifomattic a LONG time. Thank you!" : '',
			'valid'    => 'false',
		),
	);

	// Add the GIF's tags to the output array, display a message if there are none
	if ( empty( $gif->tags ) ) {
		$items['items'][] = array(
			'title'    => 'This GIF has no tags',
			'subtitle' => "It's sad, lonely, and probably difficult for you to find",
			'valid'    => 'false',
			'icon'     => array(
				'path' => '',
			),
		);

	} else {
		foreach ( $gif->tags as $tag ) {
			$items['items'][] = array(
				'title' => 'Tagged as: ' . $tag['name'],
				'arg'   => $tag['id'],
				'icon'  => array(
					'path' => '',
				),
				'variables' => array(
					'item_type' => 'tag',
					'item_id'   => $tag['id']
				),
			);
		}
	}
}

// Encode our array as JSON for Alfred's output
echo json_encode( $items );
