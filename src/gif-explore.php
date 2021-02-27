<?php
/**
 * The GIFs with tag script
 *
 * Powers the GIFs with tag script filter (CMD modifier on GIF search)
 *
 * @since 2.0
 */

require_once ( 'functions.php' );

// Initialize query input details.
$input = $argv[1];
$id = getenv( 'item_id' );

// Initialize items array for Alfred output
$items = array(
	'items' => array(),
);

if ( is_tag() ) {
	// Query the database
	$list_gifs = new GIF_Query($input, $id);

	if ($list_gifs->have_gifs()) {

		// Add any GIFs returned by the current query to the results array
		while ($list_gifs->have_gifs()) {
			$the_gif = $list_gifs->the_gif();

			$items['items'][] = array(
				'title'	   => $the_gif->name,
				'subtitle' => $the_gif->url,
				'arg'	   => $the_gif->id,
				'icon'	   => array(
					'path' => $the_gif->icon,
				),
				'variables' => array(
					'item_type' => 'gif',
					'item_id'   => $the_gif->id,
				),
				'mods'		=> array(
					'cmd'	=> array(
						'subtitle' => "View this GIF's details and stats",
						'icon'	    => array(
							'path'  => $the_gif->view_icon,
						),
					),
					'shift'	=> array(
						'subtitle' => "Edit this GIF",
						'icon'	    => array(
							'path'  => $the_gif->edit_icon,
						),
					),
				),
			);
		}
	}
} elseif (  is_gif() ) {
	// Query the requested GIF
	$gif = new GIF( $id );

	// Set up the icon for the GIF's total count
	$total = $gif->selected_count + $gif->random_count;
	if ( $total == 0 ) {
		$total_icon = 'img/sad.png';
	} elseif ( $total == 1 ) {
		$total_icon = 'img/thinking.png';
	} else {
		$total_icon = 'img/nailed it.png';
	}

	// Update the reusable Preview icon file
	$gif->generate_preview_icon();

	// Add the GIF's details to the output array
	$items['items'] = array(
		// Name and URL
		array(
			'title'    => $gif->name,
			'subtitle' => 'Share this GIF (CMD to preview in browser)',
			'arg'	   => $gif->id,
			'valid'    => 'true',
			'icon'	   => array(
				'path' => $gif->icon,
			),
			'variables' => array(
				'item_type' => 'gif',
				'item_id'   => $gif->id,
			),
			'mods'		=> array(
				'cmd'	=> array(
					'subtitle' => "Preview this GIF in your browser",
					'arg'	   => $gif->url,
					'variables' => array(
						'item_type' => 'gif_preview',
					),
					'icon'	    => array(
						'path'  => 'img/preview.jpg',
					),
				),
				'shift'	=> array(
					'subtitle' => "Edit this GIF",
					'icon'	    => array(
						'path'  => $gif->edit_icon,
					),
					'variables' => array(
						'next_step' => 'gif_url',
						'item_id'	=> $gif->id,
						'item_type' => 'gif'
					),
				),
			),
		),
		// Selected count
		array(
			'title'    => $gif->selected_count_statement,
			'subtitle' => '',
			'valid'    => 'false',
			'icon'	   => array(
				'path' => 'img/checkmark.png',
			),
		),
		// Random count
		array(
			'title'    => $gif->random_count_statement,
			'subtitle' => '(when choosing randomly from one of the tags assigned to this GIF)',
			'valid'    => 'false',
			'icon'	   => array(
				'path' => 'img/random.png',
			),
			'mods'		=> array(
				'cmd'	=> array(
					'valid'		=> 'false',
					'subtitle' => '(when choosing randomly from one of the tags assigned to this GIF)',
				),
				'shift'	=> array(
					'valid'		=> 'false',
					'subtitle' => '(when choosing randomly from one of the tags assigned to this GIF)',
				),
			),
		),
		// Total count
		array(
			'title'    => $gif->total_count_statement['title'],
			'subtitle' => $gif->total_count_statement['subtitle'],
			'valid'    => 'false',
			'icon'	   => array(
				'path' => $total_icon,
			),
			'mods'		=> array(
				'cmd'	=> array(
					'valid'		=> 'false',
					'subtitle' => $gif->total_count_statement['subtitle'],
				),
				'shift'	=> array(
					'valid'		=> 'false',
					'subtitle' => $gif->total_count_statement['subtitle'],
				),
			),
		),
		// Date (conditional values for bug reporting if the date is missing)
		array(
			'title'    => $gif->date == '' ? "The date for this GIF appears to be missing!" : "This GIF was saved on $gif->date",
			'subtitle' => $gif->date == '' ? "If you saved this GIF recently, please open an issue on Github! Thanks!" : '',
			'valid'    => $gif->date == '' ? 'true' : 'false',
			'icon'	   => array(
				'path' => 'img/calendar.png',
			),
			'mods'		=> array(
				'cmd'	=> array(
					'valid'		=> 'false',
					'subtitle' => $gif->date == '' ? "If you saved this GIF recently, please open an issue on Github! Thanks!" : '',
				),
				'shift'	=> array(
					'valid'		=> 'false',
					'subtitle' => $gif->date == '' ? "If you saved this GIF recently, please open an issue on Github! Thanks!" : '',
				),
			),
		),
	);

	// Add the GIF's tags to the output array, display a message if there are none
	if ( empty( $gif->tags ) ) {
		$items['items'][] = array(
			'title'    => 'This GIF has no tags',
			'subtitle' => "It's sad, lonely, and probably difficult for you to find",
			'valid'    => 'false',
			'icon'     => array(
				'path' => 'img/sad.png',
			),
			'mods'		=> array(
				'cmd'	=> array(
					'valid'		=> 'false',
					'subtitle' => "It's sad, lonely, and probably difficult for you to find",
				),
				'shift'	=> array(
					'valid'		=> 'false',
					'subtitle' => "It's sad, lonely, and probably difficult for you to find",
				),
			),
		);
	} else {
		foreach ( $gif->tags as $tag ) {

			// Set up subtitle statement based on the existing GIF count on the current tag
			$args = array(
				'number' => $tag->gifs_with_tag,
				'one'	 => 'This is the only GIF',
				'many'   => "View all $tag->gifs_with_tag GIFs",
				'format' => '%s with this tag',
			);
			$subtitle = gif_quantity( $args );

			// Prep an array item for Alfred output
			$items['items'][] = array(
				'title' => "Tagged as: $tag->name",
				'subtitle' => $subtitle,
				'arg'   => $tag->id,
				'valid' => $tag->gifs_with_tag == 1 ? 'false' : 'true',
				'icon'  => array(
					'path' => $tag->gifs_with_tag == 1 ? 'img/tag.png' : 'img/view tag.png',
				),
				'variables' => array(
					'item_type' => 'tag',
					'item_id'   => $tag->id,
				),
				'mods'		=> array(
					'cmd'	=> array(
						'valid'		=> 'false',
						'subtitle' => $subtitle,
						'icon'	    => array(
							'path' => $tag->gifs_with_tag == 1 ? 'img/tag.png' : 'img/view tag.png',
						),
					),
					'shift'	=> array(
						'valid'		=> 'false',
						'subtitle' => $subtitle,
						'icon'	    => array(
							'path' => $tag->gifs_with_tag == 1 ? 'img/tag.png' : 'img/view tag.png',
						),
					),
				),
			);
		}
	}
}

// Encode our array as JSON for Alfred's output
echo json_encode( $items );
