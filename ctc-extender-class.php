<?php
/*
	Main extender class
*/
// No direct access
if ( ! defined( 'ABSPATH' ) ) exit;

class CTC_Extender { 
	public $version; 
	
	function __construct() {
		// Version 
		$this->version = '1.1';
		
		// Church Theme Content is REQUIRED
		if ( ! class_exists( 'Church_Theme_Content' ) ) return;
		
		// Load plugin dependencies
		add_action( 'plugins_loaded', array( &$this, 'load_deps'), 18 );
		
		// Add a few new metaboxes for events
		add_filter( 'ctmb_meta_box-ctc_event_date', array( &$this,'metabox_filter_event_date' ) );

		// Update the event columns recurrence note
		add_filter( 'ctc_event_columns_recurrence_note', array( &$this, 'column_recurrence_note'), 10, 2 );

		// Handle the event recurrence
		remove_action( 'ctc_update_recurring_event_dates', 'ctc_update_recurring_event_dates' );
		add_action( 'ctc_update_recurring_event_dates', array(&$this, 'update_recurring_event_dates' ) );
		
		// Add taxonomy images
		add_action( 'save_post_ctc_sermon', array( &$this, 'save_sermon_image' ), 13);
		add_action( 'save_post_ctc_event', array( &$this, 'save_event_image' ), 13);
		add_action( 'save_post_ctc_location', array( &$this, 'save_location_image' ), 13);
		add_action( 'save_post_ctc_person', array( &$this, 'save_person_image' ), 13);		
	}
	
	// Load plugin dependencies
	function load_deps(){
		require_once( sprintf( "%s/ctcex-full-calendar-class.php", dirname(__FILE__) ) );
		require_once( sprintf( "%s/ctcex-taximages-class.php", dirname(__FILE__) ) );
		require_once( sprintf( "%s/ctcex-cptnames-class.php", dirname(__FILE__) ) );
		require_once( sprintf( "%s/ctcex-recurrence-class.php", dirname(__FILE__) ) );
		
		// Add calendar 
		new CTCEX_FullCalendar();	
		
		// Add Taxonomy Images
		new CTCEX_TaxImages();
		
		// Add Naming options
		new CTCEX_CPTNames();
		
	}
	
	
/********************************************		
	CTC data shortcuts
*********************************************/
	// Get sermon data for use in templates
	function get_sermon_data( $post_id, $default_img = '' ){
		if( empty( $post_id ) ) return;
		
		$permalink = get_permalink( $post_id );
		$img = get_post_meta( $post_id, '_ctc_image' , true ); 
		$default_used = ( $img === $default_img ) && !empty( $img );
		
		// Sermon data
		$video = get_post_meta( $post_id, '_ctc_sermon_video' , true ); 
		$audio = get_post_meta( $post_id, '_ctc_sermon_audio' , true ); 
		
		$ser_series = '';
		$ser_series_slug = '';
		$ser_series_link = '';
		$series = get_the_terms( $post_id, 'ctc_sermon_series');
		
		if( $series && ! is_wp_error( $series) ) {
			$series = array_shift( array_values ( $series ) );
			$ser_series = $series -> name;
			$ser_series_slug = $series -> slug;
			$ser_series_link = get_term_link( intval( $series->term_id ), 'ctc_sermon_series' );
		}
		
		$ser_speakers = '';
		$speakers = get_the_terms( $post_id, 'ctc_sermon_speaker');
		if( $speakers && ! is_wp_error( $speakers ) ) {
			$speakers_A = array();
			foreach ( $speakers as $speaker ) { $speakers_A[] = $speaker -> name; }
			$last = array_pop($speakers_A);
			if( $speakers_A )
				$last = implode(', ', $speakers_A). ", and " .$last;
			$ser_speakers = $last;
		}
		
		$ser_topic = '';
		$ser_topic_slug = '';
		$ser_topic_link = '';
		$topics = get_the_terms( $post_id, 'ctc_sermon_topic');
		if( $topics && ! is_wp_error( $topics ) ) {
			$topics = array_shift( array_values ( $topics ) );
			$ser_topic = $topics -> name;
			$ser_topic_slug = $topics -> slug;
			$ser_topic_link = get_term_link( intval( $topics->term_id ), 'ctc_sermon_topic' );
		}
		
		$data = array(
			'permalink'   => $permalink,
			'img'         => $img,
			'default_used'=> $default_used,
			'name'        => get_the_title( $post_id ),
			'series'      => $ser_series,
			'series_slug' => $ser_series_slug,
			'series_link' => $ser_series_link,
			'speakers'    => $ser_speakers,
			'topic'       => $ser_topic,
			'topic_slug'  => $ser_topic_slug,
			'topic_link'  => $ser_topic_link,
			'audio'       => $audio,
			'video'       => $video,
		);
		
		return $data;
	}

