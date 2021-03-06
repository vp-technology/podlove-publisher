<?php
namespace Podlove\Model;

/**
 * We could use simple post_meta instead of a table here
 */
class Episode extends Base {

	/**
	 * Generate a human readable title.
	 * 
	 * Return name and, if available, the subtitle. Separated by a dash.
	 * 
	 * @return string
	 */
	public function full_title() {
		
		$post_id = $this->post_id;
		$post    = get_post( $post_id );
		$title   = $post->post_title;
		
		if ( $this->subtitle )
			$title = $title . ' - ' . $this->subtitle;
		
		return $title;
	}

	public function media_files() {
		global $wpdb;
		
		$media_files = array();
		
		$sql = '
			SELECT M.*
			FROM ' . MediaFile::table_name() . ' M
				JOIN ' . EpisodeAsset::table_name() . ' A ON A.id = M.episode_asset_id
			WHERE M.episode_id = \'' . $this->id . '\'
			ORDER BY A.position ASC
		';

		$rows = $wpdb->get_results( $sql );
		
		if ( ! $rows ) {
			return array();
		}
		
		foreach ( $rows as $row ) {
			$model = new MediaFile();
			$model->flag_as_not_new();
			foreach ( $row as $property => $value ) {
				$model->$property = $value;
			}
			$media_files[] = $model;
		}
		
		return $media_files;
	}

	public function find_or_create_by_post_id( $post_id ) {
		$episode = Episode::find_one_by_property( 'post_id', $post_id );

		if ( $episode )
			return $episode;

		$episode = new Episode();
		$episode->post_id = $post_id;
		$episode->save();

		return $episode;
	}

	public function enclosure_url( $episode_asset ) {
		$media_file = MediaFile::find_by_episode_id_and_episode_asset_id( $this->id, $episode_asset->id );
		return $media_file->get_file_url();
	}

	public function get_cover_art_with_fallback() {

		if ( ! $image = $this->get_cover_art() )
			$image = Podcast::get_instance()->cover_image;

		return $image;
	}

	public function get_cover_art() {
		
		$podcast = Podcast::get_instance();
		$asset_assignment = AssetAssignment::get_instance();

		if ( $asset_assignment->image == 0 )
			return;

		if ( $asset_assignment->image == 'manual' )
			return $this->cover_art;

		$cover_art_file_id = $asset_assignment->image;
		if ( ! $asset = EpisodeAsset::find_one_by_id( $cover_art_file_id ) )
			return false;

		if ( ! $file = MediaFile::find_by_episode_id_and_episode_asset_id( $this->id, $asset->id ) )
			return false;

		return ( $file->size > 0 ) ? $file->get_file_url() : false;
	}

	public function refetch_files() {
		foreach ( EpisodeAsset::all() as $asset ) {
			if ( $file = MediaFile::find_by_episode_id_and_episode_asset_id( $this->id, $asset->id ) ) {
				$file->determine_file_size();
				$file->save();
			}
		}
	}

	public function get_duration( $format = 'HH:MM:SS' ) {
		$duration = new \Podlove\Duration( $this->duration );
		return $duration->get( $format );
	}

}

Episode::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Episode::property( 'post_id', 'INT' );
Episode::property( 'subtitle', 'TEXT' );
Episode::property( 'summary', 'TEXT' );
Episode::property( 'enable', 'INT' ); // listed in podcast directories or not?
Episode::property( 'slug', 'VARCHAR(255)' );
Episode::property( 'duration', 'VARCHAR(255)' );
Episode::property( 'cover_art', 'VARCHAR(255)' );
Episode::property( 'chapters', 'TEXT' );
Episode::property( 'record_date', 'DATETIME' );
Episode::property( 'publication_date', 'DATETIME' );
