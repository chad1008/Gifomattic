<?php
/**
 *
 * The trash management script
 */

require_once ( 'functions.php' );

$trash = new GIF_Query( '', '' , TRUE );
$next_step = getenv( 'next_step' );

// Initialize items array for Alfred output
$items = array(
	'items' => array(),
);

// If this is the launch_trash step, show prompts to either view or empty the trash
if ( $next_step == 'launch_trash') {
	$items['items'][] = array(
		'title' => "View all trashed GIFs",
		'subtitle' => 'Restore or delete individual GIFs',
		'icon' => array(
			'path' => 'img/trash.png',
		),
		'variables' => array(
			'next_step' => 'view_trash'
		),
	);
	$items['items'][] = array(
		'title' => "Empty trash",
		'subtitle' => 'Permanently delete ALL trashed GIFs. CAUTION: there is no undo!',
		'icon' => array(
			'path' => 'img/destroy.png',
		),
		'variables' => array(
			'next_step' => 'empty_trash'
		),
	);

// If this is the view_trash step, add any GIFs returned by the current query to the array
} elseif( $next_step == 'view_trash' )  {
	while ( $trash->have_gifs() ) {
		$gif = $trash->the_gif();

		$items['items'][] = array(
			'title' => "Trashed: $gif->name",
			'subtitle' => 'Restore this GIF',
			'arg' => $gif->id,
			'icon' => array(
				'path' => $gif->icon,
			),
			'variables' => array(
				'item_type' => 'gif',
				'item_id' => $gif->id,
				'next_step' => 'restore_gif',
			),
			'mods' => array(
				'ctrl' => array(
					'subtitle' => 'Delete forever. CAUTION: there is no undo!',
					'icon' => array(
						'path' => 'img/destroy.png',
					),
					'variables' => array(
						'item_id'   => $gif->id,
						'next_step' => 'delete_gif',
					),
				),
			),
		);
	}
}

echo json_encode( $items );
