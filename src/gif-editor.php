<?php
/**
 * The Add GIF script
 *
 * Powers the GIF Add script filter and its friends
 */

require_once ( 'functions.php' );

$input = $argv[1];

// Initialize the Workflow object
$flow = new Workflow();

// If the selected item was a GIF, enter the GIF editing flow
if ( is_gif() ) {
	$the_gif = new GIF( $flow->item_id );
	
	// If this is the initial editing step, display prompts to either edit or trash the GIF
	if ( 'launch_editor' === $flow->next_step ) {
		$flow->launch_editor( $the_gif );

	// If this is the gif_url step, start with the GIF URL prompts
	} elseif ( 'gif_url' === $flow->next_step ) {
		$flow->edit_gif_url( $the_gif, $input );

	// If this is the gif_name step, output the New GIF Name prompt
	} elseif ( 'gif_name' === $flow->next_step ) {
		$flow->edit_gif_name( $the_gif, $input );
	}

// If the selected item was a tag, enter the tag editing flow
} elseif ( is_tag () ) {
	$tag = new Tag ( $flow->item_id );

	// If the next step is 'confirm_delete' display a confirmation prompts
	if ( 'confirm_delete' === $flow->next_step ) {

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
				'notification_text'		   => popup_notice( "Tag deleted: $tag->name" ),
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
				'notification_text' => popup_notice( "Tag updated: $input" ),
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

// Fix unused modifier keys
$items = fix_mods( $flow->items );

// Encode the items array as JSON for Alfred's output
echo json_encode( $items );
