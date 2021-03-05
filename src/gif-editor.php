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

// Initialize items array for Alfred output
$items = array(
	'items' => array(),
);

// If the selected item was a GIF, enter the GIF editing flow
if ( is_gif() ) {

	$gif = new GIF( $id );
	
	// If this is the initial editing step, display prompts to either edit or trash the GIF
	if ( $next_step == 'launch_editor' ) {
		$items['items'][] = array(
			'title' => 'Edit "' . $gif->name . '"',
			'subtitle' => "Modify the URL, name, or the tags assigned to this GIF",
			'arg' => 'filler to trigger notifications',
			'icon' => array(
				'path' => 'img/edit.png'
			),
			'variables' => array(
				'next_step' => 'gif_url',
				'exit'		=> 'false',
			),
		);
		$items['items'][] = array(
			'title' => 'Trash "' . $gif->name . '"',
			'subtitle' => "Once trashed, the GIF will be deleted in 30 days",
			'arg' => 'filler to trigger notifications',
			'icon' => array(
				'path' => 'img/trash.png'
			),
			'variables' => array(
				'next_step' => 'save_gif',
				'trash_gif' => 'true',
				'exit'		=> 'false',
			),
		);

		// If this is the gif_url step, start with the GIF URL prompt
	} elseif ($next_step == 'gif_url') {
		// Define subtitle based on validation of user input
		if ($input == '') {
			$subtitle = 'Enter the new GIF URL';
		} elseif (is_valid_url($input)) {
			$subtitle = $input;
		} else {
			$subtitle = 'Please enter a valid URL';
		}

		$items['items'][] = array(
			'title' => 'New GIF URL:',
			'subtitle' => $subtitle,
			'arg' => 'filler to trigger notifications',
			'valid' => is_valid_url($input) ? 'true' : 'false',
			'icon' => array(
				'path' => 'img/edit.png',
			),
			'variables' => array(
				'gif_url' => $input,
				'next_step' => 'gif_name',
				'standby_1' => 'Saving your GIF',
				'standby_2' => 'This should only take a moment, please stand by',
				'gif_saved' => popup_notice("GIF saved: $gif->name"),
				'exit' => 'false',
			),
		);

		// While on the first step, if this is an existing GIF, provide an option to keep the current url
		if ( is_gif() ) {
			$items['items'][] = array(
				'title' => "Keep the GIF's current URL",
				'subtitle' => $gif->url,
				'arg' => '',
				'icon' => array(
					'path' => 'img/checkmark.png'
				),
				'variables' => array(
					'gif_url' => '',
					'next_step' => 'gif_name',
					'exit' => 'false',
				),
			);
		}
		// If this is the gif_name step, output the New GIF Name prompt
	} elseif ( $next_step == 'gif_name' ) {
		$items['items'][] = array(
			'title' => 'New GIF name:',
			'subtitle' => $input != null ? $input : 'Enter the new GIF name',
			'arg' => 'filler to trigger notifications',
			'valid' => $input == '' ? 'false' : 'true',
			'icon' => array(
				'path' => 'img/edit.png',
			),
			'variables' => array(
				'gif_name' => $input,
				'next_step' => 'save_gif',
				'gif_saved' => popup_notice("GIF saved: $input"),
				'exit' => 'false',
			),
		);

		// While on the gif_name step, if this is an existing GIF provide an option to keep the current name
		if ( is_gif() ) {
			$items['items'][] = array(
				'title' => "Keep the GIF's current name",
				'subtitle' => $gif->name,
				'arg' => 'filler to trigger notifications',
				'icon' => array(
					'path' => 'img/checkmark.png'
				),
				'variables' => array(
					'gif_name'  => '',
					'next_step' => 'save_gif',
					'exit'		=> 'false',
				),
			);
		}
	}

// If the selected item was a tag, enter the tag editing flow
} elseif ( is_tag () ) {

	$tag = new Tag ($id );

	// If the next step is 'confirm_delete' display a confirmation prompts
	if ( getenv( 'next_step' ) == 'confirm_delete' ) {

		// First the option to confirm the deletion
		$items['items'][] = array(
			'title'	   => 'Yes, I\'m sure I want to delete the "' . $tag->name . '" tag',
			'subtitle' => "This cannot be undone!",
			'icon'	   => array(
				'path' => 'img/destroy.png',
			),
			'variables' => array(
				'next_step'		   => 'save_gif',
				'confirmed_delete' =>'true',
				'gif_saved'		   => popup_notice( "Tag deleted: $tag->name" ),
			),
		);

		// Next show the option to cancel the deletion
		$items['items'][] = array(
			'title'	   => 'No, wait! I don\'t want to delete the "' . $tag->name . '" tag!',
			'subtitle' => "Go back to tag editing",
			'icon'	   => array(
				'path' => 'img/thinking.png',
			),
			'variables' => array(
				'next_step'		   => '',
				'confirmed_delete' =>'',
			),
		);

		// If this isn't the 'confirm_delete' step, proceed with the editing prompts
	} else {
		// Display a prompt to update the tag's name
		$items['items'][] = array(
			'title'    => "New tag name: $input",
			'subtitle' => "Current name: $tag->name",
			'arg'	   => $input,
			'icon' 	   => array(
				'path' => 'img/edit.png'
			),
			'variables' => array(
				'tag_name'  => $input,
				'next_step' => 'save_gif',
				'gif_saved' => popup_notice( "Tag updated: $input" ),
			),
		);

		// Display an option to delete the tag
		$items['items'][] = array(
			'title'    => 'Delete the "' . $tag->name . '" tag',
			'subtitle' => '(No GIFs will be deleted)',
			'icon' 	   => array(
				'path' => 'img/destroy.png',
			),
			'variables' => array(
				'next_step' => 'confirm_delete'
			),
		);
	}
// If the selected item was neither a GIF or a tag, abandon all hope
} else {
	$items['items'][] = array(
		'title' 	=> "Sorry, something went wrong",
		'subtitle'  => 'I have no idea what happened, but please do try again',
		'valid'		=> 'false',
	);
}

// Encode our array as JSON for Alfred's output
echo json_encode( $items );
