<?php
/**
 * ctcex Theme Customizer for setting
 *
 * @package ctcex
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class CTCEX_Customizer {
	
	function __construct(){
		
		add_action( 'customize_register', array( $this, 'customize_register' ) );
		
	}
	
	// Register the customizer settigns
	function customize_register( $wp_customize ) {

		//** Options Panel **//
		$this->customize_createPanel( $wp_customize, array(
			'id'           => 'ctcex_options_panel',
			'title'        => 'Church Content Options',
		) );

		// Podcasting options
		$this->customize_createSection( $wp_customize, array(
			'id'           => 'ctcex_podcast',
			'title'        => __( 'Podcasting', 'ctcex' ),
			'description'  => __( 'Settings for audio podcast', 'ctcex' ),
			'panel'        => 'ctcex_options_panel',
		) );
		// Description
		$this->customize_createSetting( $wp_customize, array(
			'id'           => 'ctcex_podcast_desc',
			'label'        => __( 'Podcast Description', 'ctcex' ),
			'type'         => 'textarea',
			'default'      => get_bloginfo( 'description' ),
			'section'      => 'ctcex_podcast',
		) );
		// Author
		$this->customize_createSetting( $wp_customize, array(
			'id'           => 'ctcex_podcast_author',
			'label'        => __( 'Podcast Author', 'ctcex' ),
			'type'         => 'text',
			'default'      => get_bloginfo( 'name' ),
			'section'      => 'ctcex_podcast',
		) );
		// Logo
		$this->customize_createSetting( $wp_customize, array(
			'id'           => 'ctcex_podcast_logo',
			'label'        => __( 'Podcast Logo', 'ctcex' ),
			'type'         => 'image',
			'default'      => '',
			'section'      => 'ctcex_podcast',
			'description'  => __( 'Logo used in podcast feed. Must be 1400 x 1400 jpg or png.', 'ctcex' ),
		) );
		
		
		// CTC alternate names
		$this->customize_createSection( $wp_customize, array(
			'id'              => 'ctcex_names',
			'title'           => __( 'Alternate Names', 'ctcex' ),
			'description'     => __( 'Enter alternate names for Church Content post types, ', 'ctcex' ),
			'panel'           => 'ctcex_options_panel',
		) );
		// Sermons
		$this->customize_createSetting( $wp_customize, array(
			'id' 	              => 'ctcex_sermons_singular',
			'type'              => 'text',
			'label'             => __( 'Sermon Singular', 'ctcex' ),
			'default'           => ctc_sermon_word_singular(),
			'section'           => 'ctcex_names',
		) );
		$this->customize_createSetting( $wp_customize, array(
			'id' 	              => 'ctcex_sermons_plural',
			'type'              => 'text',
			'label'             => __( 'Sermon Plural', 'ctcex' ),
			'default'           => ctc_sermon_word_plural(),
			'section'           => 'ctcex_names',
		) );
		$this->customize_createSetting( $wp_customize, array(
			'id' 	              => 'ctcex_sermon_series_singular',
			'type'              => 'text',
			'label'             => __( 'Sermon Series Singular', 'ctcex' ),
			'default'           => ctc_sermon_word_singular(),
			'section'           => 'ctcex_names',
		) );
		$this->customize_createSetting( $wp_customize, array(
			'id' 	              => 'ctcex_sermon_series_plural',
			'type'              => 'text',
			'label'             => __( 'Sermon Series Plural', 'ctcex' ),
			'default'           => ctc_sermon_word_plural(),
			'section'           => 'ctcex_names',
		) );
		// Event
		$this->customize_createSetting( $wp_customize, array(
			'id' 	              => 'ctcex_events_singular',
			'type'              => 'text',
			'label'             => __( 'Event Singular', 'ctcex' ),
			'default'           => ctc_post_type_label( 'ctc_event', 'singular' ),
			'section'           => 'ctcex_names',
		) );
		$this->customize_createSetting( $wp_customize, array(
			'id' 	              => 'ctcex_events_plural',
			'type'              => 'text',
			'label'             => __( 'Event Plural', 'ctcex' ),
			'default'           => ctc_post_type_label( 'ctc_event', 'plural' ),
			'section'           => 'ctcex_names',
		) );
		// Person
		$this->customize_createSetting( $wp_customize, array(
			'id' 	              => 'ctcex_people_singular',
			'type'              => 'text',
			'label'             => __( 'Person Singular', 'ctcex' ),
			'default'           => ctc_post_type_label( 'ctc_person', 'singular' ),
			'section'           => 'ctcex_names',
		) );
		$this->customize_createSetting( $wp_customize, array(
			'id' 	              => 'ctcex_people_plural',
			'type'              => 'text',
			'label'             => __( 'Person Plural', 'ctcex' ),
			'default'           => ctc_post_type_label( 'ctc_person', 'plural' ),
			'section'           => 'ctcex_names',
		) );
		// Location
		$this->customize_createSetting( $wp_customize, array(
			'id' 	              => 'ctcex_locations_singular',
			'type'              => 'text',
			'label'             => __( 'Location Singular', 'ctcex' ),
			'default'           => ctc_post_type_label( 'ctc_location', 'singular' ),
			'section'           => 'ctcex_names',
		) );
		$this->customize_createSetting( $wp_customize, array(
			'id' 	              => 'ctcex_locations_plural',
			'type'              => 'text',
			'label'             => __( 'Location Plural', 'ctcex' ),
			'default'           => ctc_post_type_label( 'ctc_location', 'plural' ),
			'section'           => 'ctcex_names',
		) );
		// Groups
		$this->customize_createSetting( $wp_customize, array(
			'id' 	              => 'ctcex_groups_singular',
			'type'              => 'text',
			'label'             => __( 'Group Singular', 'ctcex' ),
			'default'           => ctc_post_type_label( 'ctcex_group', 'singular' ),
			'section'           => 'ctcex_names',
		) );
		$this->customize_createSetting( $wp_customize, array(
			'id' 	              => 'ctcex_groups_plural',
			'type'              => 'text',
			'label'             => __( 'Group Plural', 'ctcex' ),
			'default'           => ctc_post_type_label( 'ctcex_group', 'plural' ),
			'section'           => 'ctcex_names',
		) );
		
		// CTC default images
		$this->customize_createSection( $wp_customize, array(
			'id'              => 'ctcex_images',
			'title'           => __( 'Default Images', 'ctcex' ),
			'description'     => __( 'Choose default images for use with sermons, events, and locations, ', 'ctcex' ),
			'panel'           => 'ctcex_options_panel',
		) );
		// Sermon
		$this->customize_createSetting( $wp_customize, array(
			'id' 	              => 'ctcex_sermon_default_image',
			'type'              => 'image',
			'label'             => __( 'Default Sermon Image', 'ctcex' ),
			'default'           => '',
			'section'           => 'ctcex_images',
		) );
		// Event
		$this->customize_createSetting( $wp_customize, array(
			'id' 	              => 'ctcex_event_default_image',
			'type'              => 'image',
			'label'             => __( 'Default Event Image', 'ctcex' ),
			'default'           => '',
			'section'           => 'ctcex_images',
		) );
		// Location
		$this->customize_createSetting( $wp_customize, array(
			'id' 	              => 'ctcex_location_default_image',
			'type'              => 'image',
			'label'             => __( 'Default Location Image', 'ctcex' ),
			'default'           => '',
			'section'           => 'ctcex_images',
		) );
		// Person
		$this->customize_createSetting( $wp_customize, array(
			'id' 	              => 'ctcex_person_dafault_image',
			'type'              => 'image',
			'label'             => __( 'Default Person Image', 'ctcex' ),
			'default'           => '',
			'section'           => 'ctcex_images',
		) );
		$this->customize_createSetting( $wp_customize, array(
			'id' 	              => 'ctcex_group_dafault_image',
			'type'              => 'image',
			'label'             => __( 'Default Group Image', 'ctcex' ),
			'default'           => '',
			'section'           => 'ctcex_images',
		) );
		
		
	}

	// Shortcut for creating a Customizer Setting
	function createSetting( $wp_customize, $args ) {
		$default_args = array(
			'id' 	              => '', // required
			'type'              => '', // required. This refers to the control type.
																 // All settings are theme_mod and accessible via get_theme_mod.
																 // Other types include: 'number', 'checkbox', 'textarea', 'radio',
																 // 'select', 'dropdown-pages', 'email', 'url', 'date', 'hidden',
																 // 'image', 'color'
			'label'             => '', // required
			'default'           => '', // required
			'section'           => '', // required
			'sanitize_callback' => 'wp_filter_nohtml_kses', // optional
			'active_callback'   => '', // optional
			'transport'         => 'refresh', // optional
			'description'       => '', // optional
			'priority'          => null, // optional
			'choices'           => '', // optional
			'panel'             => '', // optional
		);

		$args = wp_parse_args( $args, $default_args );

		// Available types and arguments
		$available_types = array( 'text', 'number', 'checkbox', 'textarea', 'radio', 'select', 'dropdown-pages', 'email', 'url', 'date', 'hidden', 'image', 'color' );
		$setting_def_args = array( 'type'=>'option', 'default'=> '', 'sanitize_callback'=>'', 'transport'=>'refresh' );
		$control_def_args = array( 'type'=>'', 'label'=>'', 'description'=>'', 'priority'=>'', 'choices'=>'', 'section'=>'', 'active_callback'=>'' );

		// Check for non-empty inputs, too
		if( empty( $args[ 'id' ] ) ||
				empty( $args[ 'section' ] ) ||
				empty( $args[ 'type' ] ) )
			return;

		// Check for a right type
		if( ! in_array( $args[ 'type' ], $available_types ) ) $args[ 'type' ] = 'text';

		$id = $args[ 'id' ];
		unset( $args[ 'id' ] );

		// Split setting arguments and control arguments
		$setting_args = array_intersect_key( $args, $setting_def_args );
		$control_args = array_intersect_key( $args, $control_def_args );

		$wp_customize->add_setting( $id, $setting_args );

		if( 'image' == $args[ 'type' ] ) {
			$wp_customize->add_control( new WP_Customize_Image_Control(
				$wp_customize,
				$id,
				array(
					'label'      => $args[ 'label' ],
					'section'    => $args[ 'section' ],
					'settings'   => $id,
					'description'=> $args[ 'description' ]
				)
			) );
		} elseif( 'color' == $args[ 'type' ] ) {
			$wp_customize->add_control( new WP_Customize_Color_Control(
				$wp_customize,
				$id,
				array(
					'label'      => $args[ 'label' ],
					'section'    => $args[ 'section' ],
					'settings'   => $id,
					'description'=> $args[ 'description' ]
				)
			) );
		} else {
			$wp_customize->add_control( $id, $control_args );
		}
	}

	// Shortcut for creating a Customizer Section
	function createSection( $wp_customize, $args ) {
		$default_args = array(
			'id' 	            => '', // required
			'title'           => '', // required
			'priority'        => null, // optional
			'description'     => '', // optional
			'active_callback' => '', // optional
			'panel'           => '', // optional
		);

		$args = wp_parse_args( $args, $default_args );

		// Check for required inputs
		if( empty( $args[ 'id' ] ) ||  empty( $args[ 'title' ] ) ) return;

		$id = $args[ 'id' ];
		unset( $args[ 'id' ] );
		$wp_customize->add_section( $id, $args );
	}

	// Shortcut for creating a Customizer Panel
	function createPanel( $wp_customize, $args ) {
		$default_args = array(
			'id'              => '', // required
			'title' 	        => '', // required
			'priority'        => null, // optional
			'description'     => '', // optional
			'active_callback' => '', // optional
		);

		$args = wp_parse_args( $args, $default_args );

		if( empty ( $args[ 'id' ] ) ||  empty( $args[ 'title' ] ) ) return;

		$id = $args[ 'id' ];
		unset( $args[ 'id' ] );
		$wp_customize->add_panel( $id, $args );
	}

	// Sanitize numeric values
	function sanitize_numeric_value( $input ) {
		if ( is_numeric( $input ) ) {
			return intval( $input );
		} else {
			return 0;
		}
	}

	// Sanitize true/false checkboxes
	function sanitize_checkbox( $input ) {
		if ( ! in_array( $input, array( true, false ) ) ) {
			$input = false;
		}
		return $input;
	}

}