	function get_event_data( $post_id ){
		if( empty( $post_id ) ) return;
		
		$permalink = get_permalink( $post_id );
		$img = get_post_meta( $post_id, '_ctc_image' , true ); 
		
		// Event data
		$start = get_post_meta( $post_id, '_ctc_event_start_date' , true ); 
		$end = get_post_meta( $post_id, '_ctc_event_end_date' , true ); 
		$time = get_post_meta( $post_id, '_ctc_event_start_time' , true );
		if( $time ) $time = date('g:ia', strtotime( $time ) );
		$recurrence = get_post_meta( $post_id, '_ctc_event_recurrence' , true ); 
		$recurrence_note = harvest_get_recurrence_note( get_post( $post_id ) );
		$venue = get_post_meta( $post_id, '_ctc_event_venue' , true ); 
		$address = get_post_meta( $post_id, '_ctc_event_address' , true ); 
		
		$address_url = urlencode( harvest_option( 'city', 'Albuquerque' ) );
		if( $address )  $address_url = urlencode( $address ); 
		$map_img_url = "https://maps.googleapis.com/maps/api/staticmap?size=640x360&zoom=15&scale=2&center=$address_url&style=saturation:-25&markers=color:orange|$address_url";
		$map_url = "http://maps.google.com/maps?q=$address_url";
		$map_used = ( $map_img_url == $img );
		
		$cats = get_the_terms( $post_id, 'ctc_event_category');
		if( $cats && ! is_wp_error( $cats ) ) {
			$cats_A = array();
			foreach( $cats as $cat ){
				$cats_A[] = sprintf('<a href="%s">%s</a>', get_term_link( intval( $cat->term_id ), 'ctc_event_category' ), $cat->name );
			}
			$categories = implode('; ', $cats_A );
		} else {
			$categories = '';
		}
		
		$data = array(
			'name'             => get_the_title( $post_id ),
			'permalink'        => $permalink,
			'img'              => $img,
			'address'          => $address,
			'venue'            => $venue,
			'categories'       => $categories,
			'start'            => $start,
			'end'              => $end,
			'time'             => $time,
			'recurrence'       => $recurrence,
			'recurrence_note'  => $recurrence_note,
			'map_url'		       => $map_url,
			'map_img_url'	     => $map_img_url,
			'map_used'         => $map_used,
		);
		
		return $data;
	}

	// Get location data for use in templates
	function get_location_data( $post_id ){
		$permalink = get_permalink( $post_id );
		$img = get_post_meta( $post_id, '_ctc_image' , true ); 
		
		// Location data
		$address = get_post_meta( $post_id, '_ctc_location_address' , true ); 
		$phone = get_post_meta( $post_id, '_ctc_location_phone' , true ); 
		$times = get_post_meta( $post_id, '_ctc_location_times' , true ); 
		$slider = get_post_meta( $post_id, '_ctc_location_slider' , true ); 
		
		$address_url = urlencode( 'Albuquerque' );
		if( $address )  $address_url = urlencode( $address ); 
		$map_img_url = "https://maps.googleapis.com/maps/api/staticmap?size=640x360&zoom=15&scale=2&center=$address_url&style=saturation:-25&markers=color:orange|$address_url";
		$map_url = "http://maps.google.com/maps?q=$address_url";
		$map_used = ( $map_img_url == $img );
		
		$data = array(
			'name'        => get_the_title( $post_id ),
			'permalink'   => $permalink,
			'img'         => $img,
			'slider'      => $slider,
			'address'     => $address,
			'phone'       => $phone,
			'times'       => $times,
			'map_url'		  => $map_url,
			'map_img_url'	=> $map_img_url,
			'map_used'    => $map_used,
		);
		
		return $data;
	}

	// Get person data for use in templates
	function get_person_data( $post_id ){
		if( empty( $post_id ) ) return;
		
		$permalink = get_permalink( $post_id );
		$img = get_post_meta( $post_id, '_ctc_image' , true ); 
		
		// Person data
		$position = get_post_meta( $post_id, '_ctc_person_position' , true ); 
		$email = get_post_meta( $post_id, '_ctc_person_email' , true ); 
		$phone = get_post_meta( $post_id, '_ctc_person_phone' , true ); 
		$url = get_post_meta( $post_id, '_ctc_person_urls' , true ); 
		
		$data = array(
			'name'      => get_the_title( $post_id ),
			'permalink' => $permalink,
			'img'       => $img,
			'position'  => $position,
			'email'     => $email,
			'url'       => $url,
		);
		
		return $data;
	}
	
/********************************************		
	CTC images shortcuts
*********************************************/
	// Apply an image to a ctc_sermon post as meta data. 
	// A default image can be given through the ctc_sermon_image 
	// filter, if not an image attached to the post is used.
	// Finally, if a taxonomy image is specified, then 
	function save_sermon_image( $post_id ){
		$img = apply_filters( 'ctc_sermon_image', '' );
		$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'ctc-wide' ); 
		if( $thumbnail ) $img = $thumbnail[0];
		
