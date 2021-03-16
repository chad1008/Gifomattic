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

	/**
	 * The new name that the tag should be updated to when edited
	 *
	 * @since 2.0
	 * @var string
	 */
	public $new_name;

	public function __construct( int $id = null ) {
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
		$stmt = $this->db->prepare( "SELECT tags.tag,
											SUM( CASE WHEN gifs.in_trash = 0 THEN 1 ELSE 0 END ) AS gifs_with_tag
									 FROM tags
									 LEFT JOIN tag_relationships
									 	USING( tag_id )
									 LEFT JOIN gifs
									 	USING (gif_id)
									 WHERE tags.tag_id IS :id"
		);

		$stmt->bindValue( ':id', $this->id );
		$result = $stmt->execute();

		$the_tag = $result->fetchArray( SQLITE3_ASSOC );

		return $the_tag;
	}

	/**
	 * Save the tag's updated info
	 *
	 * @since 2.0
	 */
	public function save() {
		$stmt = $this->db->prepare( "UPDATE tags SET tag = :new_name WHERE tag_id IS :id" );
		$args = array(
			':new_name' => $this->new_name,
			':id'       => $this->id,
		);
		bind_values( $stmt, $args );

		$stmt->execute();
	}

	/**
	 * Delete the tag from the database
	 *
	 * @since 2.0
	 */
	public function delete() {
		$delete_tag = $this->db->prepare( "DELETE FROM tags WHERE tag_id = :tag_id;" );
		$delete_relationships = $this->db->prepare( "DELETE FROM tag_relationships WHERE tag_id = :tag_id;" );
		$delete_tag->bindValue( ':tag_id', $this->id );
		$delete_relationships->bindValue( ':tag_id', $this->id );

		$delete_tag->execute();
		$delete_relationships->execute();
	}
}
