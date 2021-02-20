<?php
/**
 * The Tag class.
 *
 * @since 2.0
 */

class Tag {
	/**
	 * The sqlite3 object for the Gifomattic database
	 *
	 * @since 2.0
	 * @var object
	 */
	public $db;

	/**
	 * The ID of the tag
	 *
	 * @since 2.0
	 * @var int
	 */
	public $id;

	/**
	 * The name of the tag
	 *
	 * @since 2.0
	 * @var string
	 */
	public $name;

	public function __construct( int $id=null ) {
		// Set database connection
		$this->db = prep_db();
		
		// Set the ID
		$this->id = $id;
		
		// Pull tag details from the database
		$data = $this->get_tag_data();
		
		// Set the tag name
		$this->name = $data['tag'];
		
		// Count the GIFs with this tag assigned
		$this->gifs_with_tag = $data['gifs_with_tag'];
	}

	/**
	 * Query tag data using the provided tag id
	 *
	 * Generates an array of the tag's details
	 *
	 * @since 2.0
	 *
	 * @return array
	 */
	public function get_tag_data() { 
		$stmt = $this->db->prepare( "SELECT tag,
 											COUNT(*) as gifs_with_tag
									FROM tags 
										JOIN tag_relationships
											USING ( tag_id )
									WHERE tag_id IS :id"
								  );

		$stmt->bindValue( ':id', $this->id );
		$result = $stmt->execute();

		$the_tag = $result->fetchArray( SQLITE3_ASSOC );
		
		return $the_tag;
	}
}