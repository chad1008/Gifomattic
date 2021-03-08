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
	 * @var
	 */
	
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
	 * Checks and organizes environment variables
	 *
	 * @since 2.0
	 */
	public function __construct() {
	}

	/**
	 * Initialize the items array for script filter output
	 *
	 * @since 2.0
	 */
	public function initialize_items() {
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
			$subtitle = gif_quantity($args);

			// Set up the result
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
	 * Show an individual tag in script filter results
	 *
	 * @param object $the_gif  The current Tag() object being displayed
	 * @param string $mode     Declares the format currently needed
	 */
	public function the_gif( $the_gif, $mode ) {

		// Format GIF details for Search mode
		if ( 'search' === $mode) {
			$this->items['items'][] = array(
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
		// Format GIF details for Explore mode
		} elseif ( 'explore' === $mode ) {

		}
	}

	/**
	 * Show a 'no results found' message
	 *
	 * @param string $mode Determines if this search was for GIFs, tags, or both. Defaults to 'both'
	 */
	public function no_results($mode = 'both' ) {
		if ( 'gifs' === $mode ) {
			$missing_items = 'GIFs';
		} elseif ( 'tags' === $mode ) {
			$missing_items = 'tags';
		} elseif ( 'both' === $mode ) {
			$missing_items = 'GIFs or tags';
		}
		$this->items['items'][] = array(
			'title'		=> "There are no $missing_items that match your search!",
			'subtitle'  => "If you've entered a URL you can save it as a new GIF. Otherwise, please try again.",
			'valid'	    => 'false',
		);
	}

	/**
	 * Display option for saving a new GIF
	 * 
	 * @param string $input User input for URL validation and saving
	 */
	public function add_gif( $input ) {
		// Define subtitle based on validation of user input
		if ( is_valid_url( $input ) ) {
			$subtitle = "Save GIF URL: $input";
		} else {
			$subtitle = 'Enter a valid URL';
		}

		// If $input is a valid URL, save it and move to gif_name. Otherwise ignore $input and move to gif_url
		$this->items['items'][] = array(
			'title'    => 'Add a new GIF to your library',
			'subtitle' => $subtitle,
			'arg'	   => $input,
			'valid'    => is_valid_url( $input ) ? 'true' : 'false',
			'icon' 	   => array(
				'path' => 'img/add.png'
			),
			'variables' => array(
				'item_type' => 'gif',
				'new_gif'   => 'true',
				'gif_url'   => is_valid_url( $input ) ? $input : '',
				'next_step' => is_valid_url( $input ) ? 'gif_name' : 'gif_url',
				'notification_title' => 'Saving your GIF',
				'notification_text'  => 'This should only take a moment, please stand by',
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
			'mods'		=> array(
				'cmd' => array(
					'subtitle'  => $subtitle,
					'valid'	  	=> 'false',
				),
				'option' => array(
					'subtitle'  => $subtitle,
					'valid'	  	=> 'false',
				),
				'ctrl' => array(
					'subtitle'  => $subtitle,
					'valid'	  	=> 'false',
				),
				'shift' => array(
					'subtitle'  => $subtitle,
					'valid'	  	=> 'false',
				),
			),
		);

	}
}
