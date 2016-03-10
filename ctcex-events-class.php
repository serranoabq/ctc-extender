<?php
/*
		Class to add shortcode for displaying the upcoming events
*/
// No direct access
if ( ! defined( 'ABSPATH' ) ) exit;

class CTCEX_Events {
	
	function __construct() {
		$this->version = '1.1'; 
		
		// Church Theme Content is REQUIRED
		if ( ! class_exists( 'Church_Theme_Content' ) ) return;
		
		add_shortcode( 'ctcex_events', array( $this, 'shortcode' ) );
		add_shortcode( 'ctcex_events_list', array( $this, 'list_shortcode' ) );
	}
	
	/*
	 * Usage: [ctcex_events] 
	 *   Optional parameters:
	 *     category = (string)
	 *       Event categories to display
	 *     max_events = (number)
	 *       Maximum number of events to display
	 *     glyph = (string)
	 *       Glyph font to use in display. 'fa' for font-awesome; 'gi' for genericons
	 * @since 1.0
	 * Upcoming event slider
	 * @param string $attr Shortcode options
	 * @return string Slider with upcoming events
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
			
		$this->slider_scripts();
		
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
				$date_src = sprintf( 
					'<div class="%s"><i class="%s %s"></i> %s</div>', 
					$classes[ 'date' ], 
					$glyph === 'gi' ? 'genericon' : 'fa', 
					$glyph === 'gi' ? 'genericon-month' : 'fa-calendar', 
					$date_str );
				
				// Event time
				$time_str = sprintf( '%s%s',  $data[ 'time' ], $data[ 'endtime' ] ? ' - '. $data[ 'endtime' ] : '' );
				$time_src = '';
				if( $time_str ) {
					$time_src = sprintf( 
						'<div class="%s"><i class="%s %s"></i> %s</div>', 
						$classes[ 'time' ], 
						$glyph === 'gi' ? 'genericon' : 'fa', 
						$glyph === 'gi' ? 'genericon-time' : 'fa-clock-o', 
						$time_str );
				}
				
				// Event location
				$location_src = '';
				if( $data[ 'address' ] ) {
					$location_src = sprintf( 
						'<div class="%s"><i class="%s %s"></i> %s</div>', 
						$classes[ 'location' ], 
						$glyph === 'gi' ? 'genericon' : 'fa', 
						$glyph === 'gi' ? 'genericon-location' : 'fa-map-marker', 
						$data[ 'address' ] );
				}
				
				// Event categories
				$categories_src = '';
				if( $data[ 'categories' ] ) {
					$categories_src = sprintf( 
						'<div class="%s"><i class="%s %s-tag"></i> %s</div>', 
						$classes[ 'location' ], 
						$glyph === 'gi' ? 'genericon' : 'fa', 
						$glyph === 'gi' ? 'genericon' : 'fa', 
						$data[ 'categories' ] );
				}
				
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
				
				$edit_link = get_edit_post_link( $post_id, 'link' );
				$edit_link = $edit_link ? sprintf( '<a href="%s" class="alignright">%s</a>',
						$edit_link, 
						__( 'Edit event', 'ctcex' )
						) : '';
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
					$categories_src,
					$edit_link
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
	
	function slider_scripts(){
		wp_enqueue_script( 'slick', 
			'//cdn.jsdelivr.net/jquery.slick/1.5.9/slick.min.js', array( 'jquery' ) );
		wp_enqueue_script( 'ctcex-events-js', 
			plugins_url( 'js/ctcex-events.js' , __FILE__ ), array( 'jquery', 'slick' ) );
		wp_enqueue_style( 'slick-css', 
			'//cdn.jsdelivr.net/jquery.slick/1.5.9/slick.css' );
		wp_enqueue_style( 'ctcex-styles', 
			plugins_url( 'css/ctcex-styles.css' , __FILE__ ) );
	}
	
	function list_scripts(){
		wp_enqueue_script( 'ctcex-events-js', 
			plugins_url( 'js/ctcex-events.js' , __FILE__ ), array( 'jquery', 'slick' ) );
		wp_enqueue_style( 'ctcex-styles', 
			plugins_url( 'css/ctcex-styles.css' , __FILE__ ) );
	}
	
	/*
	 * Usage: [ctcex_events_list] 
	 *     category = (string)
	 *       Event categories to display
	 *     max_events = (number)
	 *       Maximum number of events to display
	 *     glyph = (string)
	 *       Glyph font to use in display. 'fa' for font-awesome; 'gi' for genericons
	 * @since 1.1
	 * Upcoming event list
	 * @param string $attr Shortcode options
	 * @return string Events list
	 */
	function list_shortcode( $attr ) {
		// Filter to bypass this shortcode with a theme-designed shortcode handler
		// Use: add_filter( 'ctcex_events_list', '<<<callback>>>', 10, 2 ); 
		$output = apply_filters( 'ctcex_events_list', '', $attr );
	
		if ( $output != '' ) return $output;
		
		extract( shortcode_atts( array(
			'category' 	  =>  '',  
			'max_events'  =>  -1, // default to all
			'notfound'    =>  false, // show a message when no events are found
			), $attr ) );
		$this->list_scripts();
		
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
			'container'  => 'ctcex-events-list-item',
			'title'      => 'ctcex-event-title',
			'date'       => 'ctcex-event-date',
			'location'   => 'ctcex-event-location',
			'noevents'   => 'ctcex-no-events',
		);
		