		// Check for a series image
		$series = get_the_terms( $post_id, 'ctc_sermon_series' );
		if( $series && ! is_wp_error( $series) ) {
			$series = array_shift( array_values ( $series ) );
			if ( get_option( 'ctc_tax_img_' . $series->term_id ) )
				$img = get_option( 'ctc_tax_img_' . $series->term_id );
		}
		if( $img ) update_post_meta( $post_id, '_ctc_image', $img );
	}
	
	// Apply an image to a ctc_event post as meta data. 
	// A default map is used, followed by an image given through the 
	// ctc_event_image filter, then an image attached to the post directly
	function save_event_image( $post_id ){
		// Check for an event address image
		$address = get_post_meta( $post_id, '_ctc_event_address' , true ); 
		$address_url = urlencode( 'New York' );
		if( $address )  $address_url = urlencode( $address ); 
		$map_img_url = "https://maps.googleapis.com/maps/api/staticmap?size=640x360&zoom=15&scale=2&center=$address_url&style=saturation:-25&markers=color:orange|$address_url";
		$img = $map_img_url;
		
		$img = apply_filters( 'ctc_event_image', $img );
		$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'ctc-wide' ); 
		
		if( $thumbnail ) $img = $thumbnail[0];
		
		if( $img ) update_post_meta( $post_id, '_ctc_image', $img );
	}
	
	// Apply an image to a ctc_location post as meta data. 
	// A default map is used, followed by an image given through the 
	// ctc_location_image filter, then an image attached to the post directly
	function save_location_image( $post_id ){
		// Check for an event address image
		$address = get_post_meta( $post_id, '_ctc_location_address' , true ); 
		$address_url = urlencode(  'Albuquerque' );
		if( $address )  $address_url = urlencode( $address ); 
		$map_img_url = "https://maps.googleapis.com/maps/api/staticmap?size=640x360&zoom=15&scale=2&center=$address_url&style=saturation:-25&markers=color:orange|$address_url";
		$img = $map_img_url;
		
		$img = apply_filters( 'ctc_location_image', $img );
		
		$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'ctc-wide' ); 
		
		if( $thumbnail ) $img = $thumbnail[0];
		
		if( $img ) update_post_meta( $post_id, '_ctc_image', $img );
	}
	
	// Apply an image to a ctc_person post.
	// Order is plugin folder, an image given through the ctc_person_image filter, or
	// an image attached to the person post
	function save_person_image( $post_id ){
		$img = plugin_dir_url( __FILE__ ) . 'user.png';
		$img = apply_filters( 'ctc_person_image', $img );
		$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'ctc-tall' ); 
		if( $thumbnail ) $img = $thumbnail[0];
		
		update_post_meta( $post_id, '_ctc_image', $img );
	}
	
	
