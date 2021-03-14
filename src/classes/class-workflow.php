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
	 * TODO update all vars with individual notes
	 */
	public $item_id;
	public $gif_url;
	public $gif_name;
	public $tag_name;
	public $next_step;
	public $new_gif;
	public $is_new_tag;
	public $selected_tag;
	public $save_mode;
	public $trash_mode;
	public $original_input;
	public $confirmed_delete;
	
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
		$this->item_id	 		= getenv( 'item_id' );
		$this->gif_url	 		= getenv( 'gif_url' );
		$this->gif_name	 		= getenv( 'gif_name' );
		$this->tag_name	 		= getenv( 'tag_name' );
		$this->next_step 		= getenv( 'next_step' );
		$this->new_gif   		= getenv( 'new_gif' );
		$this->is_new_tag		= 'true' === getenv( 'is_new_tag' ) ? true : false;
		$this->selected_tag		= getenv( 'selected_tag' );
		$this->save_mode		= getenv( 'save_mode' );
		$this->trash_mode		= getenv( 'trash_mode' );
		$this->original_input	= getenv( 'original_input' );
		$this->confirmed_delete = getenv( 'confirmed_delete' );

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
	 *
	 * @since 2.0
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
			$subtitle = quantity_statement( $args );

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
	 * @param string $input	   User input to be saved for retrieval later on
	 *
	 * @since 2.0
	 */
	public function the_gif( $the_gif, $input ) {
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
						'item_type' => 'gif',
						'original_input' => $input,
					),
				),
			),
		);
	}

	/**
	 * Show a 'no results found' message
	 *
	 * @param string $mode Determines if this search was for GIFs, tags, or both. Defaults to 'both'
	 *
	 * @since 2.0
	 */
	public function no_results( $mode = 'both' ) {
		// Set item type
		if ( 'gifs' === $mode ) {
			$missing_items = 'GIFs';
		} elseif ( 'tags' === $mode ) {
			$missing_items = 'tags';
		} elseif ( 'both' === $mode ) {
			$missing_items = 'GIFs or tags';
		} else {
			$missing_items = 'results';
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
	 *
	 * @since 2.0
	 */
	public function add_gif( $input ) {
		// Prepare subtitle based on validation of user input
		if ( is_valid_url( $input ) ) {
			$subtitle = "Save GIF URL: $input";
		} else {
			$subtitle = 'Add a new GIF to your library (please have the URL ready!)';
		}

		// Build the list item
		// If $input is a valid URL, save it and move to gif_name. Otherwise ignore $input and move to gif_url
		$this->items['items'][] = array(
			'title'    => 'Add a new GIF to your library',
			'subtitle' => $subtitle,
			'arg'	   => $input,
			'valid'    => 'true',
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
	 *
	 * @since 2.0
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
		$subtitle = quantity_statement( $args );

		// Build the trash prompt (invalid if trash is empty)
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
	 *
	 * @since 2.0
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
	 *
	 * @since 2.0
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
				$subtitle = quantity_statement( $args );

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
						'item_type'  => 'tag',
						'item_id'    => $tag->id,
						'trash_mode' => '',
					),
				);
			}
		}
	}

	/**
	 * Display prompts to launch the GIF editor
	 *
	 * @param object $the_gif The GIF() object to be opened in the Editor
	 *
	 * @since 2.0
	 */
	public function launch_editor( $the_gif ) {
		// Build "Edit GIF name" list item
		$this->items['items'][] = array(
			'title' => 'Edit GIF name',
			'subtitle' => 'Current name: "' . $the_gif->name . '"',
			'arg' => 'filler arg',
			'icon' => array(
				'path' => 'img/edit.png',
			),
			'variables' => array(
				'next_step' => 'gif_name',
			),
		);

		// Build "Edit GIF URL" list item
		$this->items['items'][] = array(
			'title' => 'Edit GIF URL',
			'subtitle' => "Current URL: $the_gif->url",
			'arg' => 'filler arg',
			'icon' => array(
				'path' => 'img/edit.png',
			),
			'variables' => array(
				'next_step' => 'gif_url',
			),
		);

		// Build "manage GIF tags" list item
		// Prep subtitle statement
		$args = array(
			'number' => count( $the_gif->tags ),
			'zero' => 'no tags',
			'one'  => 'one tag',
			'many' => count( $the_gif->tags ) . ' tags',
			'format' => 'This GIF currently has %s'
		);
		$subtitle = quantity_statement( $args );

		$this->items['items'][] = array(
			'title' => 'Manage GIF tags',
			'subtitle' => $subtitle,
			'arg' => 'filler arg',
			'icon' => array(
				'path' => 'img/edit.png',
			),
			'variables' => array(
				'next_step' => 'manage_tags', //TODO this is a new thing, make sure it actually functions
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
				'trash_mode'		 => 'true',
				'notification_title' => "GIF trashed!",
				'notification_text'  => '"' . $the_gif->name . '" will be permanently deleted in 30 days.',
				'exit'				 => 'true',
			),
		);
	}

	/**
	 * Display interface to edit the URL of a GIF
	 *
	 * @param string $input User input to be used for the new GIF URL
	 *
	 * @since 2.0
	 */
	public function edit_gif_url( $input ) {
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
				'next_step' => 'true' === $this->new_gif ? 'gif_name' : 'save_gif',
				'standby_title' => 'Saving your GIF...',
				'standby_text'  => 'This should only take a moment, please stand by',
				'exit' => 'false',
			),
		);

		// Build navigation list item
		$this->items['items'][] = array(
			'title' => 'Go back',
			'subtitle' => 'Return to GIF Editor',
			'arg'   => '',
			'icon'		=> array(
				'path'  => 'img/back.png'
			),
			'variables' => array(
				'next_step'	=> 'launch_editor',
			),
		);
	}

	/**
	 * Display interface to edit the name of a GIF
	 *
	 * @param string $input User input to be used for the new GIF name
	 *
	 * @since 2.0
	 */
	public function edit_gif_name( $input ) {
		// Build the 'New name' list item
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

		// Build navigation list item
		$this->items['items'][] = array(
			'title' => 'Go back',
			'subtitle' => 'Return to GIF Editor',
			'arg'   => '',
			'icon'		=> array(
				'path'  => 'img/back.png'
			),
			'variables' => array(
				'next_step'	=> 'launch_editor',
			),
		);

	}

	/**
	 * Display tag deletion confirmation interface
	 *
	 * @param object $the_tag The Tag() object currently slated for deletion
	 *
	 * @since 2.0
	 */
	public function confirm_tag_delete( $the_tag ) {
		// Build confirmation list item
		$this->items['items'][] = array(
			'title'	   => 'Yes, I\'m sure I want to delete the "' . $the_tag->name . '" tag',
			'subtitle' => "This cannot be undone!",
			'icon'	   => array(
				'path' => 'img/destroy.png',
			),
			'variables' => array(
				'next_step'		     => 'save_gif',
				'confirmed_delete'   =>'true',
				'notification_title' => "Tag deleted",
				'notification_text'	 => popup_notice( "\"$the_tag->name\" has been removed from all GIFs" ),
			),
		);

		// Build cancellation list item
		$this->items['items'][] = array(
			'title'	   => 'No, wait! I don\'t want to delete the "' . $the_tag->name . '" tag!',
			'subtitle' => "Go back to tag editing",
			'icon'	   => array(
				'path' => 'img/thinking.png',
			),
			'variables' => array(
				'next_step'		   => '',
				'confirmed_delete' =>'',
			),
		);
	}

	/**
	 * Display tag editing interface
	 *
	 * @param object $the_tag The Tag() object currently being edited
	 * @param string $input   User input to be saved as a new tag name
	 *
	 * @since 2.0
	 */
	public function edit_tag( $the_tag, $input ) {
		// Build tag name list item
		$this->items['items'][] = array(
			'title'    => "New tag name: $input",
			'subtitle' => "Current name: $the_tag->name",
			'arg'	   => $input,
			'icon' 	   => array(
				'path' => 'img/edit.png'
			),
			'variables' => array(
				'tag_name'  => $input,
				'next_step' => 'save_gif',
				'notification_title' => 'Tag updated',
				'notification_text' => popup_notice( "Tag name changed to: \"$input\"" ),
			),
		);

		// Display an option to delete the tag
		$this->items['items'][] = array(
			'title'    => 'Delete the "' . $the_tag->name . '" tag',
			'subtitle' => '(No GIFs will be deleted)',
			'icon' 	   => array(
				'path' => 'img/destroy.png',
			),
			'variables' => array(
				'next_step' => 'confirm_delete'
			),
		);
	}

	/**
	 * Display a generic error message
	 *
	 * @since 2.0
	 */
	public function error() {
		$this->items['items'][] = array(
			'title' 	=> "Sorry, there was an error... Please try again.",
			'subtitle'  => popup_notice( '', true, true  ),
			'valid'		=> 'false',
		);
	}

	/**
	 * Display prompts to launch the tag management interface
	 *
	 * @since 2.0
	 */

	public function launch_tag_management() {
		// Build 'Add tags' list item
		$this->items['items'][] = array(
			'title' => 'Add tags to this GIF',
			'subtitle' => 'You can assign existing tags, or create new ones',
			'arg'   => '',
			'icon'  => array(
				'path' => 'img/add tag.png',
			),
			'variables' => array(
				'next_step'	=> 'add_tags',
			),
		);

		// Build 'Remove tags' list item
		$this->items['items'][] = array(
			'title' => 'Remove tags from this GIF',
			'subtitle' => 'You can always add them again later!',
			'arg'   => '',
			'icon'  => array(
				'path' => 'img/remove tag.png',
			),
			'variables' => array(
				'next_step'	=> 'remove_tags',
			),
		);

		// Build navigation list item
		$this->items['items'][] = array(
			'title'    => 'Go back',
			'subtitle' => 'Return to GIF Editor',
			'arg'      => '',
			'icon'	   => array(
				'path' => 'img/back.png'
			),
			'variables' => array(
				'next_step'	=> 'launch_editor',
			),
		);
	}

	/**
	 * Display options for adding tags to an individual GIF
	 *
	 * @param string $input   User input to use for tag searches
	 * @param object $the_gif The GIF() object tags are being added to
	 * @param object $tags    The Tag_Query object being generated based on $input
	 *
	 * @since 2.0
	 */
	public function add_tags( $input, $the_gif, $tags ){
		// If user input is empty
		if ( $input == '' ) {
			// Build the 'Add a tag' list item
			$this->items['items'][] = array(
				'title'    => 'Add a tag',
				'subtitle' => 'Begin typing to select an existing tag, or create a new one',
				'arg'      => '',
				'valid'    => false,
				'icon'     => array(
					'path' => 'img/add tag.png',
				),
			);
			
		// If user input is not empty, show appropriate tags
		} else {
			// If user input does NOT match any existing tags, prepare to create a new tag
			if ( !in_array( $input, array_column( $tags->tags, 'tag') ) )  {
				// Build the 'Create a new tag" list item
				$this->items['items'][] = array(
					'title' 	=> "Create a new tag: $input",
					'arg'   	=> '',
					'variables'	=> array(
						'is_new_tag' 	=> 'true',
						'next_step'		=> 'save_gif',
						'save_mode' 	=> 'add_tag',
						'selected_tag'	=> $input,
					),
				);
			}

			// Loop through and display existing tags that match the user input
			while ( $tags->have_tags() ) {
				$the_tag = $tags->the_tag();

				// Prepare a quantity statement for the subtitle
				$args = array(
					'number' => $the_tag->gifs_with_tag,
					'zero'   => array(
						'No GIFs',
						'',
					),
					'one'    => array(
						'One other GIF',
						's',
					),
					'many'   => array(
						$the_tag->gifs_with_tag . ' other GIFs',
						'',
					),
					'format' => 'Tag this GIF as "' . $the_tag->name . '" (%s currently use%s this tag)',
				);

				$subtitle = quantity_statement( $args );

				// Build individual tag list items. Disable any tags that are already assigned to this GIF and show a subtitle to that effect
				$this->items['items'][] = array(
					'title' => $the_tag->name,
					'subtitle' => $the_gif->has_tag( $the_tag->id ) ? 'This GIF is already tagged as "' . $the_tag->name . '"' : $subtitle,
					'arg' => $the_tag->id,
					'icon' => array(
						'path' => '',
					),
					'variables' => array(
						'is_new_tag' 	=> false,
						'next_step' 	=> 'save_gif',
						'save_mode' 	=> 'add_tag',
						'selected_tag'	=> $the_tag->name,
					),
					'valid' => $the_gif->has_tag( $the_tag->id ) ? 'false' : 'true',
				);
			}
		}

		// Build navigation list item. If the GIF has no tags, fall back to the main editor interface
		if( empty( $the_gif->tags ) ) {
			$destination = 'the GIF editor';
			$next_step   = 'launch_editor';
		} else {
			$destination = 'tag management options';
			$next_step   = 'manage_tags';
		}
		$this->items['items'][] = array(
			'title' => 'Go back',
			'subtitle' => "Return to $destination",
			'arg'   => '',
			'icon'		=> array(
				'path'  => 'img/back.png'
			),
			'variables' => array(
				'next_step'	=> $next_step,
			),
		);

	}

	/**
	 * Display options for removing tags from an individual GIF
	 *
	 * @param object $the_gif The GIF() object tags are being removed from
	 *
	 * @since 2.0
	 */
	public function remove_tags( $the_gif ) {
		// Iterate through each currently assigned tag
		foreach ( $the_gif->tags as $tag ) {
			// Prepare a quantity statement for the subtitle
			$args = array(
				'number' => $tag->gifs_with_tag - 1,
				'zero'   => array (
					'No',
					'GIFs share',
				),
				'one'	 => array(
					'One',
					'GIF shares',
				),
				'many'   => array(
					$tag->gifs_with_tag - 1,
					'GIFs share',
				),
				'format' => '%s other %s this tag'
			);
			$subtitle = quantity_statement( $args );

			// Build the list item
			$this->items['items'][] = array(
				'title' => 'Remove "' . $tag->name . '" from this GIF',
				'subtitle' => $subtitle,
				'arg'   => $tag->id,
				'icon'  => array(
					'path' => 'img/remove tag.png',
				),
				'variables' => array(
					'next_step'		=> 'save_gif',
					'save_mode' 	=> 'remove_tag',
					'selected_tag'	=> $tag->name,
				),
			);
		}
		
		// Build navigation list item
		$this->items['items'][] = array(
			'title' => 'Go back',
			'subtitle' => 'Return to tag management options',
			'arg'   => '',
			'icon'		=> array(
				'path'  => 'img/back.png'
			),
			'variables' => array(
				'next_step'	=> 'manage_tags',
			),
		);
	}

	/**
	 * Display the interface to launch trash management
	 *
	 * @since 2.0
	 */
	public function launch_trash() {
		// Build 'view trash' list item
		$this->items['items'][] = array(
			'title' => "View all trashed GIFs",
			'subtitle' => 'Restore or delete individual GIFs',
			'icon' => array(
				'path' => 'img/trash.png',
			),
			'variables' => array(
				'next_step' => 'view_trash'
			),
		);
		
		// View 'empty trash' list item
		$this->items['items'][] = array(
			'title' => "Empty trash",
			'subtitle' => 'Permanently delete ALL trashed GIFs. CAUTION: there is no undo!',
			'icon' => array(
				'path' => 'img/destroy.png',
			),
			'variables' => array(
				'next_step' => 'empty_trash'
			),
		);
	}

	/**
	 * Display trashed GIFs
	 *
	 * @since 2.0
	 * @param object $the_gif The GIF() object being display in the trash view
	 */
	public function trashed_gif( $the_gif ) {
		$this->items['items'][] = array(
			'title' => "Trashed: $the_gif->name",
			'subtitle' => 'Restore this GIF (hold CTRL to permanently delete this GIF)',
			'arg' => $the_gif->id,
			'icon' => array(
				'path' => $the_gif->icon,
			),
			'variables' => array(
				'item_type' => 'gif',
				'item_id' => $the_gif->id,
				'next_step' => 'restore_gif',
			),
			'mods' => array(
				'ctrl' => array(
					'subtitle' => 'Delete forever. CAUTION: there is no undo!',
					'icon' => array(
						'path' => 'img/destroy.png',
					),
					'variables' => array(
						'item_id'   => $the_gif->id,
						'next_step' => 'delete_gif',
					),
				),
			),
		);
	}
	
	/**
	 * Output the items array for an Alfred script filter
	 * 
	 * Prevents Alfred from displaying his default actions on unused modifier keys
	 *
	 * @since 2.0
	 */
	public function output_items() {
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

	/**
	 * Output workflow configuration for an Alfred script
	 *
	 * @param string $action The workflow action to generate configuration for
	 * @param mixed $object The GIF() or Tag() object being acted on and drawn from (optional)
	 *
	 *
	 * @since 2.0
	 */
	public function output_config( $action, $object = '' )
	{
		// Initialize the configuration array
		$config = array(
			'alfredworkflow' => array(),
		);

		// Initialize workflow variables for various use cases
		if ( 'save_gif' === $action ) {
			// Prepare notification message. Customize for new GIFs, edited names, and edited URLs. Include a failsafe as well
			if( $object->is_new ) {
				$text = '"' . $object->new_props['name'] . '" has been added to your library';
			} elseif( isset( $object->new_props['name'] ) ) {
				$text = 'GIF name updated to "' . $object->new_props['name'] . '"';
			} elseif( isset( $object->new_props['url'] ) ) {
				$text = 'GIF URL updated to "' . $object->new_props['url'] . '"';
			} else {
				$text = "The GIF's details have been saved";
			}
			$variables = array(
				'item_id'			 => $object->new_props['id'],
				'next_step'			 => $object->is_new ? 'add_tags' : 'launch_editor',
				'notification_title' => 'GIF saved!',
				'notification_text'  => popup_notice( $text ),
				// Clear misc values in preparation of the next loop
				'gif_url'  			 => false,
				'gif_name' 			 => false,
				'standby_title'		 => '',
				'standby_text'		 => '',
			);
		} elseif ( 'save_tag' === $action ) {
			$variables = array(
				'exit' => 'true',
			);
		} elseif ( 'add_tag' === $action ) {
			$tag_message = 'GIF tagged as "' . $this->selected_tag . '"';
			$variables = array(
				'exit'				 => 'false',
				'next_step'  		 => 'add_tags',
				'notification_title' => 'Tag added!',
				'notification_text'  => popup_notice( $tag_message ),
			);
		} elseif ( 'remove_tag' === $action ) {
			$tag_message = '"' . $this->selected_tag . '" removed from this GIF';
			$variables = array(
				'exit'				 => 'false',
				// If this is the last tag assigned to the GIF, next step should be 'launch_editor'
				'next_step'			 => 1 === count( $object->tags ) ? 'launch_editor' : 'remove_tags',
				'notification_title' => 'Tag removed!',
				'notification_text'  => popup_notice( $tag_message ),
			);
		} elseif ( 'restore_gif' === $action ) {
			$subtitle = '"' . $object->name . '" has been has been returned to your library';
			$variables = array(
						'notification_title' => 'GIF restored!',
						'notification_text'  => popup_notice( $subtitle ),
			);
		} elseif ( 'empty_trash' === $action ) {
			// Prepare notification message
			$args = array(
				'number' => $object->gif_count,
				'zero'   => 'No GIFs',
				'one'	 => 'One GIF',
				'many'	 => "$object->gif_count GIFs",
				'format' => '%s permanently deleted',
			);
			$subtitle = quantity_statement($args);

			$variables = array(
				'notification_title' => 'Trash emptied!',
				'notification_text'  => popup_notice( $subtitle ),
			);
		} elseif ('delete_gif' === $action ) {
			$subtitle = '"' . $object->name . '" has been permanently removed from your library';
			// Prepare script output  TODO test and fix deleted GIF output
			$output = array(
				'alfredworkflow' => array(
					'variables' => array(
						'notification_title' => 'GIF deleted!',
						'notification_text'  => popup_notice( $subtitle ),
					),
				),
			);
		} elseif ( 'error' === $action ) {
			$variables = array(
				'notification_title' => 'Sorry, there was an error...',
				'notification_text'  => popup_notice('Please try again', true),
			);
		}

		// Build the configuration array
		// If needed, add the variables
		if ( isset( $variables ) ) {
			$config['alfredworkflow']['variables'] = $variables;
		}
		// Encode the config array into JSON for Alfred to parse and echo it out
		echo json_encode( $config );
	}
}
