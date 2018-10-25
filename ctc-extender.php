<?php
/*
    Plugin Name: CTC Extender
    Description: Plugin to supplement the Church Content plugin by adding additional features. Requires <strong>Church Content</strong> plugin. Designed to work woth Church Content v2.0
    Version: 2.0b
    Author: Justin R. Serrano
*/

// No direct access
if ( !defined( 'ABSPATH' ) ) exit;

global $CTCEX;

define( 'CTCEX_VERSION' ) = '2.0b';

if( ! class_exists( 'CTC_Extender' ) ) {
	require_once( sprintf( "%s/ctc-extender-class.php", dirname(__FILE__) ) );
	$CTCEX = new CTC_Extender();
}


/**********************************************
 *
 * Public shortcuts to CTCEX features
 *
 *********************************************/


/**
 * Get sermon data
 *
 * @param  string  $post_id     ID of post to retrieve data from
 * @return mixed                Array of sermon data  
 */
function ctcex_get_sermon_data( $post_id, $default_img = '' ){
	global $CTCEX;
	if( $CTCEX ) return $CTCEX->get_sermon_data( $post_id, $default_img ); 
}

/**
 * Get event data
 *
 * @param  string  $post_id     ID of post to retrieve data from
 * @return mixed                Array of event data  
 */
function ctcex_get_event_data( $post_id ){
	global $CTCEX;
	if( $CTCEX ) return $CTCEX->get_event_data( $post_id ); 
}
/**
 * Get location data
 *
 * @param  string  $post_id     ID of post to retrieve data from
 * @return mixed                Array of location data  
 */
function ctcex_get_location_data( $post_id ){
	global $CTCEX;
	if( $CTCEX ) return $CTCEX->get_location_data( $post_id ); 
}

/**
 * Get person data
 *
 * @param  string  $post_id     ID of post to retrieve data from
 * @return mixed                Array of person data  
 */
function ctcex_get_person_data( $post_id ){
	global $CTCEX;
	if( $CTCEX ) return $CTCEX->get_person_data( $post_id ); 
}

/**
 * Get group data
 *
 * @param  string  $post_id     ID of post to retrieve data from
 * @return mixed                Array of group data  
 */
function ctcex_get_group_data( $post_id ){
	global $CTCEX;
	if( $CTCEX ) return $CTCEX->get_group_data( $post_id ); 
}

// Get data
// @param  string  $post_obj    Post object to retrieve the recurrence note for
// @return string               Recurrence note
function ctcex_get_recurrence_note( $post_obj ){
	global $CTCEX;
	if( $CTCEX ) return $CTCEX->get_recurrence_note( $post_obj ); 
}

// Forward the data for events
function ctcex_update_recurring_events(){
	global $CTCEX;
  if( $CTCEX ) $CTCEX->update_recurring_event_dates(); 
}

// Get option data
// @param string   $option   Name of option to retrieve value
// @param string   $default  Default value to return if option is not found
// @return string            Value of option or default value if not found
function ctcex_get_option( $option, $default = '' ){
	$options = get_option( 'ctcex_settings' );
	if( isset( $options[ $option ] ) )
		return apply_filters( 'ctcex_translate', $option, $options[ $option ] );
	else
		return $default;
}

/**
 * Determine if an option exists
 *
 * @param  string  $option      name of option to look up
 * @return bool                 True if option exits
 */
function ctcex_has_option( $option ) {
	$options = get_option( 'ctcex_settings' );
	return array_key_exists( $option, $options );
}

/**
 * Retrieve the url of a taxonomy image
 *
 * @param  mixed  $term_id      ID of taxonomy term to get image of
 * @return string               URL of taxonomy image
 */
function ctcex_tax_img_url( $term_id = NULL ) {
	
	// Check if the taxonomy archive is being displayed
	if( is_tax() ) {	
		$tax = get_query_var( 'taxonomy' );
		$term = get_query_var( 'term' );
		$current_term = get_term_by( 'slug', $term, $tax );
		$term_id = $current_term->term_id;
	} else {
		if( $term_id ){
			$tax = get_term( $term_id )->taxonomy;
		} else {
			// w/o a term_id there's not much to do
			return;
		}
	}
	
	if( $tax ){
		$imgsrc = call_user_func( array( 'CTCEX_TaxImages', 'get_tax_image' ), $tax, $term_id );
		$imgsrc = apply_filters( 'ctcex_tax_img_url', $imgsrc, $term_id );
		return $imgsrc;
	}

	return;
	
}
