<?php
/**
 * The Add GIF script
 *
 * Powers the GIF Add script filter and its friends
 */

require_once ( 'functions.php' );

$input = $argv[1];
$tags	 = new Tag_Query( $input );

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
		$flow->edit_gif_url( $input );

	// If this is the gif_name step, output the New GIF Name prompt
	} elseif ( 'gif_name' === $flow->next_step ) {
		$flow->edit_gif_name( $input ); 

	// If this is the manage_tags step, output the tag management interface
	} elseif ( 'manage_tags' === $flow->next_step ) {
		// If the current GIF has no tags, skip ahead to the adding tags interface
		if ( empty( $the_gif->tags ) ) {
			$flow->add_tags( $input, $the_gif, $tags );
		} else {
			$flow->launch_tag_management();
		}

	// If this is the add_tags step, launch the tag addition interface
	} elseif ( 'add_tags' === $flow->next_step ) {
		$flow->add_tags( $input, $the_gif, $tags );
		
	// If this is the remove_tags step, launch the tag removal interface
	} elseif ( 'remove_tags' === $flow->next_step ) {
		$flow->remove_tags( $the_gif );
		
	// If an unexpected step is detected, display an error
	} else {
		$flow->error();
	}

// If the selected item was a tag, enter the tag editing flow
} elseif ( is_tag () ) {
	$the_tag = new Tag ( $flow->item_id );

	// If the next step is 'confirm_delete' display a confirmation prompts
	if ( 'confirm_delete' === $flow->next_step ) {
	$flow->confirm_tag_delete( $the_tag );

	// If this isn't the 'confirm_delete' step, proceed with the editing prompts
	} else {
	$flow->edit_tag( $the_tag, $input );

	}
	
// If the selected item was neither a GIF or a tag, abandon all hope
} else {
	$flow->error();

}

// Output the list of items
$flow->output_items();