/********************************************		
	CTC new Event features
*********************************************/		
// New event metaboxes
	function metabox_filter_event_date( $meta_box ) {
		// With the exception of daily recurrence, the other settings 
		// are included in the CTC plugin, but are not exposed by default. 
		
		// Add daily recurrence 
		$options = $meta_box['fields']['_ctc_event_recurrence']['options'];
		$meta_box['fields']['_ctc_event_recurrence']['options'] = ctc_array_merge_after_key(
			$options, 
			array( 'daily' => _x( 'Daily', 'event meta box', 'ctcex' ) ),
			'none'	
		);
		
		// Add recurrence period
		$recurrence_period = array(
			'name'	=> __( 'Recurrence Period', 'ctcex' ),
			'after_name'	=> '',
			'after_input'	=> '',
			'desc'	=> _x( 'Recur every N days/weeks/months/years', 'event meta box', 'ctcex' ),
			'type'	=> 'select', 
			'options'	=> array_combine( range(1,12), range(1,12) ) ,
			'default'	=> '1', 
			'no_empty'	=> true, 
			'allow_html'	=> false, 
			'visibility' 		=> array( 
				'_ctc_event_recurrence' => array( 'none', '!=' ),
			)
		);
		$meta_box['fields'] = ctc_array_merge_after_key(
			$meta_box['fields'], 
			array( '_ctc_event_recurrence_period' => $recurrence_period ),
			'_ctc_event_recurrence'	
		);
		
		// Add recurrence monthly type
		$recurrence_monthly_type = array(
			'name'	=> __( 'Monthly Recurrence Type', 'ctcex' ),
			'desc'	=> '',
			'type'	=> 'radio', 
			'options'	=> array( 
				'day'   => _x( 'On the same day of the month', 'monthly recurrence type', 'ctcex' ),
				'week'  => _x( 'On a specific week of the month', 'monthly recurrence type','ctcex' ),
			),
			'default'	=> 'day', 
			'no_empty'	=> true, 
			'allow_html'	=> false, 
			'visibility' 		=> array( 
				'_ctc_event_recurrence' => 'monthly',
			)
		);
		$meta_box['fields'] = ctc_array_merge_after_key(
			$meta_box['fields'], 
			array( '_ctc_event_recurrence_monthly_type' => $recurrence_monthly_type ),
			'_ctc_event_recurrence_period'	
		);
		
		// Add recurrence monthly week
		$recurrence_monthly_week = array(
			'name'	=> __( 'Monthly Recurrence Week', 'ctcex' ),
			'desc'	=> _x( 'Day of the week is the same as Start Date', 'event meta box', 'ctcex' ),
			'type'	=> 'select', 
			'options'	=> array( 
				'1' 		=> 'First Week',
				'2' 		=> 'Second Week',
				'3'		 	=> 'Third Week',
				'4' 		=> 'Fourth Week',
				'last' 	=> 'Last Week',
			) ,
			'default'	=> '', 
			'no_empty'	=> true, 
			'custom_field'	=> '', 
			'visibility' 		=> array( 
				'_ctc_event_recurrence_monthly_type' => 'week',
			)
		);
		$meta_box['fields'] = ctc_array_merge_after_key(
			$meta_box['fields'], 
			array( '_ctc_event_recurrence_monthly_week' => $recurrence_monthly_week ),
			'_ctc_event_recurrence_monthly_type'	
		);
		
		return $meta_box;
	}

	// Update the recurrence note on the Events listing
	function column_recurrence_note( $recurrence_note, $args ){
		extract( $args );
		return $this->get_recurrence_note( $post );
	}

	// This helper is used to get an expression for recurrence
	function get_recurrence_note( $post_obj ) {
		if( !isset( $post_obj ) )
			global $post;
		else
			$post = $post_obj;
		
		$start_date = trim( get_post_meta( $post->ID , '_ctc_event_start_date' , true ) );
		$recurrence = get_post_meta( $post->ID , '_ctc_event_recurrence' , true );
		if( $recurrence == 'none' ) return '';
		
		$recurrence_period = get_post_meta( $post->ID , '_ctc_event_recurrence_period' , true );
		$recurrence_monthly_type = get_post_meta( $post->ID , '_ctc_event_recurrence_monthly_type' , true );
		$recurrence_monthly_week = get_post_meta( $post->ID , '_ctc_event_recurrence_monthly_week' , true );
		$recurrence_note = '';
		
		// Frequency
		switch ( $recurrence ) {

			case 'daily' :
				$recurrence_note = sprintf( 
					_n( 'Every day','Every %d days', (int)$recurrence_period, 'ctcex' ), 
					(int)$recurrence_period 
				);
				break;
				
			case 'weekly' :
				$recurrence_note = sprintf( 
					_n( 'Every %s', '%ss every %d weeks', (int)$recurrence_period, 'ctcex' ), date_i18n( 'l' , strtotime( $start_date ) ),
					(int)$recurrence_period 
				);
				break;

			case 'monthly' :
				$recurrence_note = sprintf( 
					_n( 'Every month','Every %d months', (int)$recurrence_period, 'ctcex' ), 
					(int)$recurrence_period 
				);
				break;

			case 'yearly' :
				$recurrence_note = sprintf( 
					_n( 'Every year','Every %d years', (int)$recurrence_period, 'ctcex' ), 
					(int)$recurrence_period 
				);
				break;

		}
		
		if( 'monthly' == $recurrence && $recurrence_monthly_type && $recurrence_monthly_week ) {
			if( 'day' == $recurrence_monthly_type ) {
				$recurrence_note .= sprintf( _x(' on the %s', 'date expression', 'ctcex'), date_i18n( 'jS' , strtotime( $start_date ) ) );
			} else {
				$ends = array( '1' => '1st', '2' => '2nd', '3' => '3rd', '4' => '4th' );
				if( $recurrence_monthly_week != 'last' )
					$recurrence_monthly_week = $ends[ $recurrence_monthly_week ];
				$recurrence_note .= sprintf( _x(' on the %s %s', 'date expression', 'ctcex'), $recurrence_monthly_week, date_i18n( 'l' , strtotime( $start_date ) ) );
			}
		}
		
		return $recurrence_note;
	}

	// Update recurring event dates. This overrides the function provided 
	// by CTC plugin to allow daily recurrence, and custom recurrence periods
	// Unfortunately, CTC plugin does not provide a filter, so this is a rewrite
	// of the original function. It could be filtered if the query allowed additional
	// values in the recurrence
	function update_recurring_event_dates() {
		if( ! class_exists( 'CTCEX_Recurrence' ) ) 
			require_once( sprintf( "%s/ctcex-recurrence-class.php", dirname(__FILE__) ) );
		
		if( ! class_exists( 'CTCEX_Recurrence' ) ) return;
			
		// Get all events with end date in past and have valid recurring value
		$events_query = new WP_Query( array(
			'post_type'	=> 'ctc_event',
			'nopaging'	=> true,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => '_ctc_event_end_date',
					'type' => 'DATE',
					'compare' => '<=', // earlier than 
					'value' => date_i18n( 'Y-m-d' ), // today localized
				 ),
				/*array(
					'key' => '_ctc_event_recurrence',
					'value' => 'none', 
					'compare' => '!=',
				 )*/
			)
		) );

		// Loop events
		if ( ! empty( $events_query->posts ) ) {
			// Instantiate recurrence class
			$ctc_recurrence = new CTCEX_Recurrence(); // CHANGE: Use extended class

			// Loop events to modify dates
			foreach ( $events_query->posts as $post ) {

				// Get start and end date
				$start_date = get_post_meta( $post->ID, '_ctc_event_start_date', true );
				$end_date = get_post_meta( $post->ID, '_ctc_event_end_date', true );

				// Get recurrence
				$recurrence = get_post_meta( $post->ID, '_ctc_event_recurrence', true );
				if( 'none' == $recurrence ) continue;
				
				$recurrence_end_date = get_post_meta( $post->ID, '_ctc_event_recurrence_end_date', true );
				
				// CHANGE: New recurrence parameters
				$recurrence_period = get_post_meta( $post->ID, '_ctc_event_recurrence_period', true );
				$recurrence_monthly_type = get_post_meta( $post->ID, '_ctc_event_recurrence_monthly_type', true );
				$recurrence_monthly_week = get_post_meta( $post->ID, '_ctc_event_recurrence_monthly_week', true );

				// Difference between start and end date in seconds
				$time_difference = strtotime( $end_date ) - strtotime( $start_date );

				// Get soonest occurrence that is today or later
				$args = array(
					'start_date'     => $start_date, // first day of event, YYYY-mm-dd (ie. 2015-07-20 for July 15, 2015)
					'frequency'      => $recurrence, // daily, weekly, monthly, yearly
					'interval'       => $recurrence_period,        // CHANGE: New
					'monthly_type'   => $recurrence_monthly_type,  // CHANGE: New
					'monthly_week'   => $recurrence_monthly_week,  // CHANGE: New
				);
				$args = apply_filters( 'ctc_event_recurrence_args', $args, $post ); // Custom Recurring Events add-on uses this
				$new_start_date = $ctc_recurrence->calc_next_future_date( $args );

				// If no new start date gotten, set it to current start date
				// This could be because recurrence ended, arguments are invalid, etc.
				if ( ! $new_start_date ) {
					$new_start_date = $start_date;
				}

				// Add difference between original start/end date to new start date to get new end date
				$new_end_date = date( 'Y-m-d', ( strtotime( $new_start_date ) + $time_difference ) );

				// Has recurrence ended?
				// Recurrence end date exists and is earlier than new start date
				if ( $recurrence_end_date && strtotime( $recurrence_end_date ) < strtotime( $new_start_date ) ) {

					// Unset recurrence option to keep dates from being moved forward
					update_post_meta( $post->ID, '_ctc_event_recurrence', 'none' );

				}

				// No recurrence or recurrence end date is still future
				else {

					// Update start and end dates
					update_post_meta( $post->ID, '_ctc_event_start_date', $new_start_date );
					update_post_meta( $post->ID, '_ctc_event_end_date', $new_end_date );

					// Update the hidden datetime fields for ordering
					ctc_update_event_date_time( $post->ID );

				}

			}

		} 

	}
	
	
	
	
} 



