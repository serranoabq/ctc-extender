<?php
/*
    Plugin Name: CTC Extender
    Description: Plugin to supplement the Church Theme Content plugin by adding additional features. Requires <strong>Church Theme Content</strong> plugin.
    Version: 1.2
    Author: Justin R. Serrano
*/

// No direct access
if ( !defined( 'ABSPATH' ) ) exit;

global $CTCEX;
if( ! class_exists( 'CTC_Extender' ) ) {
	require_once( sprintf( "%s/ctc-extender-class.php", dirname(__FILE__) ) );
	$CTCEX = new CTC_Extender();
}

// public shortcuts to some class features 

// Get sermon data
// @param string $post_id ID of post to retrieve data from
// @param string $default_img URI to default image to return in data structure
// @return An array of the relevant sermon data
function ctcex_get_sermon_data( $post_id, $default_img = '' ){
	global $CTCEX;
	if( $CTCEX ) return $CTCEX->get_sermon_data( $post_id, $default_img ); 
}

// Get event data
// @param string $post_id ID of post to retrieve data from
// @return array An array of the event data
function ctcex_get_event_data( $post_id ){
	global $CTCEX;
	if( $CTCEX ) return $CTCEX->get_event_data( $post_id ); 
}

// Get location data
// @param string $post_id ID of post to retrieve data from
// @return array An array of the event data
function ctcex_get_location_data( $post_id ){
	global $CTCEX;
	if( $CTCEX ) return $CTCEX->get_location_data( $post_id ); 
}

// Get person data
// @param string $post_id ID of post to retrieve data from
// @return array An array of the event data
function ctcex_get_person_data( $post_id ){
	global $CTCEX;
	if( $CTCEX ) return $CTCEX->get_person_data( $post_id ); 
}
// Get data
// @param string $post_obj Post object to retrieve the recurrence note for
// @return string Recurrence note
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
// @param string $option Name of option to retrieve value
// @param string $default Default value to return if option is not found
// @return string Value of option or default value if not found
function ctcex_get_option( $option, $default = '' ){
	$options = get_option( 'ctcex_settings' );
	if( $options[ $option ] )
		return $options[ $option ];
	else
		return $default;
}

function ctcex_tax_img_url( $term_id = NULL ) {
	if( $term_id )
		$imgsrc = get_option( 'ctc_tax_img_' . $term_id );
	elseif( is_tax() ) {	
		$current_term = get_term_by( 'slug', get_query_var( 'term' ), get_query_var('taxonomy' ) );
		$imgsrc = get_option( 'ctc_tax_img_' . $current_term->term_id );
	}

	// Allow filtering with add_filter( 'ctcex_tax_img_url_filter', 'some_func', 10, 2 ) 
	// and function some_func( $imgsrc, $term_id )
	// This would allow overriding this particular function but allow another 
	// taxonomy image plugin to be used with CTC-related functions
	$imgsrc = apply_filters( 'ctcex_tax_img_url_filter', $imgsrc, $term_id );
	return $imgsrc;
}