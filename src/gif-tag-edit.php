<?php
/**
 * The Edit GIF Tags script
 *
 * Powers the tag step of adding and editing GIFs
 */

require_once( 'functions.php' );

// Initialize all the data
$input	 = $argv[1];
$flow	 = new Workflow();
$the_gif = new GIF( $flow->item_id );
$tags	 = new Tag_Query( $input );

// Initialize items array for Alfred output
$items = array(
	'items' => array(),
);

// Determine the next step is 'add_tags' or if the current GIF has no tags assigned, initiate tag addition flow
if ( 'add_tags' === $flow->next_step || empty ( $the_gif->tags ) ) {
	$flow->add_tags( $input, $the_gif, $tags );

} elseif ( $flow->next_step == 'remove_tags' ) {
	$flow->remove_tags( $the_gif );

} else {
	$flow->launch_tag_management();

}

// Output the list of items
$flow->output_items();
