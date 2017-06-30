<?php
/*
		Class to add shortcode for displaying the upcoming events
*/
// No direct access
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'CTCEX_Events' ) ) {
	
	class CTCEX_Events {
		
		public $version = '1.2';
		
		function __construct() {
			
			// Church Theme Content is REQUIRED
			if ( ! class_exists( 'Church_Theme_Content' ) ) return;
			
			add_shortcode( 'ctcex_events', array( $this, 'shortcode' ) );
			add_shortcode( 'ctcex_events_list', array( $this, 'list_shortcode' ) );
			
		}
		
		/*
		 * Slider view shortcode
		 *
		 * Usage: 
		 * [ctcex_events] 
		 *   Optional parameters:
		 *     category   = (string) Event categories to display
		 *     max_events = (number) Maximum number of events to display
		 *     glyph      = (string) Glyph font to use in display. 'fa' for font-awesome; 'gi' for genericons
		 *
		 * @since 1.0
		 * @param  string $attr         Shortcode options
		 * @return string               Slider
		 */
		function shortcode( $attr ) {
			
			extract( shortcode_atts( array(
				'category' 	=>  '',  
				'max_events'=>  5,
				'slides' => 1,
				'glyph' => 'fa',
				), $attr ) );
				
			$event_data = $this->get_query( $category, $max_events );
			
			// Filter the output of the whole shortcode
			// Note: This filters the whole shortcode. Any styles and scripts needed by the 
			//       new ouput will have to be included in the filtering function
			// Use: add_filter( 'ctcex_eventslist_shortcode', '<<<callback>>>', 10, 4 ); 
			// Args: first argument is the output to filter; empty
			//       <event_data> is the data extracted from event query; array of arrays
			//       <glyph> is the glyph indicated in the shortcode
			//       <slides> is the number of slides indicated in the shortcode
			$output = apply_filters( 'ctcex_events_shortcode', '', $event_data, $glyph, $slides );
			
			if( empty( $output) )
				$output = $this->get_output( $event_data, $glyph, $slides );
			
			return $output; 
			
		}
		
		/**
		 * Enqueue scripts and style for slider display
		 *
		 * @since  1.2
		 * @param  integer  $slides     Slides to display by default
		 */
		function slider_scripts( $slides = 1 ){
			wp_enqueue_script( 'slick', 
				'//cdn.jsdelivr.net/jquery.slick/1.6.0/slick.min.js', array( 'jquery' ) );
			wp_enqueue_script( 'ctcex-events-js', 
				plugins_url( 'js/ctcex-events.js' , __FILE__ ), array( 'jquery', 'slick' ) );
			wp_enqueue_style( 'slick-css', 
				'//cdn.jsdelivr.net/jquery.slick/1.6.0/slick.css' );
			wp_enqueue_style( 'ctcex-styles', 
				plugins_url( 'css/ctcex-styles.css' , __FILE__ ) );
			wp_localize_script( 'ctcex-events-js', 'ctcex_events', array( 'slides' => $slides ) );
		}
		
		/**
		 * Enqueue scripts and styles for list display
		 *
		 * @since  1.1
		 */
		function list_scripts(){
			wp_enqueue_script( 'ctcex-events-js', 
				plugins_url( 'js/ctcex-events.js' , __FILE__ ), array( 'jquery', 'slick' ) );
			wp_enqueue_style( 'ctcex-styles', 
				plugins_url( 'css/ctcex-styles.css' , __FILE__ ) );
		}
		
		/*
		 * List view shortcode for upcoming events
		 *
		 * Usage: 
		 * [ctcex_events_list] 
		 *   Optional parameters:
		 *     category   = (string) Event categories to display
		 *     max_events = (number) Maximum number of events to display
		 *     glyph      = (string) Glyph font to use in display. 'fa' for font-awesome; 'gi' for genericons
		 *
		 * @since   1.1
		 * @param   string $attr        Shortcode options
		 * @return  string              Events list
		 */
		function list_shortcode( $attr ) {
			
			extract( shortcode_atts( array(
				'category' 	  =>  '',  
				'max_events'  =>  -1, // default to all
				'notfound'    =>  false, // show a message when no events are found
				), $attr ) );
				
			$event_data = $this->get_query( $category, $max_events );	
			
			// Filter the output of the whole shortcode
			// Note: This filters the whole shortcode. Any styles and scripts needed by the 
			//       new ouput will have to be included in the filtering function
			// Use: add_filter( 'ctcex_eventslist_shortcode', '<<<callback>>>', 10, 4 ); 
			// Args: first argument is the output to filter; empty
			//       <event_data> is the data extracted from event query; array of arrays
			//       <notfound> flag to display a message if nothing is found
			$output = apply_filters( 'ctcex_eventslist_shortcode', '', $event_data, $notfound );
			
			if( empty( $output) )
				$output = $this->get_list_output( $event_data, $notfound );
			
			return $output;
			
		}
		
		
		/**
		 * Perform event query
		 *
		 * @since  1.2
		 * @param  string  $category    Event category to query (slug)
		 * @param  integer $max_events  Maximum number of events to fetch
		 * @return mixed                Array of event data  
		 */
		function get_query( $category, $max_events ){
			
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
			
			$posts = new WP_Query( $query );
			$data = array();
			if( $posts->have_posts() ):
				while ( $posts->have_posts() ) :
				
					$data[] = ctcex_get_sermon_data( get_the_ID() );
					
				endwhile;
			endif;
			
			wp_reset_query();
			
			return $data;
			
		}
		
		/**
		 * Generate output to display
		 *
		 * @since  1.2
		 * @param  mixed   $event_data Event data returned from get_query
		 * @param  string  $glyph      'fa' or 'gi' to use fontawesome or genericons
		 * @param  integer $slides     Maximum number of events to fetch
		 * @return string              Shortcode output
		 */
		function get_output( $event_data, $glyph ='fa', $slides ){
			
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
			
			$this->slider_scripts( $slides );
			
			$output = '<div id="ctcex-events" class="ctcex-events-list ctcex-slider ctcex-hidden">';
			
			foreach( $event_data as $data ){
				
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
				$location_txt = $data[ 'venue' ] ? $data[ 'venue' ] : $data[ 'address' ];
				$location_src = '';
				if( $location_txt ) {
					$location_src = sprintf( 
						'<div class="%s"><i class="%s %s"></i> %s</div>', 
						$classes[ 'location' ], 
						$glyph === 'gi' ? 'genericon' : 'fa', 
						$glyph === 'gi' ? 'genericon-location' : 'fa-map-marker', 
						$location_txt );
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
						<img class="%s" src="%s" alt="%s" width="960"/>
					%s', 
					$data[ 'map_used' ] ? '<a href="' . $data[ 'map_url' ] . '" target="_blank">' : '',
					$classes[ 'img' ], 
					$data[ 'img' ], 
					$data[ 'name' ],
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
					$data[ 'permalink' ],
					$data[ 'name' ],
					$date_src,
					$time_src,
					$location_src,
					$categories_src
				);
				
				$output .= $item_output;
				
			}
			
			$output .= '</div>';
			
			return $output; 
			
		}
		
		/**
		 * Generate list output to display
		 *
		 * @since  1.2
		 * @param  mixed   $event_data Event data returned from get_query
		 * @param  bool    $notfound   Flag for displaying a notice if there are no events
		 * @return string              Shortcode output
		 */
		function get_list_output( $event_data, $notfound ){
			
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
			
			if( empty( $event_data ) && $notfound ){
				
				$output = sprintf('<table id="ctcex-events_list" class="ctcex-events-list"><tr><td><p class="%s">%s</p></td></tr></table>',
						$classes[ 'noevents' ],
						__( 'Sorry no events of this category were found', 'ctcex' )
					);
				
				return $output;
			}
			
			$this->list_scripts();
			
			$output = '<table id="ctcex-events_list" class="ctcex-events-list"><thead><tr><th>Event</th><th>When?</th><th>Where?</th></tr></thead><tbody>';
			
			foreach( $event_data as $data ){
				
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
				$location_txt = $data[ 'venue' ] ? $data[ 'venue' ] : $data[ 'address' ];
				$location_src = sprintf( 
					'<td class="%s" data-th="Where?">%s</td>', 
					$classes[ 'location' ], 
					$location_txt ? $location_txt : '' 
				);
								
				// Prepare output
				$item_output = sprintf(
					'<tr class="%s">
						<td class="%s" data-th="Event"><a href="%s">%s</a></td>
						%s
						%s
					</tr>
					', 
					$classes[ 'container' ],
					$classes[ 'title' ],
					$data[ 'permalink' ],
					$data[ 'name' ],
					$date_src,
					$location_src
				);
				
				$output .= $item_output;
				
			}
			
			$output .= '</tbody></table>';
			
			return $output;
			
		}
		
	}
}

		
