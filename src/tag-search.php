<?php
/**
 * The GIF search script
 *
 * Powers the gif script filter
 *
 * @since 2.0
 */
require_once ( 'functions.php' );

// Initiate a new query
$input = $argv[1];
$query = new GIF_Query( $input );

//The tag loop!
if ( $query->have_tags() ) {

/*	echo '<?xml version="1.0"?>';
	echo '<items>';*/

	$items = array(
		'items' => array(),
	);

	while ( $query->have_tags() ) {
		$items['items'][] = $query->the_tag();
	}
	echo json_encode( $items );

/*	echo '</items>';*/
}