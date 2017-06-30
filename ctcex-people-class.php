<?php
/*
		Class to add shortcode for displaying the CTC people
*/
// No direct access
if ( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists( 'CTCEX_People' ) ) {
	
	class CTCEX_People {
		
		public $version = '1.1'; 
		
		function __construct() {
			
			// Church Theme Content is REQUIRED
			if ( ! class_exists( 'Church_Theme_Content' ) ) return;
			
			add_shortcode( 'ctcex_people', array( $this, 'shortcode' ) );
			
		}
		
		/*
		 * People shortcode 
		 *
		 * Usage: [ctc_people] 
		 *   Optional parameters:
		 *     group  = (string)  Slug of people group
		 *     slider = (boolean) Show the people as a slider; default is false and the people are displayed as a grid
		 * 
		 * @since 1.0
		 * @param  string  $attr        Shortcode options
		 * @return string               Full people display
		 */
		function shortcode( $attr ) {
			
			extract( shortcode_atts( array(
				'group' 	=>  '',  
				'slider' 	=>  false, 
				'glyph'   =>  'fa', // either fa for fontawesome or gi for genericons
				), $attr ) );
			
			$people_data = $this->get_query( $group );
			
			// Filter the output of the whole shortcode
			// Note: This filters the whole shortcode. Any styles and scripts needed by the 
			//       new ouput will have to be included in the filtering function
			// Use: add_filter( 'ctcex_people_shortcode', '<<<callback>>>', 10, 4 ); 
			// Args: first argument is the output to filter; empty
			//       <people_data> is the data extracted from people query; array of arrays
			//       <glyph> is the glyph indicated in the shortcode
			//       <slider> flag to display people in slider format 
			$output = apply_filters( 'ctcex_people_shortcode', '', $people_data, $glyph, $slider );
			
			if( empty( $output) )
				$output = $this->get_output( $people_data, $glyph, $slider );
			
			return $output; 
			
		}

		/**
		 * Enqueue scripts and styles for display
		 *
		 * @since  1.1
		 */
		function scripts( $slider ){
			
			if( $slider ) {
				wp_enqueue_script( 'slick', 
					'//cdn.jsdelivr.net/jquery.slick/1.6.0/slick.min.js', array( 'jquery' ) );
				wp_enqueue_style( 'slick-css', 
					'//cdn.jsdelivr.net/jquery.slick/1.6.0/slick.css' );
				wp_enqueue_script( 'ctcex-people-js', 
					plugins_url( 'js/ctcex-people.js' , __FILE__ ), array( 'jquery', 'slick' ) );
			} 
			wp_enqueue_style( 'ctcex-styles', 
				plugins_url( 'css/ctcex-styles.css' , __FILE__ ) );
		}

		/**
		 * Perform people query
		 *
		 * @since  1.1
		 * @param  string  $group       People topic to query (slug)
		 * @return mixed                Array of people data  
		 */
		function get_query( $group){
			
			// do query 
			$query = array(
				'post_type' 				=> 'ctc_person', 
				'order' 						=> 'ASC',
				'orderby' 					=> 'menu_order',
				'posts_per_page'		=> -1,
			); 
			
			if( !empty( $group ) )  {
				$query[ 'tax_query' ] = array( 
					array(
						'taxonomy'  => 'ctc_person_group',
						'field'     => 'slug',
						'terms'     => $group,
					),
				);
			}
			
			$posts = new WP_Query( $query );		
			if( $posts->have_posts() ):
				while ( $posts->have_posts() ) :
				
					$data[] = ctcex_get_person_data( get_the_ID() );
					
				endwhile;
			endif;
			
			wp_reset_query();
			
			return $data;
			
		}
		
		/**
		 * Generate output to display
		 *
		 * @since  1.1
		 * @param  mixed   $people_data  Sermon data returned from get_query
		 * @param  string  $glyph        'fa' or 'gi' to use fontawesome or genericons
		 * @param  bool    $slider       Flag to display people in slider or grid format
		 * @return string                Shortcode output
		 */
		function get_output( $people_data, $glyph, $slider ){
			
			// classes
			$classes = array(
				'container'  => 'ctcex-person-container',
				'details'    => 'ctcex-person-details',
				'title'      => 'ctcex-person-title',
				'position'   => 'ctcex-person-position',
				'email'      => 'ctcex-person-email',
				'urls'       => 'ctcex-person-urls',
				'img'        => 'ctcex-person-img'
			);
			
			// Filter the classes only instead of the whole shortcode
			// Use: add_filter( 'ctcex_person_classes', '<<<callback>>>' ); 
			$classes = apply_filters( 'ctcex_person_classes', $classes );
			
			$this -> scripts( $slider );
			
			$output = sprintf( '<div id="ctcex-people" class="ctcex-people-list %s">', $slider ? 'ctcex-slider ctcex-hidden' : 'no-slider' );
				
			foreach( $people_data as $data ){
				
				// Add the email to the url list and prep the urls
				$urls = explode( "\r\n", $data[ 'url' ] );
				if( $data[ 'email' ] )
					$urls[] = 'mailto:' . $data[ 'email' ];
				$url_src = sprintf( '<div class="%s %s ctcex-socials"><ul>', $classes[ 'urls' ], $glyph === 'gi' ? 'gi' : 'fa' );
				foreach( $urls as $url_item ){
					$url_src .= sprintf( '<li><a href="%s">%s</a></li>', $url_item, $url_item );
				}
				$url_src .= '</ul></div>';
				
				$position_src = $data[ 'position' ] ? sprintf( '<h3 class="%s">%s</h3>', $classes[ 'position' ], $data[ 'position' ] ) : '';
				
				// Get image
				$img_src = $data[ 'img' ] ? sprintf( '<img class="%s" src="%s" alt="%s" width="200"/>', $classes[ 'img' ], $data[ 'img' ], $title ) : '';
				
				// Prepare output
				$item_output = sprintf(
					'<div class="%s">
						%s
						<div class="%s">
							<h2 class="%s">%s</h2>
							%s
							%s
						</div>
					</div>
					', 
					$classes[ 'container' ],
					$img_src,
					$classes[ 'details' ],
					$classes[ 'title' ],
					$data[ 'name' ],
					$position_src,
					$url_src
				);
				
				$output .= $item_output;
			
			}
		
			$output .= '</div>';
			
			return $output;
			
		}
		
	}

}
		
