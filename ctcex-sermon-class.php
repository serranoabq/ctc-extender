<?php
/*
		Class to add shortcode for displaying the latest sermon
*/
// No direct access
if ( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists( 'CTCEX_Sermon' ) ) {
	
	class CTCEX_Sermon {
		
		public $version = '1.11';
		
		function __construct() {
			
			// Church Theme Content is REQUIRED
			if ( ! class_exists( 'Church_Theme_Content' ) ) return;
			
			add_shortcode( 'ctcex_sermon', array( $this, 'shortcode' ) );
		}
		
		/*
		 * Recent sermon shortcode
		 *
		 * Usage: 
		 * [ctc_sermon] 
		 *   Optional parameters:
		 *     topic = (string) Slug of sermon topic to show
		 *
		 * @since 1.0
		 * @param  string  $attr          Shortcode options
		 * @return string                 Full sermon display
		 */
		function shortcode( $attr ) {
		
			extract( shortcode_atts( array(
				'topic' 	=>  '',
				'glyph'   =>  'fa', // either fa for fontawesome or gi for genericons			

				), $attr ) );
		
			$sermon_data = $this->get_query( $topic );
			
			// Filter the output only instead of the whole shortcode
			// Note: This filters the whole shortcode. Any styles and scripts needed by the 
			//       new ouput will have to be included in the filtering function
			// Use: add_filter( 'ctcex_sermon_output', '<<<callback>>>', 10, 3 ); 
			// Args: first argument is the output to filter; empty
			//       <sermon_data> is the data extracted from sermon query
			//       <glyph> is the glyph indicated in the shortcode
			$output = apply_filters( 'ctcex_sermon_shortcode', '', $sermon_data, $glyph );
		
			if( empty( $output ) ) 
				$output = $this->get_output( $sermon_data, $glyph );
			
			return $output;
			
		}

		/**
		 * Perform sermon query
		 *
		 * @since  1.1
		 * @param  string  $topic       Sermon topic to query (slug)
		 * @return mixed                Array of sermon data  
		 */
		function get_query( $topic = '' ){
			
			// do query 
			$query = array(
				'post_type' 				=> 'ctc_sermon', 
				'order' 						=> 'DESC',
				'orderby' 					=> 'date',
				'posts_per_page'		=> 1,
			); 
			
			$data = array();
			
			if( ! empty( $topic ) )  {
				$query[ 'tax_query' ] = array( 
					array(
						'taxonomy'  => 'ctc_sermon_topic',
						'field'     => 'slug',
						'terms'     => $topic,
					),
				);
			}
			
			$m_posts = new WP_Query( $query );		
			if( $m_posts->have_posts() ):
				while ( $m_posts->have_posts() ) :
					$m_posts->the_post();
					$data = ctcex_get_sermon_data( get_the_ID() );
					
				endwhile;
			endif;
			
			wp_reset_query();
			
			return $data;
			
		}
		
		/**
		 * Generate output to display
		 *
		 * @since  1.1
		 * @param  mixed   $sermon_data  Sermon data returned from get_query
		 * @param  string  $glyph        'fa' or 'gi' to use fontawesome or genericons
		 * @return string                Shortcode output
		 */
		function get_output( $sermon_data, $glyph ){
			
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
			
			$output = ''; 
			
			$name = isset( $sermon_data[ 'name' ] ) ? $sermon_data[ 'name' ] : '';
			$permalink = isset( $sermon_data[ 'permalink' ] ) ? $sermon_data[ 'permalink' ] : '';
			
			// Get date
			$date_src = sprintf( '<div class="%s"><b> %s:</b> %s</div>', $classes[ 'date' ], __( 'Date', 'ctcex' ), get_the_date() );
			
			// Get speaker
			$speaker_src = isset( $sermon_data[ 'speakers' ] ) ? sprintf( '<div class="%s"><b>%s:</b> %s</div>', $classes[ 'speaker' ], __( 'Speaker', 'ctcex' ), $sermon_data[ 'speakers' ] ) : '';
			
			// Get series
			$series = isset( $sermon_data[ 'series' ] ) ? $sermon_data[ 'series' ] : '';
			$series_link = $series && isset( $sermon_data[ 'series_link' ] ) ? sprintf( '<a href="%s">%s</a>', $sermon_data[ 'series_link' ], $series ) : $series;
			$series_src = $series ?	sprintf( '<div class="%s"><b>%s:</b> %s</div>', $classes[ 'series' ],  __( 'Series', 'ctcex' ), $series_link ) : '';
			
			// Get topics
			// Topic name
			$topic_name = explode( '/', ctcex_get_option( 'ctc-sermon-topic' , __( 'Topic', 'ctcex') ) );
			$topic_name = ucfirst( array_pop(  $topic_name ) );
			$topic = isset( $sermon_data[ 'topic' ] ) ? $sermon_data[ 'topic' ] : '';
			$topic_link = $topic && isset( $sermon_data[ 'topic_link' ] ) ? sprintf( '<a href="%s">%s</a>', $sermon_data[ 'topic_link' ], $topic ) : $topic;
			$topic_src = $topic ? sprintf( '<div class="%s"><b>%s:</b> %s</div>', $classes[ 'topic' ], $topic_name, $topic_link ) : '';

			// Get audio link
			$audio = isset( $sermon_data[ 'audio' ] ) ? $sermon_data[ 'audio' ] : '';
			$audio_link_src = $audio ? sprintf( '<div class="%s"><b>%s:</b> <a href="%s">%s</a></div>', $classes[ 'audio-link' ], __( 'Audio', 'ctcex' ), $audio, __( 'Download audio', 'ctcex' ) ) : '';
			
			// Get audio display
			$audio_src = $audio ? sprintf( '<div class="%s">%s</div>', $classes[ 'audio' ], wp_audio_shortcode( array( 'src' => $audio ) ) ) : '';
			
			// Get video display
			$video = isset( $sermon_data[ 'video' ] ) ? $sermon_data[ 'video' ] : '';
			$video_iframe_class = strripos( $video, 'iframe' ) ? 'iframe-container' : '';
			$video_src = $video ? sprintf( '<div class="%s %s">%s</div>', $classes[ 'video' ], $video_iframe_class, $video_iframe_class ? $video : wp_video_shortcode( array( 'src' => $video ) ) ) : '';
	
			// Use the image as a placeholder for the video
			$img = isset( $sermon_data[ 'img' ] ) ? $sermon_data[ 'img' ] : '';
			$img_overlay_class = $video && $img ? 'ctcex-overlay' : '';
			$img_overlay_js = $img_overlay_class ? sprintf(
				'<div class="ctcex-overlay">
					<i class="' . ( $glyph === 'gi' ? 'genericon genericon-play' : 'fa fa-play' ) . '"></i>
				</div><script>
					jQuery(document).ready( function($) {
						$( ".%s" ).css( "position", "relative" );
						$( ".ctcex-overlay" ).css( "cursor", "pointer" );
						var vid_src = \'%s\';
						vid_src = vid_src.replace( "autoPlay=false", "autoPlay=true" );
						$( ".ctcex-overlay" ).click( function(){
							$( this ).hide();
							$( ".ctcex-sermon-img" ).fadeOut( 200, function() {
								$( this ).replaceWith( vid_src );
								$( ".%s").addClass( "video_loaded" );
							});
						} );
					})
				</script>', 
				$classes[ 'media' ],
				$video_src, 
				$classes[ 'media' ]
				) : '' ;
				
			// Get image
			$img_src = $img ? sprintf( '%s<img class="%s" src="%s" alt="%s" width="960"/>', $img_overlay_js, $classes[ 'img' ], $img, $name ) : '';
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
				$permalink,
				$name,
				$date_src,
				$speaker_src,
				$series_src,
				$topic_src,
				$audio_link_src
			);
			
		
	 
			return $output;
			
		}
		
	}

}
		
