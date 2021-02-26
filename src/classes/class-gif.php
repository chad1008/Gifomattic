<?php
/**
 * The GIF class.
 *
 * @since 2.0
 */

class GIF {
	/**
	 * The sqlite3 object for the Gifomattic database
	 *
	 * @since 2.0
	 * @var object
	 */
	public $db;

	/**
	 * The ID of the GIF
	 *
	 * @since 2.0
	 * @var int
	 */
	public $id;

	/**
	 * The URL of the GIF
	 *
	 * @since 2.0
	 * @var string
	 */
	public $url;

	/**
	 * The name of the GIF
	 *
	 * @since 2.0
	 * @var string
	 */
	public $name;

	/**
	 * The number of times the GIF has been explicitly selected
	 *
	 * @since 2.0
	 * @var int
	 */
	public $selected_count;

	/**
	 * A formatted string stating the number of times the GIF has been explicitly selected
	 *
	 * @since 2.0
	 * @var int
	 */
	public $selected_count_statement;

	/**
	 * The number of times the GIF has been randomly selected based on its tag(s)
	 *
	 * @since 2.0
	 * @var int
	 */
	public $random_count;

	/**
	 * A formatted string stating the number of times the GIF has been randomly selected
	 *
	 * @since 2.0
	 * @var int
	 */
	public $random_count_statement;

	/**
	 * A formatted string stating the total number of times the GIF has been selected
	 *
	 * Array with title and subtitle values
	 *
	 * @since 2.0
	 * @var array
	 */
	public $total_count_statement;

	/**
	 * The date the GIF was first saved
	 *
	 * @since 2.0
	 * @var string
	 */
	public $date;
	
	/**
	 * Path to the GIF's icon file
	 *
	 * @since 2.0
	 * @var string
	 */
	public $icon;
	public $view_icon;

	/**
	 * A list of the tags assigned to the GIF
	 *
	 * @since 2.0
	 * @var array
	 */
	public $tags;

	/**
	 * A flag for creating new GIFs
	 *
	 * @since 2.0
	 * @var  bool
	 */
	public $is_new;

	/**
	 * An array of new property values to update the GIF with
	 *
	 * @since 2.0
	 * @var  array
	 */
	public $new_props;


	public function __construct( int $id=null ) {
		// Set database connection
		$this->db = prep_db();
		
		// Set the ID
		$this->id = $id;
		
		// If an ID was provided, pull the rest of the data from the database
		$data = $this->get_gif_data();

		// Set the URL, name, selected count, random count, and date, IF there's data available
		if ( isset( $data['url'] ) ) {
			$this->url = $data['url'];
		}

		if ( isset( $data['name'] ) ) {
			$this->name = $data['name'];
		}

		if ( isset( $data['selected_count'] ) ) {
			$this->selected_count = $data['selected_count'];
		}

		if ( isset( $data['random_count'] ) ) {
			$this->random_count = $data['random_count'];
		}

		if ( isset( $data['date'] ) ) {
			$this->date = $data['date'];
		}

		// Set the various count statements
		$this->selected_count_statement = $this->format_count_statement( 'selected_count' );
		$this->random_count_statement 	= $this->format_count_statement( 'random_count' );
		$this-> total_count_statement 	= $this->total_count_statement();
		
		// Set the tag list, if possible
		$this->tags = $this->get_tags();

		// Set icon paths
		global $icons;
		$this->icon 	 = $icons . $this->id . '.jpg';
		$this->view_icon = $icons . 'view/' . $this->id . '.jpg';
		$this->edit_icon = $icons . 'edit/' . $this->id . '.jpg';

		// Set the is_new flag
		if ( $this->id == null ) {
			$this->is_new = TRUE;
		} else {
			$this->is_new = FALSE;
		}
	}

	/**
	 * Query GIF data using the provided GIF ID
	 *
	 * Generates the $data array of GIF details
	 *
	 * @since 2.0
	 *
	 * @return array
	 */
	public function get_gif_data() {
		$stmt = $this->db->prepare( "SELECT * FROM gifs WHERE gif_id IS :id" );
		$stmt->bindValue( ':id', $this->id );
		$result = $stmt->execute();

		$data = $result->fetchArray( SQLITE3_ASSOC );
		
		return $data;
	}

