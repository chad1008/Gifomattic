<?php
/**
 * The Add GIF script
 *
 * Powers the GIF Add script filter and its friends
 */

require_once ( 'functions.php' );

$input = $argv[1];
$item_type = getenv( 'item_type' );
$id = ( getenv( 'item_id' ) );
$gif = new GIF( $id );

// Initialize items array for Alfred output
$items = array(
	'items' => array(),
);

// If this is the first step, start with the GIF URL prompt. Populate the new_gif_url env variable
if ( !getenv( 'next_step' ) ) {
	$items['items'][] = array(
		'title' => 'New GIF URL:',
		'subtitle' => $input !=null ? $input : 'Enter the new GIF URL',
		'arg' => $input,
		'variables' => array(
			'gif_url' => $input,
			'next_step' => 'gif_name',
			'gif_add_subtitle' => "Step 2: Enter the new GIF's name"
		),
	);

	// While on the first step, if this is an existing GIF ('item_type' env var will be 'gif') provide an option to keep the current url
	if ( $item_type == 'gif') {
		$items['items'][] = array(
			'title' => "Keep the GIF's current URL",
			'subtitle' => $gif->url,
			'arg' => $gif->url,
			'variables' => array(
				'gif_url' => $gif->url,
				'next_step' => 'gif_name',
			),
		);
	}
// If this is the gif_name step, output the New GIF Name prompt. Populate the new_gif_name env variable
} elseif ( getenv( 'next_step' ) == 'gif_name' ) {
		$items['items'][] = array(
		'title' => 'New GIF name:',
		'subtitle' => $input !=null ? $input : 'Enter the new GIF name',
		'arg' => $input,
		'variables' => array(
			'gif_name' => $input,
			'next_step' => 'save_gif',
		),
	);

	// While on the second step, if this is an existing GIF ('item_type" env var will be 'gif') provide an option to keep the current name
	if ( $item_type == 'gif') {
		$items['items'][] = array(
			'title' => "Keep the GIF's current name",
			'subtitle' => $gif->name,
			'arg' => $gif->name,
			'variables' => array(
				'gif_name' => $gif->name,
				'next_step' => 'save_gif',
			),
		);
	}
}
// Encode our array as JSON for Alfred's output
echo json_encode( $items );
