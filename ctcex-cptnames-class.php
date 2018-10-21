<?php
/*
		Class to allow renaming of CTC post types.
*/
// No direct access
if ( ! defined( 'ABSPATH' ) ) exit;

class CTCEX_CPTNames {
	
	function __construct() {
		$this->version = '2.0';
		
		// Church Theme Content is REQUIRED
		if ( ! class_exists( 'Church_Theme_Content' ) ) return;
		
		//add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'settings_init' ) );
		
		// Change slugs in the custom CTC types
		add_filter( 'ctc_post_type_person_args', array( $this, 'ctcex_post_type_args' ), 10, 1);
		add_filter( 'ctc_post_type_sermon_args', array( $this, 'ctcex_post_type_args' ), 10, 1);
		add_filter( 'ctc_post_type_location_args', array( $this,'ctcex_post_type_args' ), 10, 1);
		add_filter( 'ctc_post_type_event_args', array( $this, 'ctcex_post_type_args' ), 10, 1);
		add_filter( 'ctcex_post_type_group_args', array( $this,'ctcex_post_type_args' ), 10, 1);
		add_filter( 'ctc_taxonomy_sermon_series_args', array( $this,'ctcex_post_type_args' ), 10, 1);
		
	}

	
	/**
	 * Validate inputs
	 *
	 * @since  1.0
	 * @params mixed $input  Input to validate
	 */
	function validate_settings( $input ) {
	
		if ( ! isset( $_POST['reset'] ) ) {
			$options = get_option( 'ctcex_settings' );
			return $input;
		}
		flush_rewrite_rules();
		return false;		
}
	
	/**
	 * Change the slugs of the CTC CPTs according to the new names
	 *
	 * @since  1.0
	 * @params mixed $args  Arguments
	 */
	function ctc_slugs( $args ){
		$old_slug = $args['rewrite']['slug'];
		$old_name = $args['labels']['name'];
		$old_singular_name = $args['labels']['singular_name'];

		$options = get_option( 'ctcex_settings' );
		$option_name = 'ctc-' . $old_slug;
		
		if( ! $options[ $option_name ] ) 
			return $args;
		
		if( $options[ $option_name ] == $old_name )
			return $args;
		
		// Option is in the form of plural/singular
		$option_value = $options[ $option_name ] ? $options[ $option_name ] : implode( '/', array( $old_name, $old_singular_name ) ) ;		
					
		// Get the new plural and singular names
		list( $new_name, $new_singular_name ) = array_pad( explode( "/", $option_value ), 2, $option_value );
		
		// New slug
		$new_slug = sanitize_title( $new_name, $old_slug );
		
		// Search and replace in the arguments 
		$names = array( $old_name, strtolower( $old_name ), $old_singular_name, strtolower( $old_singular_name ) );
		if( strpos( $old_name, 'Sermon ') !== false ) {
			array_push( $names, str_replace( 'Sermon ', '', $old_name ), strtolower( str_replace( 'Sermon ', '', $old_name ) ), str_replace( 'Sermon ', '', $old_singular_name ), strtolower( str_replace( 'Sermon ', '', $old_singular_name ) ) );
		}
		$new_names = array( $new_name, strtolower( $new_name ), $new_singular_name, strtolower( $new_singular_name ) );
		if( strpos( $old_name, 'Sermon ')  !== false ) {
			array_push( $new_names, $new_name, strtolower( $new_name ), $new_singular_name, strtolower( $new_singular_name ) );
		}
		// Names are only changed in the labels
		$args['labels'] = json_decode( str_replace( $names,  $new_names, json_encode( $args['labels'] ) ), true );
		
		// Change the slug
		$args['rewrite']['slug'] = $new_slug;
		
		return $args;
	}

	/**
	 * Get new singular/plural names for post types
	 *
	 * @since  2.0
	 * @params string $post_type  Post type
	 * @params string $form       'singular' or 'plural'
	 * @params string $default    Default value
	 */
	function ctcex_post_type_singular_plural( $post_type, $form = 'singular', $default ){
		$avail_post_types = array( 'sermons', 'events', 'people', 'locations', 'groups', 'sermon-series' );
		if( in_array( $post_type, $avail_post_types ) ){			
			return get_option( "ctcex_{$post_type}_{$form}", $form );
		}
		return $default;
	}
	
