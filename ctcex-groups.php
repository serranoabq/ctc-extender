<?php
/**
 * Register Group Post Type
 *
 */

// No direct access
if ( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists( 'CTCEX_Groups' ) ) {
	
	class CTCEX_Groups {
		
		public $version = '1.1.1';
		
		function __construct(){
			
			// Church Theme Content is REQUIRED
			if ( ! class_exists( 'Church_Theme_Content' ) ) return;
			
			add_action( 'init', array( $this, 'register_group_post_type' ) ); 
			add_action( 'admin_init', array( $this, 'add_meta_box_leader' ) );
			add_action( 'admin_init', array( $this, 'add_meta_box_group' ) );
			add_filter( 'manage_ctcex_groups_posts_columns' , array( $this, 'add_columns' ) );
			add_action( 'manage_posts_custom_column' , array( $this, 'add_columns_content' ) ); 

		}
		
		// Register post type
		function register_group_post_type() {

			// Arguments
			$args = array(
				'labels'      => array(
					'name'               => _x( 'Small Groups', 'post type general name', 'ctcex' ),
					'singular_name'      => _x( 'Small Group', 'post type singular name', 'ctcex' ),
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
				'public'      => true,
				'has_archive' => true,
				'rewrite'     => array(
					'slug'        => 'small-group',
					'with_front'  => true,
					'feeds'       => false, //ctc_feature_supported( 'groups' )
				),
				'supports'    => array( 'title', 'editor', 'thumbnail', 'revisions' ), 
				'menu_icon'		=> 'dashicons-groups'	
			);
			$args = apply_filters( 'ctc_post_type_group_args', $args ); // allow filtering
				
			// Registration
			register_post_type(
				'ctcex_groups',
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
				'post_type'	 => 'ctcex_groups',
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
				'post_type'	 => 'ctcex_groups',
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
						'class'           => 'ctmb-medium', 
					),

					// Group address
					'_ctcex_group_address' => array(
						'name'				    => _x( 'Address', 'event meta box', 'ctcex' ),
						'after_name'		  => '', 
						'desc'				    => '',
						'type'				    => 'textarea', 
						'no_empty'			  => false, 
						'allow_html'		  => false, 
						'class'				    => 'ctmb-medium', 
					),
					
					// Group Day				
					'_ctcex_group_demographic'  => array(
						'name'            => __( 'Target Demographic', 'ctcex' ),
						'desc'            => '',
						'type'            => 'select',
						'options'         => array( 
							'all'         => _x( 'Anyone', 'group demographic', 'ctcex' ),
							'women'       => _x( 'Women', 'group demographic', 'ctcex' ),
							'men'         => _x( 'Men', 'group demographic', 'ctcex' ),
							'teens'       => _x( 'Teens All (12-18)', 'group demographic', 'ctcex' ),
							'teen_g'      => _x( 'Teen Girls (12-18)', 'group demographic', 'ctcex' ),
							'teen_b'      => _x( 'Teen Boys (12-18)', 'group demographic', 'ctcex' ),
							'middle_sch'  => _x( 'Middle Schoolers', 'group demographic', 'ctcex' ),
							'high_sch'    => _x( 'High Schoolers', 'group demographic', 'ctcex' ),
							'kids'        => _x( 'Children (2-12)', 'group demographic', 'ctcex' ),
							'marriage'    => _x( 'Marriages', 'group demographic', 'ctcex' ),
							'young_adult' => _x( 'Young Adults', 'group demographic', 'ctcex' ),
							'seniors'     => _x( 'Seniors over 50', 'group demographic', 'ctcex' ),
							), 
						'default'         => 'all', 
						'no_empty'        => true, 
						'allow_html'      => false, 
					),
					
					// Childcare availability
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

		// Add columns
		function add_columns( $columns ) {

			$insert_array = array();
			$insert_array['ctcex_group_day_time'] = _x( 'When', 'group admin column', 'ctcex' );
			$insert_array['ctcex_group_leader'] = _x( 'Leader', 'group admin column', 'ctcex' );
			$insert_array['ctcex_group_address'] = _x( 'Address', 'group admin column', 'ctcex' );
			$insert_array['ctcex_group_demographic'] = _x( 'Demographic', 'group admin column', 'ctcex' );
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
						echo '<div class="description">' . $time . '</div>';
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
				
					echo get_post_meta( $post->ID , '_ctcex_group_demographic' , true );
				
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
			
			// Filter the output of the whole shortcode
			// Note: This filters the whole shortcode. Any styles and scripts needed by the 
			//       new ouput will have to be included in the filtering function
			// Use: add_filter( 'ctcex_group_shortcode', '<<<callback>>>', 10, 2 ); 
			// Args: first argument is the output to filter; empty
			//       <people_data> is the data extracted from people query; array of arrays
			$output = apply_filters( 'ctcex_group_shortcode', '', $group_data );
			
			if( empty( $output) )
				$output = $this->get_output( $group_data );
			
			return $output; 
			
		}
		
		function get_query(){
			
			// do query 
			$query = array(
				'post_type' 				=> 'ctc_person', 
				'order' 						=> 'ASC',
				'orderby' 					=> 'meta_value_date',
				'meta_key' 					=> '_ctcex_group_day',
				'meta_type'					=> 'DATE',
				'posts_per_page'		=> -1,
			); 
			
			$posts = new WP_Query( $query );		
			if( $posts->have_posts() ):
				while ( $posts->have_posts() ) :
				
					$data[] = ctcex_get_group_data( get_the_ID() );
					
				endwhile;
			endif;
			
			wp_reset_query();
			
			return $data;
			
		}
		
		function get_group_output( $group_data ){
			
			// generate output
			$this->scripts();
			
			$output = '';
			foreach( $group_data as $group ){
				
				$item_output = '';
				
				$output .= $output;
			}
			
			$output .= '';
			
			return $output;
		}
		
		function scripts(){
			
			// enqueue scripts
			//wp_enqueue_script...
		}
		
	}
	
}