	/**
	 * Query GIF's tags using the provided GIF ID
	 *
	 * Generates the $data array tags applied to this GIF
	 *
	 * @since 2.0
	 *
	 * @return array
	 */
	public function get_tags() {
		$stmt = $this->db->prepare( "SELECT tags.tag_id
									 FROM tags LEFT JOIN tag_relationships
										ON tags.tag_id = tag_relationships.tag_id
											WHERE tag_relationships.gif_id IS :id
									" );
		$stmt->bindValue( ':id', $this->id );
		$result = $stmt->execute();

		// Build an array of this GIF's tags
		$tags = array();
		while ( $tag = $result->fetchArray( SQLITE3_ASSOC ) ) {
			$tags[] = new Tag( $tag['tag_id'] );
		}

		return $tags;
	}

	/**
	 * Update the GIF's name and/or URL
	 *
	 * @since 2.0
	 */
	public function save() {
		// Initialize the initial args array
		$args = array();
		if ( isset( $this->new_props['url'] ) ) {
			$args['url'] = $this->new_props['url'];
		}
		if ( isset( $this->new_props['name'] ) ) {
			$args['name'] = $this->new_props['name'];
		}

		//If this is a new GIF, prepare an INSERT statement
		if ( $this->is_new ) {
			$stmt = $this->db->prepare("INSERT INTO gifs ( url,name,date ) VALUES ( :url,:name,:date )");

			// Add the date to the args array
			$args['date'] = $this->new_props['date'];
		} else {
			// Otherwise as long as either a URL or a name have been provided, prepare an UPDATE statement
			if ( isset( $this->new_props['url']) || isset( $this->new_props['name'] ) ) {
				// Initialize the UPDATE statement
				$query = "UPDATE gifs SET";
				// If a URL has been provided, add it to the query
				if (isset( $this->new_props['url'] ) ) {
					$query .= " url = :url";
				}
				// A comma, if needed
				if ( isset( $this->new_props['url']) && isset( $this->new_props['name'] ) ) {
					$query .= " ,";
				}
				// If a name has been provided, add it to the query
				if ( isset($this->new_props['name'] ) ) {
					$query .= " name = :name";
				}
				// Close the query with WHERE clause using the gif ID
				$query .= " WHERE gif_id IS :id";

				// Prep the statement
				$stmt = $this->db->prepare($query);

				// Add the ID to the args array
				$args['id'] = $this->id;
			}
		}

		// As long as there is a new name and/or URL, execute the prepared statement
		bind_values( $stmt, $args );

		if ( isset( $this->new_props['url']) || isset( $this->new_props['name'] ) ) {
			$stmt->execute();
		}

		// If this is a new GIF, grab it's ID and stage it (otherwise, just use the current GIF's ID
		if ( $this->is_new ) {
			$this->new_props['id'] = $this->db->lastInsertRowID();
		} else {
			$this->new_props['id'] = $this->id;
		}
		
		// If a new URL was provided, prepare an icon
		if ( isset ( $this->new_props['url'] ) ) {
			iconify( $this->new_props );
		}
	}

	/**
	 * Increment the GIF share count
	 *
	 * @param string $count Determines which count (selected_count or random_count) to increment
	 *
	 * @since 2.0
	 */
	public function increment_count( $count ) {
		$stmt = $this->db->prepare( "UPDATE gifs SET {$count} = {$count} + 1 WHERE gif_id IS :query" );
		$stmt->bindValue(':query', $this->id );
		$stmt->execute();
	}

	/**
	 * Prepare a formatted statement of a share count
	 *
	 * @param string $chosen_count Determines which count (selected_count or random_count) to format
	 *
	 * @since 2.0
	 *
	 * @return string
	 */
	public function format_count_statement( $chosen_count ) {
		if ($chosen_count == 'selected_count') {
			$count = $this->selected_count;
			$format = "You have %sselected this GIF %s";
		} elseif ($chosen_count == 'random_count') {
			$count = $this->random_count;
			$format = "This GIF has %scome up in a random selection %s";
		}

		// Define selected_count statement
		if ($count == 0) {
			$none = "never ";
		} elseif ($count == 1) {
			$number = "once";
		} else {
			$number = "$count times";
		}

		return sprintf($format, $none, $number);
	}
	/**
	 * Prepare a formatted statement of about the total number of shares for this GIF
	 *
	 * @since 2.0
	 *
	 * @return string
	 */
	public function total_count_statement() {
		
		$total_count = $this->selected_count + $this->random_count;

		//first, define the total count as the sum of selections and randoms, then rewrite the string
		if ( $total_count == 0 ) {
			$output = array(
				'title' => 'This GIF has never been shared!',
				'subtitle' => 'You must be saving it for a special occasion...',
				);
		} elseif ( $total_count == 1 ) {
			$output = array(
				'title' => "That's just one share for this GIF",
				'subtitle' => "Did it not go well? Or are you quitting while you're ahead?",
			);
		} else {
			$output = array(
				'title' => "That's a total of " . $total_count . " shares for this GIF!",
				'subtitle' => "Don't stop now, you're on a roll!",
			);
		}

		return $output;
	}

	/**
	 * Check to see if the GIF has a specific tag assigned
	 *
	 * @param int $id The ID of the tag to compare against the current GIF's tags
	 * 
	 * @since 2.0
	 *
	 * @return bool
	 */
	public function has_tag( $id ) {
		return in_array( $id, array_column( $this->tags, 'id' ) );
	}

	/**
	 * Prepare a temporary preview-flagged icon variant
	 * 
	 * Replaces the single preview icon each time the function runs.
	 *
	 * @since 2.0
	 */
	public function generate_preview_icon() {
		// Create flagged icon variants
		global $icons;
		$flags = imagecreatefrompng( 'img/flags.png' );
		$icon  = imagecreatefromjpeg( $this->icon );

		// Generate "Preview" icon
		imagecopymerge( $icon, $flags, 64, 64, 0, 128, 64, 64, 100 );
		imagejpeg( $icon, 'img/preview.jpg', 10 );

		// Release temp images from memory
		imagedestroy( $icon );
		imagedestroy( $flags );
	}
}
