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

	/**
	 * A list of the tags assigned to the GIF
	 *
	 * @since 2.0
	 * @var array
	 */
	public $tags;

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
		$this->random_count_statement = $this->format_count_statement( 'random_count' );
		$this-> total_count_statement = $this->total_count_statement();
		
		// Set the tag list, if possible
		$this->tags = $this->get_tags();

		// Set icon path
		global $icons;
		$this->icon = $icons . $this->id . '.jpg';


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
	public function update_gif() {
		$stmt = $this->db->prepare( "UPDATE gifs SET url=:url, name=:name WHERE gif_id = :id" );
		$args = array(
			':url'  => $this->url,
			':name' => $this->name,
			':id'	=> $this->id,
		);
		bind_values( $stmt, $args );
		$stmt->execute();
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
}
