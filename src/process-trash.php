<?php
/**
 * The trash processing script
 */

require_once( 'functions.php' );

// Determine the next step based on user actions
$next_step = getenv( 'next_step' );
$id		   = getenv( 'item_id' );
$gif	   = $id != false ? new GIF( $id) : '';

// If the next step is to restore an individual GIF
if ( $next_step == 'restore_gif' ) {
	
	$gif->restore();

	// Prepare notification message
	$subtitle = '"' . $gif->name . '" has been has been returned to your library';

	// Prepare script output
	$output = array(
		'alfredworkflow' => array(
			'variables' => array(
				'notification_title' => 'GIF restored!',
				'notification_text'  => popup_notice( $subtitle ),
			),
		),
	);

// If the next step is to empty the trash
} elseif ( $next_step == 'empty_trash' ) {

	// Query all currently trashed GIFs
	$trash = new GIF_Query( '','',TRUE );

	// Loop through the results deleting each one along the way
	if ( $trash->have_gifs() ) {
		while ($trash->have_gifs()) {

			$gif = $trash->the_gif();

			$gif->delete();
		}
	}

	// Prepare notification message
	$args = array(
		'number' => $trash->gif_count,
		'zero'	 => 'No GIFs',
		'one'	 => 'One GIF',
		'many'	 =>	"$trash->gif_count GIFS",
		'format' => '%s permanently deleted',
	);
	$subtitle = gif_quantity( $args );

	// Prepare script output
	$output = array(
		'alfredworkflow' => array(
			'variables' => array(
				'notification_title' => 'Trash emptied!',
				'notification_text'  => popup_notice( $subtitle ),
			),
		),
	);
// Or, if the next step is to delete an individual GIF
} elseif ( $next_step == 'delete_gif' ) {
	
	// Delete the GIF
	$gif->delete();

	// Prepare notification message
	$subtitle = '"' . $gif->name . '" has been permanently removed from your library';

	// Prepare script output
	$output = array(
		'alfredworkflow' => array(
			'variables' => array(
				'notification_title' => 'GIF deleted!',
				'notification_text'  => popup_notice( $subtitle ),
			),
		),
	);
// Or if this is an unexpected step
} else {
	$output = array(
		'alfredworkflow' => array(
			'variables' => array(
				'notification_title' => "Sorry, something went wrong",
				'notification_text'  => popup_notice( "Please try again!", TRUE ),
			),
		),
	);

}

echo json_encode( $output );