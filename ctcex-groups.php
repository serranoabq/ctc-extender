<?php
/**
 * Register Group Post Type
 *
 */

// No direct access
if ( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists( 'CTCEX_Groups' ) ) {
	
	class CTCEX_Groups {
		
		public $version = '1.1.3';
		
		function __construct(){
			
			// Church Theme Content is REQUIRED
			if ( ! class_exists( 'Church_Theme_Content' ) ) return;
			
			add_action( 'init', array( $this, 'register_group_post_type' ) ); 
			add_action( 'init', array( $this, 'add_demographic_taxonomy' ), 11 ); 
			add_action( 'admin_init', array( $this, 'add_meta_box_leader' ) );
			add_action( 'admin_init', array( $this, 'add_meta_box_group' ) );
			add_filter( 'manage_ctcex_group_posts_columns' , array( $this, 'add_columns' ) );
			add_action( 'manage_posts_custom_column' , array( $this, 'add_columns_content' ) ); 

			add_shortcode( 'small_groups', array( $this, 'shortcode' ) );
		}
		
		// Register post type
		function register_group_post_type() {

			// Arguments
			$args = array(
				'labels'      => array(
					'name'               => __( 'Small Groups', 'ctcex' ),
					'singular_name'      => __( 'Small Group', 'ctcex' ),
					'add_new'            => __( 'Add New', 'ctcex' ),
					'add_new_item'       => __( 'Add Group', 'ctcex' ),
					'edit_item'          => __( 'Edit Group', 'ctcex' ),
					'new_item'           => __( 'New Group', 'ctcex' ),
					'all_items'          => __( 'All Groups', 'ctcex' ),
					'view_item'          => __( 'View Group', 'ctcex' ),
					'search_items'       => __( 'Search Groups', 'ctcex' ),
					'not_found'          => __( 'No groups found', 'ctcex' ),
					'not_found_in_trash' => __( 'No groups found in Trash', 'ctcex' )
				),
				'public'      => current_theme_supports( 'ctcex-groups' ),
				'has_archive' => true,
				'rewrite'     => array(
					'slug'        => 'small-groups',
					'with_front'  => true,
					'feeds'       => false, //ctc_feature_supported( 'groups' )
				),
				'supports'    => array( 'title', 'editor', 'thumbnail', 'revisions' ), 
				'menu_icon'		=> 'dashicons-groups'	
			);
			$args = apply_filters( 'ctcex_post_type_group_args', $args ); // allow filtering
				
			// Registration
			register_post_type(
				'ctcex_group',
				$args
			);

		}

		// Add metaboxes
		function add_meta_box_leader(){
			
			// Configure Meta Box
			$meta_box = array(
			
				// Meta Box
				'id'         => 'ctcex_group_leader', // unique ID
				'title'      => __( 'Group Leader Info', 'ctcex' ),
				'post_type'	 => 'ctcex_group',
				'context'	   => 'side', 
				'priority'	 => 'core', 
				'fields'     => array(
					
					// Group Host/Leader				
					'_ctcex_group_leader'  => array(
						'name'            => __( 'Name', 'ctcex' ),
						'after_name'		  => '', 
						'desc'            => '',
						'type'            => 'text',
						'default'         => '', 
						'no_empty'        => true, 
						'allow_html'      => false, 
						'attributes'      => array(), 
						'class'           => 'ctmb-medium', 
					),
					
					// Group Host/Leader Email			
					'_ctcex_group_leader_email'  => array(
						'name'            => __( 'Email', 'ctcex' ),
						'after_name'		  => '', 
						'desc'            => '',
						'type'            => 'email',
						'default'         => '', 
						'no_empty'        => true, 
						'allow_html'      => false, 
						'attributes'      => array(), 
						'class'           => 'ctmb-medium', 
					),
					
					// Group Host/Leader Phone			
					'_ctcex_group_leader_phone'  => array(
						'name'            => __( 'Phone', 'ctcex' ),
						'after_name'		  => '', 
						'desc'            => '',
						'type'            => 'text',
						'default'         => '', 
						'no_empty'        => true, 
						'allow_html'      => false, 
						'attributes'      => array(), 
						'class'           => 'ctmb-medium', 
					),
	
					// Group Host/Leader Email			
					'_ctcex_group_leader_mobile'  => array(
						'name'            => __( 'Mobile?', 'ctcex' ),
						'after_name'		  => '', 
						'desc'            => '',
						'type'            => 'checkbox',
						'checkbox_label'  => 'Phone is mobile phone',
						'default'         => '', 
						'no_empty'        => true, 
						'allow_html'      => false, 
						'attributes'      => array(), 
						'class'           => 'ctmb-medium', 
					),
					
				),
				
			);
				
			// Add Meta Box
			new CT_Meta_Box( $meta_box );
			
		}
		
		function add_meta_box_group(){
			
			// Configure Meta Box
			$meta_box = array(
			
				// Meta Box
				'id'         => 'ctcex_group_data', // unique ID
				'title'      => __( 'Group Data', 'ctcex' ),
				'post_type'	 => 'ctcex_group',
				'context'	   => 'side', 
				'priority'	 => 'core', 
				'fields'     => array(
					
					// Group Day				
					'_ctcex_group_day'  => array(
						'name'            => __( 'Day', 'ctcex' ),
						'after_name'		  => __( '(Required)', 'ctcex' ), 
						'desc'            => __( 'Provide the day of the week the group meets', 'ctcex' ),
						'type'            => 'select',
						'checkbox_label'  => '', //show text after checkbox
						'options'         => array( 
							'sunday'     => date_i18n( 'l', strtotime( 'next Sunday' ) ),
							'monday'     => date_i18n( 'l', strtotime( 'next Monday' ) ),
							'tuesday'    => date_i18n( 'l', strtotime( 'next Tuesday' ) ),
							'wednesday'  => date_i18n( 'l', strtotime( 'next Wednesday' ) ),
							'thursday'   => date_i18n( 'l', strtotime( 'next Thursday' ) ),
							'friday'     => date_i18n( 'l', strtotime( 'next Friday' ) ),
							'saturday'   => date_i18n( 'l', strtotime( 'next Saturday' ) ),
							), 
						'default'         => 0, 
						'no_empty'        => true, 
						'allow_html'      => false, 
						'attributes'      => array(), 
						'class'           => '', 
					),
					
					// Group Time				
					'_ctcex_group_time' => array(
						'name'            => __( 'Time', 'ctcex' ),
						'after_name'      => __( '(Required)', 'ctcex' ), 
						'desc'            => __( 'Provide a time such as "8:00 am &ndash; 2:00 pm"', 'ctcex' ),
						'type'            => 'text', 
						'default'         => '', 
						'no_empty'        => true, 
						'allow_html'      => false, 
						'attributes'      => array(), 
						'class'           => 'ctmb-time', 
					),

					// Group address
					'_ctcex_group_address' => array(
						'name'				    => __( 'Address', 'ctcex' ),
						'after_name'		  => '', 
						'desc'				    => '',
						'type'				    => 'textarea', 
						'no_empty'			  => false, 
						'allow_html'		  => false, 
						'class'				    => 'ctmb-medium', 
					),
					
					// Group zip
					'_ctcex_group_zip' => array(
						'name'				    => __( 'Zip code', 'ctcex' ),
						'after_name'		  => '', 
						'desc'				    => '',
						'type'				    => 'number', 
						'no_empty'			  => true, 
						'allow_html'		  => false, 
						'class'				    => 'ctmb-medium', 
					),
					
					// Childcare Availability
					'_ctcex_group_childcare' => array(
						'name'            => __( 'Has childcare', 'ctcex' ),
						'desc'            => '',
						'type'            => 'checkbox', 
						'checkbox_label'  => __( 'Group has childcare', 'ctcex' ), 
						'default'         => false, 
						'no_empty'        => false, 
						'allow_html'      => false, 
						'class'           => 'ctmb-medium', 
					),
					
				),

			);
			
			// Add Meta Box
			new CT_Meta_Box( $meta_box );
			
		}

		function add_demographic_taxonomy(){
			
			// Arguments
			$args = array(
				'labels' => array(
					'name'                  => __( 'Demographics', 'ctcex' ),
					'singular_name'         => __( 'Demographic', 'ctcex' ),
					'search_items'          => __( 'Search Demographic', 'ctcex' ),
					'popular_items'         => __( 'Popular Demographics', 'ctcex' ),
					'all_items'             => __( 'All Demographics', 'ctcex' ),
					'parent_item'           => null,
					'parent_item_colon'     => null,
					'edit_item'             => __( 'Edit Demographic', 'ctcex' ),
					'update_item'           => __( 'Update Demographic', 'ctcex' ),
					'add_new_item'          => __( 'Add Demographic', 'ctcex' ),
					'new_item_name'         => __( 'New Demographic', 'ctcex' ),
					'add_or_remove_items'   => __( 'Add or remove demographic', 'ctcex' ),
					'choose_from_most_used' => __( 'Choose from the most used demographic', 'ctcex' ),
					'menu_name'             => __( 'Demographics', 'ctcex' )
				),
				'hierarchical'	=> true, // category-style instead of tag-style
				'public' 		=> true,
				'rewrite' 		=> array(
					'slug' 			=> 'group-demographic',
					'with_front' 	=> false,
					'hierarchical' 	=> false
				)
			);
			$args = apply_filters( 'ctc_group_demographic_args', $args ); // allow filtering

			// Registration
			register_taxonomy(
				'ctcex_group_demographic',
				array( 'ctcex_group' ),
				$args
			);
		}
		
		// Add columns
		function add_columns( $columns ) {

			$insert_array = array();
			$insert_array['ctcex_group_day_time'] = _x( 'When', 'group admin column', 'ctcex' );
			$insert_array['ctcex_group_leader'] = _x( 'Leader', 'group admin column', 'ctcex' );
			$insert_array['ctcex_group_address'] = _x( 'Address', 'group admin column', 'ctcex' );
			$insert_array['ctcex_group_demographic'] = _x( 'Demographics', 'group admin column', 'ctcex' );
			$insert_array['ctcex_group_childcare'] = _x( 'Has Childcare?', 'group admin column', 'ctcex' );
			$columns = ctc_array_merge_after_key( $columns, $insert_array, 'title' );

			// remove author
			unset( $columns['author'] );
			
			return $columns;

		}

		// Add column content
		function add_columns_content( $column ) {

			global $post;
			
			switch ( $column ) {

				// Day
				case 'ctcex_group_day_time' :
				
					$day = get_post_meta( $post->ID , '_ctcex_group_day' , true );
					$time = get_post_meta( $post->ID , '_ctcex_group_time' , true );
					$date = date_i18n( 'l', strtotime( 'next ' . $day ) ); 
					
					echo "<b>{$date}'s</b>";
					if ( ! empty( $time ) ) {
						echo '<div class="description">' . date_i18n( 'g:iA', strtotime( $time ) ). '</div>';
					}
					
					break;

				// Leader
				case 'ctcex_group_leader' :
				
					echo get_post_meta( $post->ID , '_ctcex_group_leader' , true );
				
					break;
				
				// Address
				case 'ctcex_group_address' :
				
					echo get_post_meta( $post->ID , '_ctcex_group_address' , true );
				
					break;
				
				// Demographic
				case 'ctcex_group_demographic' :
				
					$terms = join( ', ', wp_list_pluck( wp_get_post_terms( $post->ID, 'ctcex_group_demographic' ), 'name' ) );
					echo $terms;
				
					break;
				
				// Childcare
				case 'ctcex_group_childcare' :
					$cb = (bool) get_post_meta( $post->ID , '_ctcex_group_childcare' , true );
					
					echo $cb ? '<span class="dashicons dashicons-yes"></span>' : '';
				
					break;
					
			}

		}

		function shortcode(){
			
			$group_data = $this->get_query();
			
			$terms = get_terms( array( 
				'taxonomy' => 'ctcex_group_demographic',
				'hide_empty' => true,
			) );
			
			// Filter the output of the whole shortcode
			// Note: This filters the whole shortcode. Any styles and scripts needed by the 
			//       new ouput will have to be included in the filtering function
			// Use: add_filter( 'ctcex_group_shortcode', '<<<callback>>>', 10, 3 ); 
			// Args: first argument is the output to filter; empty
			//       <people_data> is the data extracted from people query; array of arrays
			$output = apply_filters( 'ctcex_group_shortcode', '', $group_data, $terms );
			
			if( empty( $output) )
				$output = $this->get_group_output( $group_data, $terms );
			
			return $output; 
			
		}
		
		function get_query(){
			
			// do query 
			$query = array(
				'post_type' 				=> 'ctcex_group', 
				'order' 						=> 'ASC',
				'orderby' 					=> 'meta_value_date',
				'meta_key' 					=> '_ctcex_group_day',
				'meta_type'					=> 'DATE',
				'posts_per_page'		=> -1,
			); 
			
			$m_posts = new WP_Query( $query );
			
			if( $m_posts->have_posts() ):
				while ( $m_posts->have_posts() ) : $m_posts->the_post();
					
					$data[] = ctcex_get_group_data( get_the_ID() );
					
				endwhile;
			endif;
			
			wp_reset_query();
			
			return $data;
			
		}
		
		function get_group_output( $group_data, $terms ){
			
			$this->scripts();
			
			$output =  $this->control_markup( $terms );
			$output .= $this->container_markup( $group_data );
			
			return $output;
		}
		
		function control_markup( $terms ){
			
			$output = apply_filters( 'ctc_group_demographic_control_output', '', $terms ); // allow filtering
			if( ! empty( $output ) ) return $output;
			
			$output = '
			<style>
				#childcare_label, #ctcex_demographics{
					margin-right: 2em;
				}
				.ctcex_group{
					border-radius: 0.5em;
					margin-bottom: 1em;
					background-color: rgba(250,250,250, 0.8);
					padding: 1em;
				} 
			</style>
			';
			
			$output .= '<div class="ctcex_group_controls">';
			$output .= '	<select id="ctcex_demographics">';
			$output .= '		<option value="all">' . __( 'All', 'ctcex' ) . '</option>';
			foreach( $terms as $term ){
				$output .= '		<option value=".' . $term->slug . '">' . $term->name . '</option>';
			}
			$output .= '	</select>';
			$output .= '	<label for="childcare" id="childcare_label">';
			$output .= '		<input id="childcare" type="checkbox" data-toggle=".haschildcare"> ';
			$output .=			__( 'Childcare provided?', 'ctcex' );
			$output .= '	</label>';
			$output .= '</div>';
			
			$output .= '
			<script>
				jQuery(document).ready( function($) {
					$( "#ctcex_demographics, #childcare" ).on( "change", function(){
						do_filter();
					});
					
					function do_filter(){
						var childcare = $( "#childcare" ).prop( "checked" ) ? ".haschildcare" : "";
						var demo = $( "#ctcex_demographics" ).val();
						var value = childcare ? ( "all" == demo ? childcare : demo + childcare ) : demo;
						mixer.filter(value);
						return;
						if( value + childcare )
							$( ".ctcex_group:not(" + value + childcare + ")" ).fadeOut();
						$( ".ctcex_group" + value + childcare ).fadeIn();
						
					}
					
					var mixer = mixitup( ".ctcex_groups_container" );
				});
			</script>';
			
			return $output; 
			
		}
		
		function container_markup( $group_data ){

			$output = apply_filters( 'ctc_group_demographic_container_output', '', $group_data ); // allow filtering
			if( ! empty( $output ) ) return $output;
			
			$output = '<div class="ctcex_groups_container">';
			
			foreach( $group_data as $group ){
				$day = $group[ 'day' ] ? date_i18n( 'l', strtotime( 'next ' . $group[ 'day' ] ) ): '';
				$time = $group[ 'time' ] && $day ? date_i18n( ' @ g:iA', strtotime( $group[ 'time' ] ) ) : '';
				
				$classes = array();
				$classes[] = $group[ 'day' ];
				$classes[] = $group[ 'zip' ];
				if( $group[ 'has_childcare' ] ) $classes[] = 'haschildcare';
				$demos = explode( ", ", $group[ 'demographic_sl' ] );
				$classes = array_merge( $classes, $demos );
				
				$output .= '<div class="ctcex_group mix ' . join( ' ', $classes ) . '">';
				$output .= '	<h5 class="ctcex_group_name">' . $group[ 'name' ] . '</h5>';
				$output .= '	<div class="ctcex_group_data">';
				$output .= "		<div class='ctcex_group_day_time'>{$day}'s {$time}</div>";				
				$output .= $group[ 'leader' ] ? '			<div class="ctcex_group_leader"><b>' . __( 'Leader', 'ctcex' ) . ":</b> {$group[ 'leader' ]}</div>" : '';
				$output .= $group[ 'leader_em' ] ?  '			<div class="ctcex_group_leader_email"><b>' . __( 'Email', 'ctcex' ) . ":</b> {$group[ 'leader_em' ]}</div>" : '';
				$output .= $group[ 'leader_ph' ] ?  '			<div class="ctcex_group_leader_phone"><b>' . __( 'Phone', 'ctcex' ) . ":</b> {$this->format_phone( $group[ 'leader_ph' ] )}</div>" : '';
				$output .= $group[ 'address' ] ? '			<div class="ctcex_group_address">' . nl2br( $group[ 'address' ] ) . '</div>' : '';
				$output .= $group[ 'has_childcare' ] ? '			<div class="ctcex_group_haschildcare">' . __( 'Childcare provided', 'ctcex' ) . '</div>' : '';
				
				$output .= '		</div><!-- .ctcex_group_data -->';
				$output .= '	</div><!-- .ctcex_group -->';
			}
			
			$output .= '</div>';
			
			return $output;
		}
		
		function format_phone( $number ){
			return preg_replace('/\D*([2-9]\d{2})*(\D*)([2-9]\d{2})(\D*)(\d{4})/', '($1) $3-$5', $number );
		}
		
		function scripts(){
			
			// enqueue scripts
			wp_enqueue_script( 'mixitup', 'http://cdn.jsdelivr.net/gh/patrickkunka/mixitup@3.2.1/dist/mixitup.min.js', array( 'jquery' ) );
			
			//wp_enqueue_style...
		}
		
	}
	
}

