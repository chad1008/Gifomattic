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
	 * 
	 * @var $input string The Alfred input query
	 * @var $query string Custom query string based on the parameters provided
	 * @var $tag_to_search int An optional, specific tag that query results should be filtered against
	 * @var $is_trash_query bool Determines if the query should return trashed or untrashed GIFs. Defaults to FALSE
	 * @var $cleanup int Determines if the query should only return GIFs ready for automatic cleanup. Defaults to FALSE
	 */
	public  $input;
	private $query;
	public  $tag_to_search;
	private $is_trash_query;
	private $cleanup;

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
	 * Constructor
	 *
	 * Sets up the GIF query
	 *
	 * @since 2.0
	 *
	 * @param mixed $input Alfred user input
	 * @param int   $tag Optional tag to filter GIFs from
	 * @param bool  $trash Set if the query should return trashed or untrashed GIFs
	 * @param bool  $cleanup Set if the query should return GIFs ready for automatic removal from trash
	 */
	public function __construct( $input, $tag = null, $trash = FALSE, $cleanup = FALSE ) {
		// Store query and its properties
		$this->input		  = $input;
		$this->tag_to_search  = $tag;
		$this->is_trash_query = $trash;
		$this->cleanup		  = $cleanup;
		$this->query 		  = $this->parse_query();

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
	 * Parse query based on provided parameters
	 *
	 * The query built here is then used by get_gifs() to actually query the database
	 *
	 * @since 2.0
	 *
	 * @return string
	 */
	private function parse_query() {
		// Start with selecting IDs for all of the results of the query
		$query = "SELECT gifs.gif_id FROM gifs";

		// If a tag to filter against was provided, set up a LEFT JOIN (with leading AND for next condition)
		if ( $this->tag_to_search != null ) {
			$query .= " LEFT JOIN tag_relationships
						ON gifs.gif_id = tag_relationships.gif_id
							WHERE tag_relationships.tag_id IS :tag AND";
		// Or, if no tag filter was provided, append a WHERE
		} else {
			$query .= " WHERE";
		}

		// Append LIKE matching for $input if $input is provided (with leading AND for next condition)
		if ( $this->input != '' ) {
			$query .= " gifs.name LIKE  '%' || :input ||'%' AND";
		}

		// Append filter for trashed status
		$query .= " in_trash = :trashed";

		// If cleanup mode is active, check the trashed dates
		if ($this->cleanup == TRUE ) {
			$query .= " AND trash_date < strftime('%s','now','-30 days')";
		}

		return $query;
	}

	/**
	 * Query GIFs using the custom built query statement
	 *
	 * Generates the GIFs array.
	 *
	 * @since 2.0
	 *
	 * @return array
	 */
	public function get_gifs() {
		$stmt = $this->db->prepare( $this->query );
		$args = array(
			':tag'	   => $this->tag_to_search,
			':input'   => $this->input,
			':trashed' => $this->is_trash_query == TRUE ? 1 : 0, // 0 and 1 act as a boolean value for 'trashed' status
		);
		bind_values( $stmt, $args );

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
	 * @since 2.0
	 *
	 * @return object
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
