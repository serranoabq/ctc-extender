<?php
/**
 * Class for managing podcast options
 *
 * @package ctcex
 * @version 0.5
 * @since   CTCEX 2.0b
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class CTCEX_Podcast {
	
	public $version = '0.5'; 
	
	function __construct(){
		
		add_filter( 'rss2_ns', array( $this, 'rss_namespace' ) );
		add_filter( 'rss2_head', array( $this, 'rss_head' ) );
		add_filter( 'request', array( $this, 'feed_request' ) );
		add_filter( 'the_excerpt_rss', array( $this, 'rss_post_thumbnail' ) );
		add_filter( 'the_content_feed', array( $this, 'rss_post_thumbnail' ) );
		add_filter('get_wp_title_rss', array( $this, 'rss_title' ) );
		add_filter( 'the_author', array( $this, 'rss_author' ) );
		
		// Replace do_enclose for local files
		remove_action( 'save_post', 'ctc_sermon_save_audio_enclosure', 11 ); 
		add_action( 'save_post', array( $this, 'sermon_save_audio_enclosure' ), 11, 2 ); 
		
	}
	
	/**
	 * Add itunes namespace 
	 *
	 * @since 0.5
	 */
	function rss_namespace() {
		echo 'xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"';
	}
	
	/**
	 * Update podcast header with information provided in settings
	 *
	 * @since 0.5
	 */
	function rss_head() {
		if( ! $this->is_podcast_feed() ) return;
		
		if( get_option( 'ctcex_podcast_author' ) ) {
				echo '
		<itunes:author>'. option( 'ctcex_podcast_author' ) . '</itunes:author>';
		}
		if( get_option( 'ctcex_podcast_desc' ) ) {
				echo '
		<itunes:summary>'. get_option( 'ctcex_podcast_desc' ) . '</itunes:summary>';
		}
		if( get_option( 'ctcex_podcast_logo' ) ) {
				echo '
		<itunes:image href="'. get_option( 'ctcex_podcast_logo' ) . '"/>';
		}
	}
	
	/**
	 * Filter feed request to show only sermon posts
	 *
	 * @since 0.5
	 * @param  mixed  $qv      Query to filter
	 * @return mixed           Filtered query
	 */
	function feed_request( $qv ) {
		if( ! isset( $qv['feed'] ) ) return $qv;
		
		$is_podcast_feed = $this->is_podcast_feed();
		
		if( is_podcast_feed() ) {
			// Show podcast feed
			$qv[ 'post_type' ] = 'ctc_sermon';
			
		} elseif( ! isset( $qv['pagename'] ) ) {
			
			// Show the regular post feed
			$qv['pagename'] = get_post_field( 'post_name', get_option( 'page_for_posts' ) );	
			
		}
		
		return $qv;
	}
	
	/**
	 * Filter post content to add post image to podcast feed
	 *
	 * @since 0.5
	 * @param string  $content      Post content to filter
	 * @param string                Filtered content
	 */
	function rss_post_thumbnail( $content ) {
		global $post;
		$content = '<p><img src="' . ctcex_getImage( $post->ID ) . '"/></p>' . $content;
		
		return $content;
	}
	
	/**
	 * Add site icon to feed
	 *
	 * @since 0.5
	 */
	function atom_feed_add_icon() { 
?>
	<feed>
		<icon><?php echo get_site_icon_url(); ?></icon>
		<logo><?php echo get_option( 'ctcex_podcast_logo', get_theme_mod( 'custom_logo', '' ) ); ?></logo>
	</feed>
<?php 
	}

	/**
	 * Filter podcast feed title 
	 *
	 * @since 0.5
	 * @param string  $title      Feed title to filter
	 * @param string              Filtered title
	 */
	function rss_title( $title ){
		
		// Update the title for a podcast
		if( $this->is_podcast_feed() ) 
			$title = get_bloginfo( 'name' );
		
		return $title;
	}
	
	/**
	 * Use sermon speaker as post author in podcast feed
	 *
	 * @since 0.5
	 * @param string  $name      Name to filter
	 * @param string             Filtered name
	 */
	function rss_author( $name ){
		if( is_feed() ){
			global $post;
			if ( 'ctc_sermon' != $post->post_type ) {
				return $name;
			}
			$data = ctcex_get_sermon_data( $post->ID );
			if( $data[ 'speakers' ] ) 
				$name = $data[ 'speakers' ];
		}
		return $name;
	}
	
	/**
	 * Streamline the enclosure to work more efficiently with locally-hosted
	 * media files
	 *
	 * @since 0.5
	 * @param mixed   $post_id      Post ID to add enclosure to
	 * @param mixed   $post         Post object this applies to
	 */
	function sermon_save_audio_enclosure( $post_id, $post ) {

		// Stop if no post, auto-save (meta not submitted) or user lacks permission
		if ( 'ctc_sermon' != $post->post_type ) {
			return;
		}
		$post_type = get_post_type_object( $post->post_type );
		if ( empty( $_POST ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
			return;
		}

		// Stop if PowerPress plugin is active
		// Solves conflict regarding enclosure field: http://wordpress.org/support/topic/breaks-blubrry-powerpress-plugin?replies=6
		if ( defined( 'POWERPRESS_VERSION' ) ) {
			return;
		}

		// Get audio URL
		$audio = get_post_meta( $post_id , '_ctc_sermon_audio' , true );

		// The built-in do_enclose method goes a roundabout way of getting the file 
		// length, which involves an http fetch to get the right length. On some server 
		// configurations if the fetch fails the enclosure isn't added. While the fetch 
		// is necessary if the file is remote, it's frustrating if the file is on the 
		// same server, where WP can get all the information without the http fetch. 
		// This method now does the handling of the enclosure data using WP methods if the 
		// file is local and then falls back to the normal do_enclose method if it's 
		// a remote file
		
		// Populate enclosure field with URL, length and format, if valid URL found
		$uploads = wp_upload_dir();
		// A local file is assume to be one living in the uploads directory
		$is_local = stripos( $audio, $uploads[ 'baseurl' ] ); 
		if( ! ( false === $is_local)  ) {
			// Get the path to the file
			$audio_src = str_replace( $uploads['baseurl'], $uploads['basedir'], $audio );
			// Get meta data
			$metadata =  wp_read_audio_metadata( $audio_src );
			if( $metadata ){
				// Make sure we got metadata and read the mime_type 
				// and filesize values which are needed for the enclosure
				$mime = $metadata[ 'mime_type' ];
				$length = $metadata[ 'filesize' ];
				if( $mime ) {
					// We've got data, add enclosure meta
					update_post_meta( $post_id, 'enclosure', "$audio\n$length\n$mime\n" );
				}
			}
		} else {
			// Leave do_enclose for remote files
			do_enclose( $audio, $post_id ); 
		}
	}
	
	/**
	 * Determine if the current url corresponds to the podcast feed url
	 *
	 * @since 0.5
	 * @return bool       True if current feed link is the sermon feed url
	 */
	function is_podcast_feed(){
		$feed[ site_feed ] = bloginfo( 'rss2_url' );
		$feed[ sermon_feed ] = ctcex_get_post_type_archive_url( 'ctc_sermon' ) . 'feed/';
		
		return( $feed[ get_option( 'ctcex_podcast_feed' ) ] == self_link() );
		
	}
}