	/**
	 * Change the arguments used in registering custom post types
	 *
	 * @since  2.0
	 * @params mixed $args  Arguments to filter
	 */
	function ctcex_post_type_args( $args ){
		// default settings
		$old_slug = $args['rewrite']['slug'];
		$old_plural = $arg['labels']['name'];
		$old_singular = $arg['labels']['singular_name'];
		
		// New settings
		$new_singular = ctcex_post_type_singular_plural( $old_slug, 'singular', $old_singular );
		$new_plural = ctcex_post_type_singular_plural( $old_slug, 'plural', $old_plural );
		$new_slug = sanitize_title( $new_plural, $old_slug );
		
		$add = sprintf( _x( 'Add %s', 'Add post type', 'ctcex' ), $new_singular );
		$edit = sprintf( _x( 'Edit %s', 'Edit post type', 'ctcex' ), $new_singular );
		$new = sprintf( _x( 'New %s', 'New post type', 'ctcex' ), $new_singular );
		$all = sprintf( _x( 'All %s', 'All post types', 'ctcex' ), $new_plural );
		$views = sprintf( _x( 'View %s', 'View post type', 'ctcex' ), $new_singular );
		$viewp = sprintf( _x( 'View %s', 'View post types', 'ctcex' ), $new_plural );
		$search = sprintf( _x( 'Search %s', 'Search post types', 'ctcex' ), $new_plural );
		$none = sprintf( _x( 'No %s found', 'No post types found', 'ctcex' ), $new_plural );
		
		
		// Taxonomy-specific
		$is_tax = array_key_exists( 'hierarchichal', $args );
		$popular = sprintf( _x( 'Popular %s', 'Popular taxonomy', 'ctcex' ), $new_plural );
		$update = sprintf( _x( 'Update %s', 'Update taxonomy', 'ctcex' ), $new_singular );
		$commas = sprintf( _x( 'Separate %s with commas', 'Popular taxonomy', 'ctcex' ), strtolower( $new_plural ) );
		$addremove = sprintf( _x( 'Add or remove %s', 'Popular taxonomy', 'ctcex' ), strtolower( $new_plural ) );
		$choose = sprintf( _x( 'Choose from the most used %s', 'Popular taxonomy', 'ctcex' ), strtolower( $new_plural ) );
		
		if( $is_tax ){
			$new_labels = array(
				'name'								=> esc_html( $new_plural ),
				'singular_name'				=> esc_html( $new_singular ),
				'search_items' 				=> esc_html( $search ),
				'search_items' 				=> esc_html( $search ),
				'popular_items' 			=> esc_html( $popular ),
				'all_items' 					=> esc_html( $add ),
				'parent_item' 				=> null,
				'parent_item_colon' 	=> null,
				'edit_item' 					=> esc_html( $edit ),
				'update_item' 				=> esc_html( $update ),
				'add_new_item' 				=> esc_html( $add ),
				'new_item_name' 			=> esc_html( $new ),
				'separate_items_with_commas' 			=> esc_html( $commas ),
				'add_or_remove_items' => esc_html( $addremove ),
				'choose_from_most_used' => esc_html( $choose ),
				'menu_name' 					=> esc_html( $new_plural )
			);
		} else {
			$new_labels = array(
				'name'								=> esc_html( $new_plural ),
				'singular_name'				=> esc_html( $new_singular ),
				'add_new' 						=> esc_html( $add ),
				'add_new_item' 				=> esc_html( $add ),
				'edit_item' 					=> esc_html( $edit ),
				'new_item' 						=> esc_html( $new ),
				'all_items' 					=> esc_html( $add ),
				'view_item' 					=> esc_html( $views ),
				'view_items'					=> esc_html( $viewp ),
				'search_items' 				=> esc_html( $search ),
				'not_found' 					=> esc_html( $none ),
				'not_found_in_trash' 	=> esc_html( $none )
			);
		}
		$args[ 'labels' ] = $new_labels;
		$args[ 'rewrite' ][ 'slug' ] = $new_slug;
		
		return $args;
	}
	
}
