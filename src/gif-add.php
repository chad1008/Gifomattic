<?php
/**
 * The Add GIF script
 *
 * Powers the GIF Add script filter and its friends
 */

require_once ( 'functions.php' );

$input= $argv[1];

// If this is the first step, start with the GIF URL prompt. Populate the new_gif_url env variable
if ( !getenv( 'next_step' ) ) {
	$items = array(
		'items' => array(
			array(
				'title' => 'New GIF URL:',
				'subtitle' => $input,
				'arg' => $input,
				'variables' => array(
					'new_gif_url' => $input,
					'next_step' => 'gif_name',
					'gif_add_subtitle' => "Step 2: Enter the new GIF's name"
				),
			),
		),
	);
	// Encode our array as JSON for Alfred's output
	echo json_encode($items);
// If this is the gif_name step, output the New GIF Name prompt. Populate the new_gif_name env variable
} elseif ( getenv( 'next_step' ) == 'gif_name' ) {
	$items = array(
		'items' => array(
			array(
				'title' => 'New GIF name:',
				'subtitle' => $input,
				'arg' => $input,
				'variables' => array(
					'new_gif_name' => $input,
					'next_step' => 'save_gif',
				),
			),
		),
	);
	// Encode our array as JSON for Alfred's output
	echo json_encode($items);

}