<?php
/**
 * The trash processing script
 */

require_once( 'functions.php' );

// Initialize data
$flow = new Workflow();
$the_gif = $flow->item_id != false ? new GIF( $flow->item_id ) : '';
$trash = new GIF_Query( '','',true );

// If the next step is to restore an individual GIF
if ( 'restore_gif' === $flow->next_step ) {

	// Restore the GIF
	$the_gif->restore();

	// Output workflow configuration
	$flow->output_config( 'restore_gif', $the_gif, $trash );

// If the next step is to empty the trash
} elseif ( 'empty_trash'  === $flow->next_step ) {

	// Loop through the results deleting each one along the way
	if ( $trash->have_gifs() ) {
		while ( $trash->have_gifs() ) {

			$gif = $trash->the_gif();

			$gif->delete();
		}
	}

	// Output workflow configuration
	$flow->output_config( 'empty_trash', $trash );

// Or, if the next step is to delete an individual GIF
} elseif ( 'delete_gif' === $flow->next_step ) {
	
	// Delete the GIF
	$the_gif->delete();
	
	// Output workflow configuration
	$flow->output_config( 'delete_gif', $the_gif, $trash );

// Or, if this is an unexpected step
} else {
	// Output workflow error configuration
	$flow->output_config( 'error' );

}
