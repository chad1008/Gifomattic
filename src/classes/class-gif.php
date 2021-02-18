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
	private $id;

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
	 * The number of times the GIF has been randomly selected based on its tag(s)
	 *
	 * @since 2.0
	 * @var int
	 */
	public $random_count;

	/**
	 * The date the GIF was first saved
	 *
	 * @since 2.0
	 * @var string
	 */
	public $date;
	
	/**
	 * An array of all of the GIF's data, from the 'gifs' table (i.e. everything except the tags)
	 *
	 * @since 2.0
	 * @var array
	 */
	//private $data;

	/**
	 * A list of the tags associated with the GIF
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
		
		// Set the tag list, if possible
		$this->tags = $this->get_tags();
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
		$stmt = $this->db->prepare( "SELECT tags.tag as name,tags.tag_id as id
									 FROM tags LEFT JOIN tag_relationships
										ON tags.tag_id = tag_relationships.tag_id
											WHERE tag_relationships.gif_id IS :id
									" );
		$stmt->bindValue( ':id', $this->id );
		$result = $stmt->execute();

		// Build an array of this GIF's tags
		$tags = array();
		while ( $tag = $result->fetchArray( SQLITE3_ASSOC ) ) {
			$tags[] = $tag;
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
}