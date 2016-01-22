<?php
/*
		Class to display the people in CTC through a shortcode
*/
// No direct access
if ( ! defined( 'ABSPATH' ) ) exit;

class CTCEX_People {
	
	function __construct() {
		$this->version = '1.0'; 
		
		// Church Theme Content is REQUIRED
		if ( ! class_exists( 'Church_Theme_Content' ) ) return;
		
		add_shortcode( 'ctcex_people', array( $this, 'shortcode' ) );
	}
	
	/*
	 * Usage: [ctc_people] 
	 *   Optional parameters:
	 *     group = (string)
	 *       Slug of people group
	 *     slider = (boolean)
	 *       Show the people as a slider; default is false and the people are displayed as a grid
	 * @since 1.0
	 * Parse shortcode and insert people
	 * @param string $attr Shortcode options
	 * @return string Full people display
	 */
	function shortcode( $attr ) {
		// Filter to bypass this shortcode with a theme-designed shortcode handler
		// Use: add_filter( 'ctcex_people', '<<<callback>>>', 10, 2); 
		$output = apply_filters( 'ctcex_people', '', $attr );
	
		if ( $output != '' ) return $output;
		
		extract( shortcode_atts( array(
			'group' 	=>  '',  
			'slider' 	=>  '',  
			), $attr ) );
		
		$this -> scripts( $slider );
		
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
		
		// classes
		$classes = array(
			'container'  => 'ctcex-people-container',
			'details'    => 'ctcex-person-details',
			'position'   => 'ctcex-person-position',
			'email'      => 'ctcex-person-email',
			'urls'       => 'ctcex-person-urls',
			'urls'       => 'ctcex-person-urls',
			'img'        => 'ctcex-person-img'
		);
		
		if( $slider ) 
			$classes[ 'container' ] .= ' ctcex_unslider';
		
		// Filter the classes only instead of the whole shortcode
		// Use: add_filter( 'ctcex_person_classes', '<<<callback>>>' ); 
		$classes = apply_filters( 'ctcex_person_classes', $classes );
		
		$posts = new WP_Query( $query );
		if( $posts->have_posts() ){
			$output = sprintf( '<div class="%s ctcex-container" style="overflow: hidden"><ul class="ctcex-people-list %s">', $classes[ 'container' ], $slider ? '' : 'no-slider' );
			
			while ( $posts->have_posts() ) :
				$posts		-> the_post();
				$post_id 	= get_the_ID();
				$title 		= get_the_title() ;
				$url 			= get_permalink();
				$data     = ctcex_get_person_data( $post_id );
				
				// Add the email to the url list and prep the urls
				$urls = explode( "\r\n", $data[ 'url' ] );
				if( $data[ 'email' ] )
					$urls[] = 'mailto:' . $data[ 'email' ];
				$url_src = sprintf( '<div class="%s ctcex-socials"><ul>', $classes[ 'urls' ] );
				foreach( $urls as $url_item ){
					$url_src .= sprintf( '<li><a href="%s">%s</a></li>', $url_item, $url_item );
				}
				$url_src .= '</ul></div>';
				
				$position_src = $data[ 'position' ] ? sprintf( '<h3 class="%s">%s</h3>', $classes[ 'position' ], $data[ 'position' ] ) : '';
				
				// Get image
				$img_src = $data[ 'img' ] ? sprintf( '<img class="%s" src="%s" alt="%s"/>', $classes[ 'img' ], $data[ 'img' ], $title ) : '';
				
				// Prepare output
				$item_output = sprintf(
					'<li class="%s">
						%s
						<div class="%s">
							<h2>%s</h2>
							%s
							%s
						</div>
					</li>
					', 
					$classes[ 'container' ],
					$img_src,
					$classes[ 'details' ],
					$title,
					$position_src,
					$url_src
				);
				
				// Filter the output only instead of the whole shortcode
				// Use: add_filter( 'ctcex_person_output', '<<<callback>>>', 10, 3 ); 
				//  Args: output is the output to filter
				//        topic is the topic passed on to the shortcode
				//        data is the person data
				$item_output = apply_filters( 'ctcex_person_output', $item_output, $group, $data );
				
				$output .= $item_output;
			endwhile; 
		}
		wp_reset_query();
		
		$output .= '</ul></div>';
		
		// Filter the final output only instead of the the individual person
		// Use: add_filter( 'ctcex_people_output', '<<<callback>>>', 10, 3 ); 
		//  Args: output is the output to filter
		//        group is the topic passed on to the shortcode
		//        data is the person data
		$output = apply_filters( 'ctcex_people_output', $output, $group, $slider );
		
		if( $slider ){
			$output .= '
					<script>
						jQuery(document).ready( function($) {
							$( ".ctcex-container" ).unslider();
						})
					</script>';
		}
		echo $output;
	}

	function scripts( $slider ){
		
		if( $slider ) {
			
		} 
		wp_enqueue_style( 'ctcex-people', 
			plugins_url( 'css/ctcex-people.css' , __FILE__ ) );
	}
}


	