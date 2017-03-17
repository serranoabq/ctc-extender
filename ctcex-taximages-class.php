<?php
/*
		Custom class for adding taxonomy images to CTC taxonomies (by default only for ctc_sermon_series).
*/
// No direct access
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'CTCEX_TaxImages' ) ) {
	class CTCEX_TaxImages {
		
		public $version;
		
		public $taxonomies = array( 'ctc_sermon_series' ) ;
		
		function __construct( $ctcex_version ) {
			// Church Theme Content is REQUIRED
			if ( ! class_exists( 'Church_Theme_Content' ) ) return;

			$this->version = $ctcex_version;
			$this->taxonomies = apply_filters( 'ctc_tax_img_taxonomies', $this->taxonomies );
			
			add_action('admin_head', array( $this, 'admin_head' ) ) ;
			add_action('edit_term', array( $this, 'save_tax_img' ) );
			add_action('create_term', array( $this, 'save_tax_img' ) );
			add_action('delete_term', array( $this, 'delete_tax_img' ) );
		}

		/**
		 * Add actions to handle image fields on taxonomies
		 *
		 * @since 0.1
		 */
		function admin_head(){
			
			// This is the array of taxonomies to add an image to. 
			// By default it applies only to the sermon series, but using the
			// 'ctc_taxonomy_img_filter' filter, other taxonomies can be included
			$taxonomies = $this->taxonomies;
			
			if( version_compare( $this->version, '1.4.1', '>' ) )	{
				$this->upgrade();
			}
			
			foreach( $taxonomies as $tax ) {
				add_action( $tax . '_add_form_fields', array( $this, 'img_tax_field' ) );
				add_action( $tax . '_edit_form_fields', array( $this, 'img_tax_field' ) );
				add_filter( "manage_edit-{$tax}_columns", array( $this, 'img_tax_column' ) );
				add_filter( "manage_{$tax}_custom_column", array( $this, 'edit_tax_img_column' ), 10 , 3 );
			}
		}

		/**
		 * Add image fields for a taxonomy
		 *
		 * @since 0.1
		 * @param mixed $tax_or_term Term object or taxonomy name being edited
		 */
		function img_tax_field( $tax_or_term ) {
			wp_enqueue_media();
			if( is_object( $tax_or_term ) ){
				// $tax_or_term is an object so it's the edit term screen
				$t_id = $tax_or_term->term_id;
				$tax_label = get_taxonomy( $tax_or_term->taxonomy )->label;
				$opening = '<tr clas="form-field term-featured-image"><th scope="row">';
				$mid = '</th><td>';
				$closing = '</td></tr>';
				$ctc_img = get_option( 'ctc_tax_img_' . $t_id );
				$val =  $ctc_img ? esc_attr( $ctc_img ) : '';
			} else {
				$tax_label = get_taxonomy( $tax_or_term )->label;
				$opening = '<div class="form-field term-featured-image">';
				$mid = '';
				$closing = '</div>'; 
				$t_id = '';
				$val = '';
			}
?>
<?php echo $opening; ?>
	<label for="ctc_tax_image"><?php _e( 'Featured Image', 'ctcex' ); ?></label>
<?php echo $mid; ?>
	<input type="hidden" name="ctc_tax_image" id="ctc_tax_image" value="<?php echo $val; ?>" /> 
	<img id="ctc_tax_img" src="<?php echo $val; ?>" style="max-width: 200px; border: 1px solid #ccc; padding: 5px; box-shadow: 5px 5px 10px #ccc; margin: 10px 0; display:<?php echo (empty( $val ) ? 'none' : 'block'); ?>; " />
	<input type="button" class="button button-secondary" value="<?php _e( 'Add Image', 'ctcex' ); ?>" id="ctc_tax_img_upload" style="display:<?php echo (empty( $val ) ? 'inline' : 'none' ); ?>"/>
  <input type="button" class="button button-secondary" value="<?php _e( 'Remove Image', 'ctcex' ); ?>" id="ctc_tax_img_delete" style="display:<?php echo (empty( $val ) ? 'none' : 'inline' ); ?>"/>
	<br/><span class="description"><?php sprintf( __('Choose an image to associate with this %s. To replace an image, first <strong>Remove</strong> it and then <strong>Add</strong> another one.', 'ctcex' ), $tax_label ); ?></span>
<?php echo $closing; ?>
<script type="text/javascript">
	jQuery( document ).ready(function($) {
		var media_file_frame;
		jQuery( "#ctc_tax_img_upload" ).click(function(e) {
			e.preventDefault();
			if( media_file_frame ) {
				media_file_frame.open();
				return;
			}
			media_file_frame = wp.media.frames.file_frame = wp.media( {
				title: 'Select An Image',
				button: { text: 'Use Image' },
				class: $(this).attr('id')
      } );
			
			media_file_frame.on( 'select', function(){
				var imgsrc = media_file_frame.state().get( 'selection' ).first().toJSON();
				$( "#ctc_tax_image" ).val( imgsrc.url );
				$( "#ctc_tax_img" ).attr( "src", imgsrc.url );
				$( "#ctc_tax_img" ).css('display','block');
				$( "#ctc_tax_img_delete" ).show();
				$( "#ctc_tax_img_upload" ).hide();
			} );
			media_file_frame.open();
			
		});
		function ctc_img_reset() {
			$( "#ctc_tax_img" ).css('display','none' );
			$( "#ctc_tax_img" ).attr( "src", '' );
			$( "#ctc_tax_image" ).val( '' );
			$( "#ctc_tax_img_upload" ).show();
			$( "#ctc_tax_img_delete" ).hide();
		}
		
		$( "#ctc_tax_img_delete" ).click( ctc_img_reset );
<?php if( empty( $mid ) ): ?>
		$( "#submit" ).on( "click", ctc_img_reset );
<?php endif ?>
	});
</script>
<?php 
		}

		/**
		 * Save image data
		 *
		 * @since 0.1
		 * @param mixed $term_id ID of the taxonomy term to update
		 */
		function save_tax_img( $term_id ) {
			$ctc_tax_image = get_option( 'ctc_tax_img_' . $term_id );
			if ( isset( $_POST[ 'ctc_tax_image' ] ) ) {
				// Update the image if specified
				update_option( 'ctc_tax_img_' . $term_id, $_POST[ 'ctc_tax_image' ] );
			} else {
				// Delete an image if it has been set
				if( $ctc_tax_image )
					delete_option( 'ctc_tax_img_' . $term_id );
			}
		}

		/**
		 * Delete image data
		 *
		 * @since 0.1
		 * @param mixed $term_id ID of the taxonomy term to remove the image from
		 */
		function delete_tax_img( $term_id ){
			$ctc_tax_image = get_option( 'ctc_tax_img_' . $term_id );
			if( $ctc_tax_image )
				delete_option( 'ctc_tax_img_' . $term_id );
		} 

		/**
		 * Add an image column 
		 *
		 * @since 0.1
		 * @param mixed $term_id ID of the taxonomy term to remove the image from
		 */
		function img_tax_column( $columns ){
			$columns[ 'ctc_tax_image' ] = __( 'Taxonomy Image', 'ctcex' );
			return $columns;
		}

		/**
		 * Display an image in the column 
		 *
		 * @since 0.1
		 * @param mixed $term_id ID of the taxonomy term to remove the image from
		 */
		function edit_tax_img_column( $out, $column_name, $term_id ) {
			if( $column_name != 'ctc_tax_image' ) return $out;
			$imgsrc = get_option( 'ctc_tax_img_' . $term_id );
			if( $imgsrc ) {
				$img = '<img src="' .  $imgsrc .'" style="max-width:75px; max-height:75px;" width="75"/>';
				$out = $img;
			}
			return $out; 
		}
		
		function upgrade(){
			// Upgrade the existing tax image to term_meta if the WP version allows it
			if( ! function_exists( 'add_term_meta') ) return;
			
			foreach( $this->taxonomies as $tax ) {
				$terms = get_terms( $tax );
				foreach ( $terms as $term ) {
					$id = $term->term_id;
					$img = get_option( 'ctc_tax_img_' . $id );
					if( $img ) {
						$meta = update_term_meta( $id, $tax . '_image', $img );
					}
				}
			}
		}
		
	}

}