		// Filter the classes only instead of the whole shortcode
		// Use: add_filter( 'ctcex_events_classes', '<<<callback>>>' ); 
		$classes = apply_filters( 'ctcex_eventslist_classes', $classes );
		

		$posts = new WP_Query( $query );		
		if( $posts->have_posts() ){
			$output = '<table id="ctcex-events_list" class="ctcex-events-list"><thead><tr><th>Event</th><th>When?</th><th>Where?</th></tr></thead><tbody>';
			while ( $posts->have_posts() ) :
				$posts		-> the_post();
				$post_id 	= get_the_ID();
				$title 		= get_the_title() ;
				$url 			= get_permalink();
				$data = ctcex_get_event_data( $post_id );

				// Event date
				$date_str = sprintf( 
					'%s%s',  
					date_i18n( 'D, M j', strtotime( $data[ 'start' ] ) ),
					$data[ 'time' ] ? ' @ ' . $data[ 'time' ] : ''
				);
				$date_src = sprintf( 
					'<td class="%s" data-th="When?">%s</td>', 
					$classes[ 'date' ], 
					$date_str 
				);
				
				// Event location
				$location_src = sprintf( 
					'<td class="%s" data-th="Where?">%s</td>', 
					$classes[ 'location' ], 
					$data[ 'address' ] ? $data[ 'address' ] : '' 
				);
								
				$edit_link = get_edit_post_link( $post_id, 'link' );
				$edit_link = $edit_link ? sprintf( ' (<a href="%s">%s</a>)',
						$edit_link, 
						__( 'Edit event', 'ctcex' )
						) : '';
				// Prepare output
				$item_output = sprintf(
					'<tr class="%s">
						<td class="%s" data-th="Event"><a href="%s">%s</a>%s</td>
						%s
						%s
					</tr>
					', 
					$classes[ 'container' ],
					$classes[ 'title' ],
					$url,
					$title,
					$edit_link,
					$date_src,
					$location_src
				);
				// Filter the output only instead of the whole shortcode
				// Use: add_filter( 'ctcex_eventslist_item_output', '<<<callback>>>', 10, 3 ); 
				//  Args: item_output is the output to filter
				//        category is the category passed on to the shortcode
				//        data is the sermon data
				$item_output = apply_filters( 'ctcex_eventslist_item_output', $item_output, $category, $data );
				$output .= $item_output;
			endwhile; 
			$output .= '</tbody></table>';
		} else {
			if( $notfound ) {
				$output = sprintf('<table id="ctcex-events_list" class="ctcex-events-list"><tr><td><p class="%s">%s</p></td></tr></table>',
					$classes[ 'noevents' ],
					__( 'Sorry no events of this category were found', 'ctcex' )
				);
			}
		}
		wp_reset_query();
		
		$output = apply_filters( 'ctcex_eventslist_output', $output, $category );
		
		return $output;
	}
	
}


	