<?php
/**
 * The Edit GIF Tags script
 *
 * Powers the tag step of adding and editing GIFs
 */

require_once( 'functions.php' );

$input = $argv[1];
$gif_id =  getenv( 'item_id' );
$gif = new GIF( $gif_id );
$tags = new Tag_Query( $input );
$mode = getenv( 'tag_edit_mode' );

// Initialize items array for Alfred output
$items = array(
	'items' => array(),
);

// Determine if tags should be added or removed
if ( $mode == 'add_tags' || empty ( $gif->tags ) ) {
	// Display prompts to add a tag or go back to mode selection
	if ( $input == '' ) {
		$items['items'][] = array(
			'title'    => 'Add a tag',
			'subtitle' => 'Begin typing to select an existing tag, or create a new one',
			'arg'      => '',
			'valid'    => false,
			'icon'  => array(
				'path' => 'add tag.png',
			),
		);
		$items['items'][] = array(
			'title' => 'Go back',
			'subtitle' => 'Choose between adding or editing tags',
			'arg'   => '',
			'icon'  => array(
				'path' => '',
			),
			'variables' => array(
				'tag_edit_mode'	=> '',
			),
		);
	} else {
		// Set up user input as a new tag to save, unless it matches an existing tag
		if ( !in_array( $input, array_column( $tags->tags, 'tag') ) )  {
			$items['items'][] = array(
				'title' 	=> "Create a new tag: $input",
				'arg'   	=> $input,
				'variables'	=> array(
					'is_new_tag' 	=> 'true',
					'tag_edit_mode' => 'add_tags',
					'selected_tag'	=> $input,
				),
			);
		}
		// Loop through and display existing tags that match the user input
		while ( $tags->have_tags() ) {
			$the_tag = $tags->the_tag();

			// Set up subtitle statement based on the existing GIF count on the current tag
			if ($the_tag->gifs_with_tag == 0) {
				$q = "No GIFs";
				$u = "use";
			} elseif ($the_tag->gifs_with_tag == 1) {
				$q = "One other GIF";
				$u = "uses";
			} else {
				$q = "$the_tag->gifs_with_tag other GIFs";
				$u = "use";
			}
			$subtitle = 'Tag this GIF as "' . $the_tag->name . '" (%s currently %s this tag)';

			// Prep an array item for Alfred output
			//	Disable any tags that are already assigned to this GIF and show a subtitle to that effect
			$items['items'][] = array(
				'title' => $the_tag->name,
				'subtitle' => $gif->has_tag( $the_tag->id ) ? 'This GIF is already tagged as "' . $the_tag->name . '"' : sprintf($subtitle, $q, $u),
				'arg' => $the_tag->id,
				'icon' => array(
					'path' => '',
				),
				'variables' => array(
					'is_new_tag' => false,
					'tag_edit_mode' => 'add_tags',
					'selected_tag'	=> $the_tag->name,
				),
				'valid' => $gif->has_tag( $the_tag->id ) ? 'false' : 'true',
			);
		}
	}
} elseif ( $mode == 'remove_tags' ) {
	// Add each of the GIF's tags to the items array
	foreach ( $gif->tags as $tag ) {
		$items['items'][] = array(
			'title' => 'Remove "' . $tag->name . '" from this GIF',
			'subtitle' => $tag->gifs_with_tag - 1 . " other GIFs share this tag",
			'arg'   => $tag->id,
			'icon'  => array(
				'path' => 'remove tag.png',
			),
			'variables' => array(
				'selected_tag'	=> $tag->name,
			),
		);
	}
	// Display option to go back one level
	$items['items'][] = array(
		'title' => 'Go back',
		'subtitle' => 'Choose between adding or editing tags',
		'arg'   => '',
		'icon'  => array(
			'path' => '',
		),
		'variables' => array(
			'tag_edit_mode'	=> '',
		),
	);
} else {
	// Display prompt to add tags
	$items['items'][] = array(
		'title' => 'Add tags to this GIF',
		'subtitle' => 'You can assign existing tags, or create new ones',
		'arg'   => '',
		'icon'  => array(
			'path' => 'add tag.png',
		),
		'variables' => array(
			'tag_edit_mode'	=> 'add_tags',
		),
	);
	// Display prompt to remove tags
	$items['items'][] = array(
		'title' => 'Remove tags from this GIF',
		'subtitle' => 'You can always add them again later!',
		'arg'   => '',
		'icon'  => array(
			'path' => 'remove tag.png',
		),
		'variables' => array(
			'tag_edit_mode'	=> 'remove_tags',
		),
	);
	// Display prompt to exit the GIF editor
	$items['items'][] = array(
		'title'     => 'Exit',
		'subtitle'  => 'Close the GIF editor',
		'arg'	    => '',
		'variables' => array(
			'exit'  => 'true',
		),
	);
}

echo json_encode( $items );
