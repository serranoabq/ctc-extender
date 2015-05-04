<?php
/*
    Plugin Name: CTC Extender
    Description: Plugin to supplement the Church Theme Content plugin by adding additional features. Requires <strong>Church Theme Content</strong> plugin.
    Version: 1.0.2
    Author: Justin R. Serrano
*/

// No direct access
if ( !defined( 'ABSPATH' ) ) exit;
global $CTCEX;

require_once( sprintf( "%s/ctcex-full-calendar-class.php", dirname(__FILE__) ) );
require_once( sprintf( "%s/ctcex-recurrence-class.php", dirname(__FILE__) ) );
require_once( sprintf( "%s/ctcex-taximages-class.php", dirname(__FILE__) ) );
require_once( sprintf( "%s/ctcex-cptnames-class.php", dirname(__FILE__) ) );
require_once( sprintf( "%s/ctc-extender-class.php", dirname(__FILE__) ) );

if( class_exists( 'CTC_Extender' ) ) {
	$CTCEX = new CTC_Extender();
}

// public shortcuts to some class features 
function ctcex_get_sermon_data( $post_id, $default_img = '' ){
	global $CTCEX;
	return $CTCEX->get_sermon_data( $post_id, $default_img ); 
}
function ctcex_get_event_data( $post_id ){
	global $CTCEX;
	if( $CTCEX ) return $CTCEX->get_event_data( $post_id ); 
}
function ctcex_get_location_data( $post_id ){
	global $CTCEX;
	if( $CTCEX ) return $CTCEX->get_location_data( $post_id ); 
}
function ctcex_get_person_data( $post_id ){
	global $CTCEX;
	if( $CTCEX ) return $CTCEX->get_person_data( $post_id ); 
}
function ctcex_get_recurrence_note( $post_obj ){
	global $CTCEX;
	if( $CTCEX ) return $CTCEX->get_recurrence_note( $post_obj ); 
}

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