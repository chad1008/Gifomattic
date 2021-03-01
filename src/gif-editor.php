<?php
/**
 * The Add GIF script
 *
 * Powers the GIF Add script filter and its friends
 */

require_once ( 'functions.php' );

$input = $argv[1];
$id = ( getenv( 'item_id' ) );
$next_step = ( getenv( 'next_step' ) );
$gif = new GIF( $id );

// Initialize items array for Alfred output
$items = array(
	'items' => array(),
);

// If this is the gif_url step, start with the GIF URL prompt
if ( $next_step == 'gif_url' ) {
	// Define subtitle based on validation of user input
	if ( $input == '' ) {
		$subtitle = 'Enter the new GIF URL';
	} elseif ( is_valid_url( $input ) ) {
		$subtitle = $input;
	} else {
		$subtitle = 'Please enter a valid URL';
	}

	$items['items'][] = array(
		'title' 	=> 'New GIF URL:',
		'subtitle'  => $subtitle,
		'arg' 		=> 'filler to trigger notifications',
		'valid'		=> is_valid_url( $input ) ? 'true' : 'false',
		'icon'  => array(
			'path' => 'img/edit.png',
		),
		'variables' => array(
			'gif_url'   => $input,
			'next_step' => 'gif_name',
			'standby_1' => 'Saving your GIF',
			'standby_2' => 'This should only take a moment, please stand by',
			'gif_saved' => popup_notice( "GIF saved: $gif->name" )
		),
	);

	// While on the first step, if this is an existing GIF, provide an option to keep the current url
	if ( is_gif() ) {
		$items['items'][] = array(
			'title'		=> "Keep the GIF's current URL",
			'subtitle'  => $gif->url,
			'arg'		=> '',
			'icon'		=> array(
				'path'  => 'img/checkmark.png'
			),
			'variables' => array(
				'gif_url'	=> '',
				'next_step' => 'gif_name',
			),
		);
	}
// If this is the gif_name step, output the New GIF Name prompt
} elseif ( $next_step == 'gif_name' ) {
		$items['items'][] = array(
		'title'		=> 'New GIF name:',
		'subtitle'	=> $input !=null ? $input : 'Enter the new GIF name',
		'arg' 		=> 'filler to trigger notifications',
		'valid'		=> $input == '' ? 'false' : 'true',
		'icon'  => array(
			'path' => 'img/edit.png',
		),
		'variables' => array(
			'gif_name'	=> $input,
			'next_step' => 'save_gif',
			'gif_saved' => popup_notice( "GIF saved: $input" )
		),
	);

	// While on the gif_name step, if this is an existing GIF provide an option to keep the current name
	if ( is_gif() ) {
		$items['items'][] = array(
			'title'		=> "Keep the GIF's current name",
			'subtitle'	=> $gif->name,
			'arg' 		=> 'filler to trigger notifications',
			'icon'		=> array(
				'path'  => 'img/checkmark.png'
			),
			'variables' => array(
				'gif_name'	=> '',
				'next_step' => 'save_gif',
			),
		);
	}
}
// Encode our array as JSON for Alfred's output
echo json_encode( $items );
