<?php
/*
		Class to allow renaming of CTC post types.
*/
// No direct access
if ( ! defined( 'ABSPATH' ) ) exit;

class CTCEX_CPTNames {
	
	function __construct() {
		$this->version = '1.0';
		
		// Church Theme Content is REQUIRED
		if ( ! class_exists( 'Church_Theme_Content' ) ) return;
		
		add_action( 'admin_menu', array( &$this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( &$this, 'settings_init' ) );
		
		// Change slugs in the custom CTC types
		add_filter( 'ctc_post_type_person_args', array( &$this, 'ctc_slugs' ), 10, 1);
		add_filter( 'ctc_post_type_sermon_args', array( &$this, 'ctc_slugs' ), 10, 1);
		add_filter( 'ctc_post_type_location_args', array( &$this,'ctc_slugs' ), 10, 1);
		add_filter( 'ctc_taxonomy_sermon_series_args', array( &$this,'ctc_slugs' ), 10, 1);
		//add_filter( 'ctc_post_type_event_args', array( 'ctc_slugs' ), 10, 1);
		
		// Hijack the topic taxonomy for other purposes
		add_filter( 'ctc_taxonomy_sermon_topic_args', array( &$this,'ctc_slugs' ), 10, 1);
		
	}

	function add_admin_menu() { 
		add_options_page( 
			'CTC Extender', 
			'CTC Extender', 
			'manage_options', 
			'ctc-extender',
			array( &$this, 'options_page' ) );
	}

	function settings_init() { 

		register_setting( 'ctcexSettings', 'ctcex_settings', array( &$this, 'validate_settings' ) );

		add_settings_section(
			'ctcex_ctcexSettings_section', 
			__( 'Your section description', 'ctcex' ), 
			array( &$this, 'settings_section_callback' ), 
			'ctcexSettings'
		);

		add_settings_field( 
			'ctc-sermons', 
			__( 'Sermons', 'ctcex' ), 
			array( &$this, 'textfield_render' ), 
			'ctcexSettings', 
			'ctcex_ctcexSettings_section', 
			array( 
				'default' => __( 'Sermons' , 'ctcex' ),
				'id'      => 'ctc-sermons',
			)
		);

		add_settings_field( 
			'ctc-sermon-series', 
			__( 'Sermon Series', 'ctcex' ), 
			array( &$this, 'textfield_render' ), 
			'ctcexSettings', 
			'ctcex_ctcexSettings_section', 
			array( 
				'default' => __( 'Sermon Series' , 'ctcex' ),
				'id'      => 'ctc-sermon-series',
			)
		);

		add_settings_field( 
			'ctc-sermon-topic', 
			__( 'Sermon Topics', 'ctcex' ), 
			array( &$this, 'textfield_render' ), 
			'ctcexSettings', 
			'ctcex_ctcexSettings_section' ,
			array( 
				'default' => __( 'Sermon Topics' , 'ctcex' ),
				'id'      => 'ctc-sermon-topic',
			)
		);

		add_settings_field( 
			'ctc-people', 
			__( 'People', 'ctcex' ), 
			array( &$this, 'textfield_render' ), 
			'ctcexSettings', 
			'ctcex_ctcexSettings_section', 
			array( 
				'default' => __( 'People' , 'ctcex' ),
				'id'      => 'ctc-people',
			)
		);

		add_settings_field( 
			'ctc-locations', 
			__( 'Locations', 'ctcex' ), 
			array( &$this, 'textfield_render' ), 
			'ctcexSettings', 
			'ctcex_ctcexSettings_section', 
			array( 
				'default' => __( 'Locations' , 'ctcex' ),
				'id'      => 'ctc-locations',
			)
		);

		add_settings_field( 
			'ctc-events', 
			__( 'Events', 'ctcex' ), 
			array( &$this, 'textfield_render' ), 
			'ctcexSettings', 
			'ctcex_ctcexSettings_section', 
			array( 
				'default' => __( 'Events' , 'ctcex' ),
				'id'      => 'ctc-events',
			)
		);


	}

	function textfield_render( $args ) { 

		extract( $args );
		
		$options = get_option( 'ctcex_settings' );
		
		?>
		<input type="text" name="ctcex_settings[<?php echo $id; ?>]" class="regular-text"value="<?php echo $options[$id]; ?>" placeholder = "<?php echo $default; ?>">
		<?php

	}

	function settings_section_callback(  ) { 

		echo __( 'Enter the display names to use for the different church theme content types. For instance <code>People</code> could be <code>Staff</code>, <code>Sermons</code> could be <code>Messages</code> or <code>Locations</code> could be <code>Places</code>. Make sure to resave the Permalinks to update the permalinks. If separate singular and plural names are desired, write them as <code>Plural/Singluar</code> (i.e., <code>Campuses/Campus</code>).', 'ctcex' );

	}

	function options_page(  ) { 

		?>
		<form action='options.php' method='post'>
			
			<h2>Church Theme Content Extender</h2>
			
			<?php
			settings_fields( 'ctcexSettings' );
			do_settings_sections( 'ctcexSettings' ); 
			
			 
			echo ' <input name="reset" id="reset" type="submit" class="button reset-button button-secondary" value="'. __( 'Restore Defaults' ) .'" onclick="return confirm(\'Click OK to reset. Any theme settings will be lost!\');" />
			<input name="submit" id="submit" type="submit" class="button button-primary" value="' . __( 'Save Changes' ) . '" />
			';
			?>
			
		</form>
		<?php

	}
	
	function validate_settings( $input ) {
	
		if ( ! isset( $_POST['reset'] ) ) {
			$options = get_option( 'ctcex_settings' );
			return $input;
		}
		flush_rewrite_rules();
		return false;		
}
	
	// Change slugs of CTC 
	function ctc_slugs( $args ){
		$old_slug = $args['rewrite']['slug'];
		$old_name = $args['labels']['name'];
		$old_singular_name = $args['labels']['singular_name'];
		
		$options = get_option( 'ctcex_settings' );
		$option_name = 'ctc-' . $old_slug;
		
		if( ! $options[ $option_name ] ) 
			return $args;
		
		if( $options[ $option_name ] == $old_name )
			return $args;
		
		// Option is in the form of plural/singular
		$option_value = $options[ $option_name ] ? $options[ $option_name ] : implode( '/', array( $old_name, $old_singular_name ) ) ;		
					
		// Get the new plural and singular names
		list( $new_name, $new_singular_name ) = array_pad( explode( "/", $option_value ), 2, $option_value );
		
		// New slug
		$new_slug = sanitize_title( $new_name, $old_slug);
		
		// Search and replace in the arguments 
		$names = array( $old_name, strtolower( $old_name ), $old_singular_name, strtolower( $old_singular_name ) );
		if( strpos( $old_name, 'Sermon ') !== false ) {
			array_push( $names, str_replace( 'Sermon ', '', $old_name ), strtolower( str_replace( 'Sermon ', '', $old_name ) ), str_replace( 'Sermon ', '', $old_singular_name ), strtolower( str_replace( 'Sermon ', '', $old_singular_name ) ) );
		}
		$new_names = array( $new_name, strtolower( $new_name ), $new_singular_name, strtolower( $new_singular_name ) );
		if( strpos( $old_name, 'Sermon ')  !== false ) {
			array_push( $new_names, $new_name, strtolower( $new_name ), $new_singular_name, strtolower( $new_singular_name ) );
		}
		// Names are only changed in the labels
		$args['labels'] = json_decode( str_replace( $names,  $new_names, json_encode( $args['labels'] ) ), true );
		
		// Change the slug
		$args['rewrite']['slug'] = $new_slug;
		
		return $args;
	}

	
}
