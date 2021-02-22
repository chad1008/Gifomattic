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
$type = getenv( 'item_type' );
$id = getenv( 'item_id' );

// Initialize items array for Alfred output
$items = array(
	'items' => array(),
);

if ( $type == 'tag' ) {
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
						'subtitle' => "View this GIF's details and stats"
					),
					'shift'	=> array(
						'subtitle' => "Edit this GIF"
					),
				),
			);
		}
	}
} elseif (  $type == 'gif' ) {
	// Query the requested GIF
	$gif = new GIF( $id );

	// Set up the icon for th GIF's total count
	$total = $gif->selected_count + $gif->random_count;
	if ( $total == 0 ) {
		$total_icon = 'sad icon.png';
	} elseif ( $total == 1 ) {
		$total_icon = 'thinking icon.png';
	} else {
		$total_icon = 'nailed it icon.png';
	}


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
				),
				'shift'	=> array(
					'subtitle' => "Edit this GIF"
				),
			),
		),
		// Selected count
		array(
			'title'    => $gif->selected_count_statement,
			'subtitle' => '',
			'valid'    => 'false',
			'icon'	   => array(
				'path' => 'checkmark icon.png',
			),
		),
		// Random count
		array(
			'title'    => $gif->random_count_statement,
			'subtitle' => '(when choosing randomly from one of the tags assigned to this GIF)',
			'valid'    => 'false',
			'icon'	   => array(
				'path' => 'random icon.png',
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
		),
		// Date (conditional values for bug reporting if the date is missing)
		array(
			'title'    => $gif->date == '' ? "The date for this GIF appears to be missing!" : "This GIF was saved on $gif->date",
			'subtitle' => $gif->date == '' ? "If you saved this GIF recently, please open an issue on Github! Thanks!" : '',
			'valid'    => $gif->date == '' ? 'true' : 'false',
			'icon'	   => array(
				'path' => 'calendar icon.png',
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
				'path' => 'sad icon.png',
			),
		);
	} else {
		foreach ( $gif->tags as $tag ) {
			$items['items'][] = array(
				'title' => 'Tagged as: ' . $tag['name'],
				'subtitle'  => 'Share a randomly selected ' . $tag['name'] . ' GIF',
				'arg'   => $tag['id'],
				'icon'  => array(
					'path' => 'tag icon.png',
				),
				'variables' => array(
					'item_type' => 'tag',
					'item_id'   => $tag['id']
				),
				'mods'		=> array(
					'cmd'	=> array(
						'subtitle' => "View GIFs with this tag"
					),
				),
			);
		}
	}
}

// Encode our array as JSON for Alfred's output
echo json_encode( $items );
