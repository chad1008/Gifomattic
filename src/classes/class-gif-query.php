<?php
/**
 * The GIF Query class.
 *
 * @since 2.0
 */
class GIF_Query {

	/**
	 * Query vars
	 *
	 * @since 2.0
	 * @var $query string The Alfred input query
	 * @var $query_type string Allows for filtering different query types in $this_>get_gifs()
	 */
	public $query;
	public $query_type;
	public $tag_to_search;

	/**
	 * The sqlite3 object for the Gifomattic database
	 *
	 * @since 2.0
	 * @var object
	 */
	public $db;

	/**
	 * The current GIF being iterated through
	 *
	 * @since 2.0
	 * @var array
	 */
	public $current_gif;

	/**
	 * The number of GIFs returned by the current query
	 *
	 * @since 2.0
	 * @var int
	 */
	public $gif_count;

	/**
	 * The GIFs array. Contains all of the GIFs returned by the current query.
	 * Each GIF in the array is an associative array of the values requested from the database.
	 *
	 * @since 2.0
	 * @var array
	 */
	public $gifs;

	/**
	 * Constructor.
	 *
	 * Sets up the GIF query
	 *
	 * @since 2.0
	 *
	 * @param mixed $query Alfred user input
	 * @param string $type Type of query required
	 * @param int $tag Optional tag to filter GIFs from
	 */
	public function __construct( $query, $type='', $tag=null) {
		// Set query and query type properties
		$this->query = $query;
		$this->query_type = $type;
		$this->tag_to_search = $tag;

		// Initialize counts
		$this->current_gif = -1;

		// Set database connection
		$this->db = prep_db();

		// Populate the GIFs array
		$this->gifs = $this->get_gifs();

		// Count the GIFs in the query
		$this->gif_count = $this->count_gifs();
	}
	
	/**
	 * Query GIFs based on the user-provided name or workflow-provided ID
	 *
	 * Generates the GIFs array. Relies on Alfred function getenv() for variables passed between workflow nodes
	 *
	 * @since 2.0
	 *
	 * @return array
	 */
	public function get_gifs() {
		if ( $this->query_type == 'gifs_with_tag' ) {
			// Prepare the initial query of all GIFs with the current tag
			$prepped_stmt = "SELECT *
						 			FROM gifs
						 				LEFT JOIN tag_relationships
											ON gifs.gif_id = tag_relationships.gif_id
											WHERE tag_relationships.tag_id IS :tag";
			// Prepare additional statement to GIF names by user input
			$filter_gif_name = " AND gifs.name LIKE  '%' || :query ||'%'";

			// Append the filter statement if user input is provided
			$prepped_stmt .= $this->query != '' ? $filter_gif_name : '';

			// Prepare the final query and bind the tag ID value
			$stmt = $this->db->prepare( $prepped_stmt );
			$stmt->bindValue( ':tag', $this->tag_to_search );
		} else {
			$stmt = $this->db->prepare( "SELECT * FROM gifs WHERE name LIKE '%' || :query ||'%'" );
		}
		$stmt->bindValue( ':query', $this->query );
		$result = $stmt->execute();

		//Build the GIFs array
		$gifs = array();
		while ( $gif = $result->fetchArray( SQLITE3_ASSOC ) ) {
			$gifs[] = $gif;
		}

		return $gifs;
	}

	/**
	 * Count the number of GIFs returned by the current query.
	 *
	 * Used to define $this->gif_count
	 *
	 * @since 2.0
	 *
	 * @return int
	 */
	public function count_gifs() {
		$gif_count = count( $this->gifs );

		return $gif_count;
	}

	/**
	 * Check if there are additional GIFs in the query results
	 *
	 * @since 2.0
	 *
	 * @return bool True if there are more GIFs, False if there are not
	 */
	public function have_gifs() {
		if ( $this->current_gif + 1 < $this->gif_count ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * The current GIF being accessed by the loop
	 *
	 * Outputs XML list elements formatted for Alfred
	 *
	 * @since 2.0
	 *
	 * @return array
	 */
	public function the_gif() {
		// Increment the current_gif pointer, and then use it to identify the current gif from the GIF array
		++$this->current_gif;
		$current_gif = $this->gifs[$this->current_gif]['gif_id'];

		// Populate GIF data into an array for eventual output as JSON for Alfred
		$the_gif = new GIF( $current_gif );

		return $the_gif;
	}

	/**
	 * Select a random GIF from the query's results
	 *
	 * @since 2.0
	 */
	public function random() {
		$rand = array_rand( $this->gifs );

		return $this->gifs[$rand]['gif_id'];
	}
}