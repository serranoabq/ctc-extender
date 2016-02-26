<?php
/*
		Class to add shortcode for displaying the upcoming events
*/
// No direct access
if ( ! defined( 'ABSPATH' ) ) exit;

class CTCEX_Events {
	
	function __construct() {
		$this->version = '1.0'; 
		
		// Church Theme Content is REQUIRED
		if ( ! class_exists( 'Church_Theme_Content' ) ) return;
		
		add_shortcode( 'ctcex_events', array( $this, 'shortcode' ) );
	}
	
	/*
	 * Usage: [ctc_sermon] 
	 *   Optional parameters:
	 *     topic = (string)
	 *       Slug of sermon topic to show
	 * @since 1.0
	 * Parse shortcode and insert recent sermon
	 * @param string $attr Shortcode options
	 * @return string Full sermon display
	 */
	function shortcode( $attr ) {
		// Filter to bypass this shortcode with a theme-designed shortcode handler
		// Use: add_filter( 'ctcex_events', '<<<callback>>>', 10, 2 ); 
		$output = apply_filters( 'ctcex_events', '', $attr );
	
		if ( $output != '' ) return $output;
		
		extract( shortcode_atts( array(
			'category' 	=>  '',  
			'max_events'=>  5,
			'glyph' => 'fa',
			), $attr ) );
			
		$this->scripts();
		
		// do query 
		$query = array(
			'post_type'      => 'ctc_event', 
			'order'          => 'ASC',
			'orderby'        => 'meta_value',
			'meta_key'       => '_ctc_event_start_date_start_time',
			'meta_type'      => 'DATETIME',
			'posts_per_page' => $max_events, 
			'meta_query'     => array(
				array(
					'key'     => '_ctc_event_end_date',
					'value'   => date_i18n( 'Y-m-d' ), // today localized
					'compare' => '>=', // later than today
					'type'    => 'DATE',
				),
			), 
		); 
		
		if( !empty( $category ) )  {
			$query[ 'tax_query' ] = array( 
				array(
					'taxonomy'  => 'ctc_event_category',
					'field'     => 'slug',
					'terms'     => $category,
				),
			);
		}
		
		// classes
		$classes = array(
			'container'  => 'ctcex-events-container',
			'media'      => 'ctcex-event-media',
			'details'    => 'ctcex-event-details',
			'date'       => 'ctcex-event-date',
			'time'       => 'ctcex-event-time',
			'location'   => 'ctcex-event-location',
			'categories' => 'ctcex-event-categories',
			'img'        => 'ctcex-event-img'
		);
		
		// Filter the classes only instead of the whole shortcode
		// Use: add_filter( 'ctcex_events_classes', '<<<callback>>>' ); 
		$classes = apply_filters( 'ctcex_events_classes', $classes );
		

		$posts = new WP_Query( $query );		
		if( $posts->have_posts() ){
			$output = '<div id="ctcex-events" class="ctcex-events-list ctcex-slider ctcex-hidden">';
			while ( $posts->have_posts() ) :
				$posts		-> the_post();
				$post_id 	= get_the_ID();
				$title 		= get_the_title() ;
				$url 			= get_permalink();
				$data = ctcex_get_event_data( $post_id );

				// Event date
				$date_str = sprintf( '%s%s',  date_i18n( 'l, F j', strtotime( $data[ 'start' ] ) ), $data[ 'start' ] != $data[ 'end' ] ? ' - '. date_i18n( 'l, F j', strtotime( $data[ 'end' ] ) ) : '' );
				$date_src = sprintf( '<div class="%s"><i class="%s calendar"></i> %s</div>', $classes[ 'date' ], $glyph === 'gi' ? 'gi' : 'fa', $date_str );
				
				// Event time
				$time_str = sprintf( '%s%s',  $data[ 'time' ], $data[ 'endtime' ] ? ' - '. $data[ 'endtime' ] : '' );
				$time_src = sprintf( '<div class="%s"><i class="%s clock"></i> %s</div>', $classes[ 'time' ], $glyph === 'gi' ? 'gi' : 'fa', $time_str );
				
				// Event location
				$location_src = sprintf( '<div class="%s"><i class="%s location"></i> %s</div>', $classes[ 'location' ], $glyph === 'gi' ? 'gi' : 'fa', $data[ 'address' ] );
				
				// Event categories
				$categories_src = sprintf( '<div class="%s"><i class="%s tag"></i> %s</div>', $classes[ 'location' ], $glyph === 'gi' ? 'gi' : 'fa', $data[ 'categories' ] );
				
				// Get image
				$img_src = $data[ 'img' ] ? sprintf( 
					'%s
						<img class="%s" src="%s" alt="%s"/>
					%s', 
					$data[ 'map_used' ] ? '<a href="' . $data[ 'map_url' ] . '" target="_blank">' : '',
					$classes[ 'img' ], 
					$data[ 'img' ], 
					get_the_title(),
					'</a>' 
				) : '' ;
				
				// Prepare output
				$item_output = sprintf(
					'<div class="%s">
						<div class="%s">%s</div>
						<div class="%s">
							<h3><a href="%s">%s</a></h3>
							%s
							%s
							%s
							%s
						</div>
					</div>
					', 
					$classes[ 'container' ],
					$classes[ 'media' ],
					$img_src,
					$classes[ 'details' ],
					$url,
					$title,
					$date_src,
					$time_src,
					$location_src,
					$categories_src
				);
				// Filter the output only instead of the whole shortcode
				// Use: add_filter( 'ctcex_events_output', '<<<callback>>>', 10, 3 ); 
				//  Args: item_output is the output to filter
				//        category is the category passed on to the shortcode
				//        data is the sermon data
				$item_output = apply_filters( 'ctcex_event_output', $item_output, $category, $data );
				$output .= $item_output;
			endwhile; 
		}
		wp_reset_query();
		$output .= '</div>';
		
		$output = apply_filters( 'ctcex_events_output', $output, $category );
		
		echo $output;
	}
	function scripts(){
		wp_enqueue_script( 'slick', 
			'//cdn.jsdelivr.net/jquery.slick/1.5.9/slick.min.js', array( 'jquery' ) );
		wp_enqueue_style( 'slick-css', 
			'//cdn.jsdelivr.net/jquery.slick/1.5.9/slick.css' );
		wp_enqueue_script( 'ctcex-events-js', 
			plugins_url( 'js/ctcex-events.js' , __FILE__ ), array( 'jquery', 'slick' ) );
	}
}


	