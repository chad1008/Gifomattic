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
	 * @var $query_type Allows for filtering different query types in $this_>get_gifs() and $this->get_tags()
	 */
	protected $argv;
	public $query;
	public $query_type;

	/**
	 * The sqlite3 object for the Gifomattic database
	 *
	 * @since 2.0
	 * @var object
	 */
	public $db;

	/**
	 * The current GIF or tag being iterated through
	 *
	 * @since 2.0
	 * @var array
	 */
	public $current_gif;
	public $current_tag;

	/**
	 * The number of GIFs and tags returned by the current query
	 *
	 * @since 2.0
	 * @var int
	 */
	public $gif_count;
	public $tag_count;

	/**
	 * The GIFs and tags arrays. Contains all of the GIFs and tags returned by the current query.
	 * Each GIF or tag in the array is an associative array of the values requested from the database.
	 *
	 * @since 2.0
	 * @var array
	 */
	public $gifs;
	public $tags;

	/**
	 * Constructor.
	 *
	 * Sets up the GIF query
	 * 
	 * Relies on Alfred-provided $argv[1] for user input
	 *
	 * @since 2.0
	 *
	 * @param mixed $query Alfred user input
	 * @param string $type Type of query required
	 */
	public function __construct( $query, $type='' ) {
		// Set query and query type properties
		$this->query = $query;
		$this->query_type = $type;

		// Initialize counts
		$this->current_gif = -1;
		$this->current_tag = -1;

		// Set database connection TODO Remove testing conditional
		if ( isset( $_SERVER['alfred_workflow_data'] ) ) {
			$file = $_SERVER['alfred_workflow_data'] . '/gifomattic.db';
		} else {
			$file = 'gifomattic.db';
		}
		$this->db = new sqlite3($file);

		// Set icon folder path
		global $icons;
		$icons = $_SERVER['alfred_workflow_data'] . '/icons/';

		// Populate the GIFs and tags arrays
		$this->gifs = $this->get_gifs();
		$this->tags = $this->get_tags();

		// Count the GIFs and tags in the query
		$this->gif_count = $this->count_gifs();
		$this->tag_count = $this->count_tags();

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
		if ( $this->query_type == 'gif_by_id' ) {
			$stmt = $this->db->prepare( "SELECT * FROM gifs WHERE gif_id IS :query" );
		} elseif ( $this->query_type == 'tag_by_id' ) {
			$stmt = $this->db->prepare( "SELECT *
										 FROM gifs LEFT JOIN tag_relationships
									  		ON gifs.gif_id = tag_relationships.gif_id
									 			WHERE tag_relationships.tag_id IS :query
										" );
		} else {
			$stmt = $this->db->prepare( "SELECT * FROM gifs WHERE name LIKE '%' || :query ||'%'" );
		}
		$stmt->bindValue( ':query', $this->query );
		$result = $stmt->execute();

		//Build the GIFs array
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
	public function count_gifs() { //TODO Update to adapt to query_types
		/*$stmt = $this->db->prepare( "SELECT COUNT(*) as count FROM gifs WHERE name LIKE '%' || :query ||'%'" );
		$stmt->bindValue( ':query', $this->query );
		$result = $stmt->execute();
		$gif_count = $result->fetchArray( SQLITE3_ASSOC )['count'];*/

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
	 * @return mixed
	 */
	public function the_gif() {
		global $icons;
		++$this->current_gif;
		$the_gif = $this->gifs[$this->current_gif];

		echo '<item arg="' . $the_gif['gif_id'] . '">';
		echo '<title>' . htmlspecialchars( $the_gif['name'] ) . '</title>';
		echo '<subtitle>' . $the_gif['url'] . '</subtitle>';

		$icon = $icons . $the_gif['gif_id'] . '.jpg';
		if ( file_exists( $icon ) ) {
			echo '<icon>' . $icon . '</icon>';
		}
		echo '</item>';
	}

	/**
	 * Increment the share count of the queried GIF
	 *
	 * @param mixed $id ID of GIF to increment. If empty, use current query.
	 * @param string $count Determines which count (selected_count or random_count) to increment
	 *
	 * @since 2.0
	 */
	public function increment_count( $count, $id='' ) {
		$stmt = $this->db->prepare( "UPDATE gifs SET {$count} = {$count} + 1 WHERE gif_id IS :query" );
		if ( $id == '' ) {
			$stmt->bindValue( ':query', $this->query );
		} else {
			$stmt->bindValue( ':query', $id );
		}
		$stmt->execute();
		
	}

	/**
	 * Query tags based on the user-provided name or workflow-provided ID
	 *
	 * Generates the tags array. Relies on Alfred function getenv() for variables passed between workflow nodes
	 *
	 * @since 2.0
	 *
	 * @return array
	 */
	public function get_tags() {
		if ( $this->query_type == 'tag_by_id' ) {
			$stmt = $this->db->prepare( "SELECT tags.tag_id,
										tags.tag,
										COUNT(*) as 'gifs-avail'
									 FROM tags LEFT JOIN tag_relationships
									 	ON tags.tag_id = tag_relationships.tag_id
									 		WHERE tags.tag IS :query 
									 GROUP BY tags.tag_id
								   " );
		} else {
			$stmt = $this->db->prepare( "SELECT tags.tag_id,
										tags.tag,
										COUNT(*) AS 'gifs-avail'
									 FROM tags LEFT JOIN tag_relationships
									 	ON tags.tag_id = tag_relationships.tag_id
									 		WHERE tags.tag LIKE '%' || :query ||'%'
									 GROUP BY tags.tag_id
								   " );
		}
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
		$stmt = $this->db->prepare( "SELECT COUNT(*) as count FROM tags WHERE tag LIKE '%' || :query ||'%'" );
		$stmt->bindValue( ':query', $this->query );
		$result = $stmt->execute();
		$tag_count = $result->fetchArray( SQLITE3_ASSOC )['count'];

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
	 * @return mixed
	 */
	public function the_tag() {
		++$this->current_tag;
		$the_tag = $this->tags[$this->current_tag];

		echo '<item arg="' . $the_tag['tag_id'] . '">';
		echo '<title>' . htmlspecialchars( $the_tag['tag'] ) . '</title>';
		echo '<subtitle>Insert a randomly selected ' . $the_tag['tag'] . ' GIF (' . $the_tag['gifs-avail'] . ' available)</subtitle>';
		echo '</item>';
	}

}