<?php
/**
 * The workflow class.
 *
 * Gathers current workflow environment variables and prepares all workflow output
 *
 * @since 2.0
 */

class Workflow {
	/**
	 * Environment vars set in Alfred
	 *
	 * @since 2.0
	 *
	 * @var integer $item_id   The ID of the GIF or tag that's currently being worked on
	 * @var string  $next_step The next step the workflow should enter. Used for workflow nodes that get looped through multiple times
	 */
	public $item_id;
	public $next_step;
	public $new_gif;
	
	/**
	 * The items array for script filter output
	 *
	 * @since 2.0
	 *
	 * @var
	 */
	public $items;

	/**
	 * Constructor
	 *
	 * Checks and organizes environment variables and initialized output
	 *
	 * @since 2.0
	 */
	public function __construct() {
		$this->item_id	 = getenv( 'item_id' );
		$this->next_step = getenv( 'next_step' );
		$this->new_gif   = getenv( 'new_gif' );

		$this->items = array(
			'items' => array(),
		);

	}

	/**
	 * Show prompt to update database if required
	 *
	 * @since 2.0
	 */
	public function initiate_update() {
		// Build the list item
		$this->items['items'][] = array(
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
	}

	/**
	 * Show an individual tag in script filter results
	 *
	 * @param object $the_tag  The current Tag() object being displayed
	 * @param string $mode     Declares the format currently needed
	 */
	public function the_tag( $the_tag, $mode ) {

		// Format tag details for Search mode
		if ( 'search' === $mode) {
			// Prepare a quantity statement for the subtitle
			$args = array(
				'number' => $the_tag->gifs_with_tag,
				'zero'	 => 'No GIFs',
				'one'	 => 'One GIF',
				'many'	 => $the_tag->gifs_with_tag . ' GIFs',
				'format' => 'Share a randomly selected "' . $the_tag->name . '" GIF (%s available)',
			);
			$subtitle = gif_quantity( $args );

			// Build the list item
			$this->items['items'][] = array(
				'title'	   => $the_tag->name,
				'subtitle' => $subtitle,
				'arg'	   => $the_tag->id,
				'valid'	   => $the_tag->gifs_with_tag > 0 ? 'true' : 'false',
				'icon'	   => array(
					'path' => 'img/randomize.png',
				),
				'variables' => array(
					'item_type' => 'tag',
					'item_id'	=> $the_tag->id,
					'next_step' => 'output',
				),
				'mods' => array(
					'cmd' => array(
						'subtitle' => 'View GIFs with this tag',
						'valid'	   => $the_tag->gifs_with_tag > 0 ? 'true' : 'false',
						'icon'	   => array(
							'path' => 'img/view tag.png',
						),
					),
					'shift' => array(
						'subtitle' => 'Edit this tag',
						'icon'	   => array(
							'path' => 'img/edit tag.png',
						),
						'variables' => array(
							'item_type' => 'tag',
							'item_id'	=> $the_tag->id,
						),
					),
				),
			);
		// Format tag details for Explore mode
		} elseif ( 'explore' === $mode ) {

		}
	}

	/**
	 * Show an individual GIF in script filter results
	 *
	 * @param object $the_gif  The current GIF() object being displayed
	 */
	public function the_gif( $the_gif ) {
		// Build the list item
		$this->items['items'][] = array(
			'title'		=> $the_gif->name,
			'subtitle'  => 'Copy and share this GIF',
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
					'subtitle'  => "View this GIF's details and stats",
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
						'next_step' => 'launch_editor',
						'item_id'	=> $the_gif->id,
						'item_type' => 'gif'
					),
				),
			),
		);
	}

	/**
	 * Show a 'no results found' message
	 *
	 * @param string $mode Determines if this search was for GIFs, tags, or both. Defaults to 'both'
	 */
	public function no_results( $mode = 'both' ) {
		// Set item type
		if ( 'gifs' === $mode ) {
			$missing_items = 'GIFs';
		} elseif ( 'tags' === $mode ) {
			$missing_items = 'tags';
		} elseif ( 'both' === $mode ) {
			$missing_items = 'GIFs or tags';
		}

		// Build the list item
		$this->items['items'][] = array(
			'title'		=> "There are no $missing_items that match your search!",
			'subtitle'  => "If you've entered a URL you can save it as a new GIF",
			'valid'	    => 'false',
			'mods'		=> array(),
		);
	}

	/**
	 * Display option for saving a new GIF
	 * 
	 * @param string $input User input for URL validation and saving
	 */
	public function add_gif( $input ) {
		// Prepare subtitle based on validation of user input
		if ( is_valid_url( $input ) ) {
			$subtitle = "Save GIF URL: $input";
		} else {
			$subtitle = 'Enter a valid URL';
		}

		// Build the list item
		// If $input is a valid URL, save it and move to gif_name. Otherwise ignore $input and move to gif_url
		$this->items['items'][] = array(
			'title'    => 'Add a new GIF to your library',
			'subtitle' => $subtitle,
			'arg'	   => $input,
			'valid'    => is_valid_url( $input ) ? 'true' : 'false',
			'icon' 	   => array(
				'path' => 'img/add.png'
			),
			'mods' => array(),
			'variables' => array(
				'item_type' => 'gif',
				'new_gif'   => 'true',
				'gif_url'   => is_valid_url( $input ) ? $input : '',
				'next_step' => is_valid_url( $input ) ? 'gif_name' : 'gif_url',
				'standby_title' => 'Saving your GIF...',
				'standby_text'  => 'This should only take a moment, please stand by',
			),
		);
	}

	/** 
	 * Display option to view trashed GIFs
	 * 
	 * @param object $trash The GIF_Query() object containing all of the currently trashed GIFs
	 */
	public function view_trash( $trash ) {
		// Set up the subtitle
		$args = array(
			'number' => $trash->gif_count,
			'zero'   => array(
				'are',
				'no GIFs',
			),
			'one'    => array(
				'is',
				'one GIF',
			),
			'many'   => array(
				'are',
				$trash->gif_count . ' GIFs',
			),
			'format' => 'There %s currently %s in the trash',
		);
		$subtitle = gif_quantity( $args );

		// Build the trash prompt
		$this->items['items'][] = array(
			'title'	    => 'View and manage trashed GIFs',
			'subtitle'  => $subtitle,
			'arg'	    => 'filler arg',
			'valid'		=> $trash->gif_count == 0 ? 'false' : 'true',
			'icon'	    => array(
				'path'  => 'img/trash.png'
			),
			'match'		=> 'trash',
			'variables' => array(
				'next_step' => 'launch_trash'
			),
		);
	}

	/**
	 * Display the details for an individual GIF
	 *
	 * @param object $the_gif The GIF object currently being displayed
	 */
	public function display_gif_details( $the_gif ) {
		// Update the reusable Preview icon file
		$the_gif->generate_preview_icon();

		// Just for fun, update the workflow's icon
		update_icon();

		// Build GIF name list item
		$this->items['items'][] = array(
			'title'    => $the_gif->name,
			'subtitle' => 'Share this GIF (CMD to preview in browser)',
			'arg'	   => $the_gif->id,
			'valid'    => 'true',
			'icon'	   => array(
				'path' => $the_gif->icon,
			),
			'variables' => array(
				'item_type' => 'gif',
				'item_id'   => $the_gif->id,
			),
			'mods'		=> array(
				'cmd'	=> array(
					'subtitle'  => 'Preview this GIF in your browser',
					'arg'		=> $the_gif->url,
					'variables' => array(
						'next_step' => 'preview_gif',
					),
					'icon'	    => array(
						'path'  => 'img/preview.jpg',
					),
				),
				'shift'	=> array(
					'subtitle'  => "Edit this GIF",
					'icon'	    => array(
						'path'  => $the_gif->edit_icon,
					),
					'variables' => array(
						'next_step' => 'launch_editor',
					),
				),
			),
		);
		
		// Build selected_count list item
		$this->items['items'][] = array(
			'title' => $the_gif->selected_count_statement,
			'subtitle' => '',
			'valid' => 'false',
			'icon' => array(
				'path' => 'img/checkmark.png',
			),
		);

		// Build random count list item
		$this->items['items'][] = array(
			'title' => $the_gif->random_count_statement,
			'subtitle' => '',
			'valid' => 'false',
			'icon' => array(
				'path' => 'img/random.png',
			),
		);

		// Set up the icon for the GIF's total count
		$total = $the_gif->selected_count + $the_gif->random_count;
		if ( 0 === $total ) {
			$total_icon = 'img/sad.png';
		} elseif ( 1 === $total ) {
			$total_icon = 'img/thinking.png';
		} else {
			$total_icon = 'img/nailed it.png';
		}

		// Build total count list item
		$this->items['items'][] = array(
			'title' => $the_gif->total_count_statement['title'],
			'subtitle' => $the_gif->total_count_statement['subtitle'],
			'valid' => 'false',
			'icon' => array(
				'path' => $total_icon,
			),
		);

		// Build date list item (conditional values for bug reporting if the date is missing)
		$this->items['items'][] = array(
			'title'    => $the_gif->date == '' ? "The date for this GIF appears to be missing!" : "This GIF was saved on $the_gif->date",
			'subtitle' => $the_gif->date == '' ? "If you saved this GIF recently, please open an issue on Github! Thanks!" : '',
			'valid'    => 'false',
			'icon'	   => array(
				'path' => 'img/calendar.png',
			),
		);
	}

	/**
	 * Display the tags assigned to an individual GIF
	 *
	 * @param object $the_gif The GIF() object currently being displayed
	 */
	public function display_gif_tags( $the_gif ) {
		// Add the GIF's tags to the output array, display a message if there are none
		if ( empty( $the_gif->tags ) ) {
			$this->items['items'][] = array(
				'title'    => 'This GIF has no tags',
				'subtitle' => "It's sad, lonely, and probably difficult for you to find",
				'valid'    => 'false',
				'icon'     => array(
					'path' => 'img/sad.png',
				),
			);
		} else {
			foreach ( $the_gif->tags as $tag ) {
				// Set up subtitle statement based on the GIF count for the current tag
				$args = array(
					'number' => $tag->gifs_with_tag,
					'one'	 => 'This is the only GIF',
					'many'   => "View all $tag->gifs_with_tag GIFs",
					'format' => '%s with this tag',
				);
				$subtitle = gif_quantity( $args );

				// Build the list item
				$this->items['items'][] = array(
					'title'    => "Tagged as: $tag->name",
					'subtitle' => $subtitle,
					'arg'	   => $tag->id,
					'valid'	   => $tag->gifs_with_tag == 1 ? 'false' : 'true',
					'icon'     => array(
						'path' => $tag->gifs_with_tag == 1 ? 'img/tag.png' : 'img/view tag.png',
					),
					'variables' => array(
						'item_type' => 'tag',
						'item_id'   => $tag->id,
					),
				);
			}
		}
	}

	/**
	 * Display prompts to launch the GIF editor
	 *
	 * @param object $the_gif The GIF() object to be opened in the Editor
	 */
	public function launch_editor( $the_gif ) {
		// Build 'Edit GIF' list item
		$this->items['items'][] = array(
			'title' 	=> 'Edit "' . $the_gif->name . '"',
			'subtitle'  => "Modify the URL, name, or the tags assigned to this GIF",
			'arg'		=> 'filler arg',
			'icon'		=> array(
				'path'  => 'img/edit.png'
			),
			'variables' => array(
				'next_step' => 'gif_url',
				'exit'		=> 'false',
			),
		);

		// Build 'Trash GIF' list item
		$this->items['items'][] = array(
			'title'    => 'Trash "' . $the_gif->name . '"',
			'subtitle' => "Once trashed, the GIF will be deleted in 30 days",
			'arg' 	   => 'filler arg',
			'icon'	   => array(
				'path' => 'img/trash.png'
			),
			'variables' => array(
				'next_step' 		 => 'save_gif',
				'trash_gif'			 => 'true',
				'notification_title' => "GIF trashed!",
				'notification_text'  => '"' . $the_gif->name . '" will be permanently deleted in 30 days.',
				'exit'				 => 'true',
			),
		);
	}

	/**
	 * Display interface to edit the URL of a GIF
	 *
	 * @param object $the_gif The GIF() object currently being edited
	 */
	public function edit_gif_url( $the_gif, $input ) {
		// Define subtitle based on validation of user input
		if ( '' === $input ) {
			$subtitle = 'Enter the new GIF URL';
		} elseif ( is_valid_url( $input ) ) {
			$subtitle = $input;
		} else {
			$subtitle = 'Please enter a valid URL';
		}

		// Build the 'New URL' list item
		$this->items['items'][] = array(
			'title' => 'New GIF URL:',
			'subtitle' => $subtitle,
			'arg' => 'filler arg',
			'valid' => is_valid_url( $input ) ? 'true' : 'false',
			'icon' => array(
				'path' => 'img/edit.png',
			),
			'variables' => array(
				'gif_url' => $input,
				'next_step' => 'gif_name',
				'standby_title' => 'Saving your GIF...',
				'standby_text'  => 'This should only take a moment, please stand by',
				'exit' => 'false',
			),
		);
		// While on the first step, if this is an existing GIF build the 'Keep current URL' list item
		if ( 'true' != $this->new_gif ) {
			$this->items['items'][] = array(
				'title'    => "Keep the GIF's current URL",
				'subtitle' => $the_gif->url,
				'arg'	   => '',
				'icon'	   => array(
					'path' => 'img/checkmark.png'
				),
				'variables' => array(
					'gif_url'   => '',
					'next_step' => 'gif_name',
					'exit'		=> 'false',
				),
			);
		}
	}

	/**
	 * Display interface to edit the name of a GIF
	 *
	 * @param object $the_gif The GIF() object currently being edited
	 */
	public function edit_gif_name( $the_gif, $input ) {
		$this->items['items'][] = array(
			'title'    => 'New GIF name:',
			'subtitle' => null != $input ? $input : 'Enter the new GIF name',
			'arg'	   => 'filler arg',
			'valid'	   => '' === $input ? 'false' : 'true',
			'icon'	   => array(
				'path' => 'img/edit.png',
			),
			'variables' => array(
				'gif_name'  		=> $input,
				'next_step' 		=> 'save_gif',
				'exit' 				=> 'false',
			),
		);

		// While on the gif_name step, if this is an existing GIF provide an option to keep the current name
		if ( 'true' != $this->new_gif ) {
			$this->items['items'][] = array(
				'title'	   => "Keep the GIF's current name",
				'subtitle' => $the_gif->name,
				'arg'	   => 'filler to trigger notifications',
				'icon'	   => array(
					'path' => 'img/checkmark.png'
				),
				'variables' => array(
					'gif_name'  => '',
					'next_step' => 'save_gif',
					'exit'		=> 'false',
				),
			);
		}
	}

	/**
	 * Output the items array
	 * 
	 * Prevents Alfred from displaying his default actions on unused modifier keys
	 *
	 * @return array
	 */
	public function output_items()
	{
		// List all possible mods
		$mods = array(
			'cmd',
			'option',
			'ctrl',
			'shift',
		);

		// Loop through each provided list items
		foreach ( $this->items['items'] as $k => $item ) {
			// Initialize the main item subtitle and validity (if missing, default validity to 'true')
			$subtitle = $item['subtitle'];
			$valid = isset ( $item ['valid'] ) ? $item['valid'] : 'true';

			// If the 'mods' sub-array is missing, initialize it
			if ( !array_key_exists('mods', $item ) ) {
				$item['mods'] = array();
			}

			// Initialize default format
			$format = array(
				'subtitle' => $subtitle,
				'valid' => $valid,
			);

			// Loop through each possible mod and if it's missing, insert it with default values
			foreach ( $mods as $mod ) {
				if ( !array_key_exists($mod, $item['mods'] ) ) {
					$item['mods'][$mod] = $format;
				}
			}

			// Update the items array with the updated item
			$this->items['items'][$k] = $item;
		}
		
		// Encode items array into JSON for Alfred to parse
		$output = json_encode( $this->items );

		// Echo the encoded array to Alfred
		echo $output;
	}

}
