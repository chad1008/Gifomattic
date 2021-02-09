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
	 * @var $props string Properties to manipulate (SELECT, INSERT, etc)
	 * @var $cols string  Database column(s) to check against/insert into
	 */
	public $query;
	public $props;
	public $col;
	
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
	 * The snumber of gifs returned by the current query
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
	 * By default, sets $props to 'gif_id,url,name' and $col to 'name'
	 *
	 * @since 2.0
	 *
	 * @param string $query Alfred user input
	 * @param string $props Values to SELECT from the database
	 * @param string $col Which column should be searched in the database
	 */
	public function __construct( $query='', $props='gif_id,url,name', $col='name' ) {
		// Set object properties
		$this->query = $query;
		$this->props = $props;
		$this->col   = $col;

		// Initiate values
		$this->current_gif = -1;

		// Set database connection
		$file = 'gifomattic.db';
		$this->db = new sqlite3($file);
		
		// Count the GIFs in the query
		$this->gif_count = $this->count_gifs();

		// Populate the GIFs array
		$this->gifs = $this->get_gifs();

	}
	
	/**
	 * Build the prepared statement for the query, based on provided values or defaults
	 *
	 * @since 2.0
	 *
	 * @return object
	 */
	public function get_gifs(){
		// Execute the query
		$stmt = $this->db->prepare( "SELECT {$this->props} FROM gifs WHERE {$this->col} LIKE '%' || :query ||'%'" );
		$stmt->bindValue( ':query', $this->query );
		$result = $stmt->execute();

		//Build the gifs array
		$gifs = array();
		while ( $gif = $result->fetchArray( SQLITE3_ASSOC ) ) {
			$gifs[] = $gif;
		}

		return $gifs;
		
	}

	/**
	 * Count the number of GIFs returned by the current query.
	 *
	 * Used to defind $this->gif_count
	 *
	 * @since 2.0
	 *
	 * @return int
	 */
	public function count_gifs() {
		$stmt = $this->db->prepare( "SELECT COUNT(*) as count FROM gifs WHERE {$this->col} LIKE '%' || :query ||'%'" );
		$stmt->bindValue( ':query', $this->query );
		$result = $stmt->execute();
		$gif_count = $result->fetchArray( SQLITE3_ASSOC )['count'];
		
		return $gif_count;
		
	}

	/**
	 * Check if there are additional GIFs in the query results
	 *
	 *
	 *
	 * @since 2.0
	 *
	 * @return bool True if there are more GIFs, False if there are not
	 */
	public function have_gifs() {
		if ( $this->current_gif + 1 < $this-> gif_count ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * The current GIF being accessed by the loop
	 *
	 * @since 2.0
	 *
	 * @return array //TODO set to return the array rather than printing
	 */
	public function the_gif() {
		++$this->current_gif;
		$the_gif = $this->gifs[$this->current_gif];

		echo "<pre>";
		print_r( $the_gif );
		echo "</pre>";
	}
}