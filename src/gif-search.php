<?php
/**
 * The GIF search script
 *
 * Powers the GIF script filter, and returns both gifs and tags
 *
 * @since 2.0
 */

require_once ( 'functions.php' );

// Initialize items array for Alfred output
$items = array(
	'items' => array(),
);

if ( is_legacy_db() ) {
	$items['items'][] = array(
		'title'     => 'Gifomattic update required: database and icon files',
		'subtitle'  => 'Press RETURN to update now, or ESC to exit',
		'arg'		=> 'filler arg',
		'icon'		=> array(
			'path'  => 'img/update.png',
		),
		'variables' => array(
			'next_step' => 'update',
		),
	);
	echo json_encode( $items );
	die;
}

// Initiate a new query
$input = $argv[1];
$gifs = new GIF_Query( $input );
$tags = new Tag_Query( $input );

//The Gifomattic loop!
// Imitation is the sincerest form of flattery...
if ( $gifs->have_gifs() || $tags->have_tags() ) {

	// Add any tags returned by the current query to the array
	while ( $tags->have_tags() ) {
		$the_tag = $tags->the_tag();

		// Prepare a quantity statement for the subtitle
		$args = array(
			'number' => $the_tag->gifs_with_tag,
			'zero'   => 'No GIFs',
			'one'    => 'One GIF',
			'many'   => $the_tag->gifs_with_tag . ' GIFs',
			'format' => 'Share a randomly selected "' . $the_tag->name . '" GIF (%s available)',
		);
		$subtitle = gif_quantity( $args );

		$items['items'][] = array(
			'title'     => $the_tag->name,
			'subtitle'  => $subtitle,
			'arg'	    => $the_tag->id,
			'valid'		=> $the_tag->gifs_with_tag > 0 ? 'true' : 'false',
			'icon'	    => array(
				'path'  => 'img/randomize.png',
			),
			'variables' => array(
				'item_type' => 'tag',
				'item_id'   => $the_tag->id,
				'next_step' => 'output',
			),
			'mods'		=> array(
				'cmd'	=> array(
					'subtitle' => 'View GIFs with this tag',
					'valid'		=> $the_tag->gifs_with_tag > 0 ? 'true' : 'false',
					'icon'	    => array(
						'path'  => 'img/view tag.png',
					),
				),
				'shift'	=> array(
					'subtitle' => 'Edit this tag',
					'icon'	    => array(
						'path'  => 'img/edit tag.png',
					),
					'variables' => array(
						'item_type' => 'tag',
						'item_id'   => $the_tag->id,
					),
				),
			),
		);
	}

	// Add any GIFs returned by the current query to the array
	while ( $gifs->have_gifs() ) {
		$the_gif = $gifs->the_gif();

		$items['items'][] = array(
			'title'		=> $the_gif->name,
			'subtitle'  => $the_gif->url,
			'arg'	    => $the_gif->id,
			'icon'		=> array(
				'path'  => $the_gif->icon,
			),
			'variables' => array(
				'item_type' => 'gif',
				'item_id'	=> $the_gif->id,
				'next_step' => 'output',
			),
			'mods'		=> array(
				'cmd'	=> array(
					'subtitle' => "View this GIF's details and stats",
					'icon'	    => array(
						'path'  => $the_gif->view_icon,
					),
				),
				'shift' => array(
					'subtitle' => 'Edit this GIF',
					'icon'	    => array(
						'path'  => $the_gif->edit_icon,
					),
					'variables' => array(
						'next_step' => 'gif_url',
						'item_id'	=> $the_gif->id,
						'item_type' => 'gif'
					),
				),
			),
		);
	}
}

// Display an option to add a new GIF
// Define subtitle based on validation of user input
if ( is_valid_url( $input ) ) {
	$subtitle = "Save GIF URL: $input";
} else {
	$subtitle = 'Enter the new GIF URL';
}

// If $input is a valid URL, save it and move to gif_name. Otherwise ignore $input and move to gif_url
$items['items'][] = array(
	'title' => 'Add a new GIF to your library',
	'subtitle' => $subtitle,
	'arg' => $input,
	'icon' => array(
		'path' => 'img/add.png'
	),
	'variables' => array(
		'gif_url'   => is_valid_url( $input ) ? $input : '',
		'next_step' => is_valid_url( $input ) ? 'gif_name' : 'gif_url',
		'standby_1' => 'Saving your GIF',
		'standby_2' => 'This should only take a moment, please stand by',
	),
);

// Encode our array as JSON for Alfred's output
echo json_encode( $items );
