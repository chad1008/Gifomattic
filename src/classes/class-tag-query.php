<?php
/**
 * The Tag Query class.
 *
 * @since 2.0
 */
class Tag_Query {

	/**
	 * Query vars
	 *
	 * @since 2.0
	 * @var $query string The Alfred input query
	 */
	protected $argv;
	public $query;

	/**
	 * The sqlite3 object for the Gifomattic database
	 *
	 * @since 2.0
	 * @var object
	 */
	public $db;

	/**
	 * The current tag being iterated through
	 *
	 * @since 2.0
	 * @var array
	 */
	public $current_tag;

	/**
	 * The number of tags returned by the current query
	 *
	 * @since 2.0
	 * @var int
	 */
	public $tag_count;

	/**
	 * The tags array. Contains all of the tags returned by the current query.
	 * Each tag in the array is an associative array of the various tag details
	 *
	 * @since 2.0
	 * @var array
	 */
	public $tags;

	/**
	 * Constructor.
	 *
	 * Sets up the tag query
	 **
	 * @since 2.0
	 *
	 * @param string $query Alfred user input
	 */
	public function __construct( $query ) {
		// Store the current query
		$this->query = $query;

		// Initialize counts
		$this->current_tag = -1;

		// Set database connection
		$this->db = prep_db();

		// Populate the tags array
		$this->tags = $this->get_tags();

		// Count the tags returned by the query
		$this->tag_count = $this->count_tags();
	}
	
	/**
	 * Query tags based on the user-provided name or workflow-provided ID
	 *
	 * Generates the tags array.
	 *
	 * @since 2.0
	 *
	 * @return array
	 */
	public function get_tags() {
		$stmt = $this->db->prepare( "SELECT tags.tag_id,
									tags.tag,
									COUNT(*) AS 'gifs-avail'
								 FROM tags LEFT JOIN tag_relationships
									ON tags.tag_id = tag_relationships.tag_id
										WHERE tags.tag LIKE '%' || :query ||'%'
								 GROUP BY tags.tag_id
								 ORDER BY CASE
								 	WHEN tags.tag IS :query THEN 1
								 	ELSE 2
								 	END
							   " );

		$stmt->bindValue( ':query', $this->query );
		$result = $stmt->execute();

		//Build the tags array
		$tags = array();
		while ( $tag = $result->fetchArray( SQLITE3_ASSOC ) ) {
			$tags[] = $tag;
		}

		return $tags;
	}

	/**
	 * Count the number of tags returned by the current query.
	 *
	 * Used to define $this->tag_count
	 *
	 * @since 2.0
	 *
	 * @return int
	 */
	public function count_tags() {
		$tag_count = count( $this->tags );

		return $tag_count;
	}

	/**
	 * Check if there are additional tags in the query results
	 *
	 * @since 2.0
	 *
	 * @return bool True if there are more tags, false if there are not
	 */
	public function have_tags() {
		if ( $this->current_tag + 1 < $this->tag_count ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * The current tag being accessed by the loop
	 *
	 * Outputs XML list elements formatted for Alfred
	 *
	 * @since 2.0
	 *
	 * @return array
	 */
	public function the_tag() {
		// Increment the current_tag pointer, and then use it to identify the current tag from the tag array
		++$this->current_tag;
		$current_tag = new Tag( $this->tags[$this->current_tag]['tag_id'] );

		return $current_tag;
	}
}