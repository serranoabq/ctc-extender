<?php
/*
		Class to add display the latest sermon
*/
// No direct access
if ( ! defined( 'ABSPATH' ) ) exit;

class CTCEX_Sermon {
	
	function __construct() {
		$this->version = '1.0'; 
		
		// Church Theme Content is REQUIRED
		if ( ! class_exists( 'Church_Theme_Content' ) ) return;
		
		add_shortcode( 'ctcex_sermon', array( $this, 'shortcode' ) );
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
		// Use: add_filter( 'ctcex_sermon', '<<<callback>>>', 10, 2 ); 
		$output = apply_filters( 'ctcex_sermon', '', $attr );
	
		if ( $output != '' ) return $output;
		
		extract( shortcode_atts( array(
			'topic' 	=>  '',  
			), $attr ) );
		
		// do query 
		$query = array(
			'post_type' 				=> 'ctc_sermon', 
			'order' 						=> 'DESC',
			'orderby' 					=> 'date',
			'posts_per_page'		=> 1,
		); 
		
		if( !empty( $topic ) )  {
			$query[ 'tax_query' ] = array( 
				array(
					'taxonomy'  => 'ctc_sermon_topic',
					'field'     => 'slug',
					'terms'     => $topic,
				),
			);
		}
		
		// classes
		$classes = array(
			'container'  => 'ctcex-sermon-container',
			'media'      => 'ctcex-sermon-media',
			'details'    => 'ctcex-sermon-details',
			'date'       => 'ctcex-sermon-date',
			'speaker'    => 'ctcex-sermon-speaker',
			'series'     => 'ctcex-sermon-series',
			'topic'      => 'ctcex-sermon-topic',
			'audio-link' => 'ctcex-sermon-audio-link',
			'audio'      => 'ctcex-sermon-audio',
			'video'      => 'ctcex-sermon-video',
			'img'        => 'ctcex-sermon-img'
		);
		
		// Filter the classes only instead of the whole shortcode
		// Use: add_filter( 'ctcex_sermon_classes', '<<<callback>>>' ); 
		$classes = apply_filters( 'ctcex_sermon_classes', $classes );
		

		$posts = new WP_Query( $query );		
		if( $posts->have_posts() ){
			while ( $posts->have_posts() ) :
				$posts		-> the_post();
				$post_id 	= get_the_ID();
				$title 		= get_the_title() ;
				$url 			= get_permalink();
				$data = ctcex_get_sermon_data( $post_id );
				
				// Get date
				$date_src = sprintf( '<div class="%s"><b>%s:</b> %s</div>', $classes[ 'date' ], __( 'Date', 'ctcex' ), get_the_date() );
				
				// Get speaker
				$speaker_src = $data[ 'speakers' ] ? sprintf( '<div class="%s"><b>%s:</b> %s</div>', $classes[ 'speaker' ], __( 'Speaker', 'ctcex' ), $data[ 'speakers' ] ) : '';
				
				// Get series
				$series_src = $data[ 'series' ] ?	sprintf( '<div class="%s"><b>%s:</b> <a href="%s">%s</a></div>', $classes[ 'series' ],  __( 'Series', 'ctcex' ), $data[ 'series_link' ], $data[ 'series' ] ) : '';
				
				// Get topics
				// Topic name
				$topic_name = explode( '/', ctcex_get_option( 'ctc-sermon-topic' , __( 'Topic', 'ctcex') ) );
				$topic_name = ucfirst( array_pop(  $topic_name ) );
				$topic_src = $data[ 'topic' ] ? sprintf( '<div class="%"><b>%s:</b> <a href="%s">%s</a></div>', $classes[ 'topic' ], $topic_name, $data[ 'topic_link' ], $data[ 'topic' ] ) : '';

				// Get audio link
				$audio_link_src = $data[ 'audio' ] ? sprintf( '<div class="%s"><b>%s:</b> <a href="%s">%s</a></div>', $classes[ 'audio-link' ], __( 'Audio', 'ctcex' ), $data[ 'audio' ], __( 'Download audio', 'ctcex' ) ) : '';
				
				// Get audio display
				$audio_src = $data[ 'audio' ] ? sprintf( '<div class="%s">%s</div>', $classes[ 'audio' ], wp_audio_shortcode( array( 'src' => $data[ 'audio' ] ) ) ) : '';
				
				// Get video display
				$video_iframe_class = strripos( $data[ 'video' ], 'iframe' ) ? 'iframe-container' : '';
				$video_src = $data[ 'video' ] ? sprintf( '<div class="%s %s">%s</div>', $classes[ 'video' ], $video_iframe_class, $video_iframe_class ? $data[ 'video' ] : wp_video_shortcode( array( 'src' => $data[ 'video' ] ) ) ) : '';
		
				// Use the image as a placeholder for the video
				$img_overlay_class = $data[ 'video' ] && $data[ 'img' ] ? 'ctcex-overlay' : '';
				$img_overlay_js = $img_overlay_class ? sprintf(
					'<script>
						jQuery(document).ready( function($) {
							$( ".%s" ).css( "position", "relative" );
							$( ".ctcex-overlay" ).css( "cursor", "pointer" );
							var vid_src = \'%s\';
							vid_src = vid_src.replace( "autoPlay=false", "autoPlay=true" );
							$( ".ctcex-overlay" ).click( function(){
								$( this ).hide();
								$( ".ctcex-overlay" ).fadeOut( 400, function() {
									$( this ).replaceWith( vid_src );
								});
							} );
						})
					</script>', 
					$classes[ 'media' ],
					$video_src ) : '' ;
					
				// Get image
				$img_src = $data[ 'img' ] ? sprintf( '%s<img class="%s %s" src="%s" alt="%s"/>', $img_overlay_js, $classes[ 'img' ], $img_overlay_class, $data[ 'img' ], get_the_title() ) : '';
				$video_src = $img_overlay_class ? $img_src : $video_src;
				
				$img_video_output = $video_src ? $video_src : $img_src . $audio_src;
				
				// Prepare output
				$output = sprintf(
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
					', 
					$classes[ 'container' ],
					$classes[ 'media' ],
					$img_video_output,
					$classes[ 'details' ],
					$url,
					$title,
					$date_src,
					$speaker_src,
					$series_src,
					$topic_src,
					$audio_link_src
				);
				
				// Filter the output only instead of the whole shortcode
				// Use: add_filter( 'ctcex_sermon_output', '<<<callback>>>', 10, 3 ); 
				//  Args: output is the output to filter
				//        topic is the topic passed on to the shortcode
				//        data is the sermon data
				$output = apply_filters( 'ctcex_sermon_output', $output, $topic, $data );
				
			endwhile; 
		}
		wp_reset_query();
		echo $output;
	}

}


	