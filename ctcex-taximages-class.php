<?php
/*
		Custom class for adding taxonomy images to CTC taxonomies (by default only for ctc_sermon_series).
*/
// No direct access
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'CTCEX_TaxImages' ) ) {
	class CTCEX_TaxImages {
		
		public $version = '1.5.1';
		
		public $taxonomies = array( 'ctc_sermon_series' ) ;
		
		function __construct( $ctcex_version ) {
			
			// Church Theme Content is REQUIRED
			if ( ! class_exists( 'Church_Theme_Content' ) ) return;

			$this->taxonomies = apply_filters( 'ctc_tax_img_taxonomies', $this->taxonomies );
			
			add_action('admin_head', array( $this, 'admin_head' ) ) ;
			add_action('edit_term', array( $this, 'save_tax_img' ) );
			add_action('create_term', array( $this, 'save_tax_img' ) );
			add_action('delete_term', array( $this, 'delete_tax_img' ) );
			add_action('quick_edit_custom_box', array( $this, 'img_tax_quick_edit' ), 10, 2 ); 
		}
		
		/**
		 * Add actions to handle image fields on taxonomies
		 *
		 * @since 0.1
		 */
		function admin_head(){
			
			$taxonomies = $this->taxonomies;
			
			if( version_compare( $this->version, '1.4.1', '>' ) )	{
				$this->upgrade();
			}
			
			foreach( $taxonomies as $tax ) {
				add_action( "{$tax}_add_form_fields", array( $this, 'add_new_tax_image' ) );
				add_action( "{$tax}_edit_form_fields", array( $this, 'edit_tax_image' ), 10, 2 );
				add_filter( "manage_edit-{$tax}_columns", array( $this, 'img_tax_column' ) );
				add_filter( "manage_{$tax}_custom_column", array( $this, 'edit_tax_img_column' ), 10, 3 );
			}
		}

		/**
		 * Output image form in the new term screen
		 *
		 * @since 1.5
		 * @param string  $tax          Taxonomy
		 */
		function add_new_tax_image( $tax ){
			wp_enqueue_media();
		?>
			
			<div class="form-field term-group term-featured-image">
				<?php $this->img_tax_field_label( get_taxonomy( $tax )->label ); ?>
				<?php $this->img_tax_field_description( get_taxonomy( $tax )->label ); ?>				
				<?php $this->img_tax_field_thumbnail( '', $tax ); ?>
				<?php $this->img_tax_field_buttons(); ?>
				<?php $this->img_tax_field_script(); ?>
			</div>
			
		<?php 
		}
		
		/**
		 * Output image form in the term edit screen
		 *
		 * @since 1.5
		 * @param string  $term         Taxonomy term to display form for
		 * @param string  $tax          Taxonomy
		 */
		function edit_tax_image( $term, $tax ){
			wp_enqueue_media();
		
			$term_id = intval( $term->term_id );
			
			$imgsrc = $this->get_tax_image( $tax, $term_id );
			$imgsrc = $imgsrc ? esc_attr( $imgsrc ) : '';
			
		?>
		
			<tr class="form-field term-group-wrap term-featured-image">
				<th scope="row">
					<?php $this->img_tax_field_label( get_taxonomy( $tax )->label ); ?>
				</th>
				<td>
					<?php $this->img_tax_field_thumbnail( $imgsrc, $tax ); ?>
					<?php $this->img_tax_field_description( get_taxonomy( $tax )->label ); ?>
					<?php $this->img_tax_field_buttons(); ?>
					<?php $this->img_tax_field_script(); ?>
				</td>
			</tr>
			
		<?php 
		}
		
		/**
		 * Output quick edit form 
		 *
		 * @since 1.5
		 * @param string  $column_name  Column name in taxonomy list
		 * @param mixed   $empty        Not used
		 * @param string  $tax          Taxonomy 
		 */
		function img_tax_quick_edit( $column_name, $empty ){
			if( ! isset( $_GET[ 'taxonomy' ] ) ) return false;
			$tax = $_GET[ 'taxonomy' ];
			if( ! in_array( $tax, $this->taxonomies ) ) return false;
			if( 'ctc_tax_image' != $column_name ) return false;
			?>
			
				<input type="hidden" name="<?php echo $tax; ?>_image" id="ctc_tax_image-qe" value="" />
			
			<?php
			
			$this->img_tax_quick_edit_script();
		}
		
		/**
		 * Output field label 
		 *
		 * @since 1.5
		 * @param string  $term_label   Label of term to use in label
		 */
		function img_tax_field_label( $term_label = '' ){
		?>
		
			<label for="ctc_tax_image">
				<?php _e( "$term_label Image", 'ctcex' ); ?>
			</label>

		<?php
		}
		
		/**
		 * Output field thumbnail 
		 *
		 * @since 1.5
		 * @param string  $img          URL for term image
		 * @param string  $tax          Taxonomy
		 */
		function img_tax_field_thumbnail( $img = '', $tax ){
			$placeholder = $img ? $img : plugins_url( 'placeholder.png', __FILE__ );
		?>
		
			<input type="hidden" name="<?php echo $tax; ?>_image" id="ctc_tax_image" value="<?php echo  $img; ?>" />
			<div id="term_thumbnail">
				<img id="ctc_tax_img" src="<?php echo $placeholder; ?>" style="max-width: 200px; border: 1px solid #ccc; padding: 5px; box-shadow: 5px 5px 10px #ccc; margin: 10px 0; cursor: pointer" />
			</div>
		
		<?php
		}
		
		/**
		 * Output field buttons 
		 *
		 * @since 1.5
		 */
		function img_tax_field_buttons(){
		?>
		
			<input type="button" class="button button-secondary" value="<?php _e( 'Add Image', 'ctcex' ); ?>" id="ctc_tax_img_upload"/>
			<input type="button" class="button button-secondary" value="<?php _e( 'Remove Image', 'ctcex' ); ?>" id="ctc_tax_img_delete"/>
		
		<?php
		}
		
		/**
		 * Output quick edit scripts 
		 *
		 * @since 1.5
		 */
		function img_tax_quick_edit_script(){
		?>
			<script>
			(function($) {
				<?php // Create events to handle showing and removal of the quick edit form ?>
				$.each(['show', 'remove'], function(i , ev){
					var el = $.fn[ev];
					$.fn[ev] = function(){
						this.trigger(ev);
						return el.apply(this, arguments);
					}
				});
				
				$(document).ready( function(){
					
					$('#the-list').on('click','.editinline',function( event ){
						<?php // Get the term id ?>
						term_id = parseInt( $(this).parents( 'tr' ).attr('id').replace('tag-', '') );
						if( term_id > 0 ){
							<?php // Get the details from the column ?>
							tag_row = $( '#tag-' + term_id );
							ctc_img_col = $( '.column-ctc_tax_image', tag_row ).clone();
							img = $( '.column-ctc_tax_image > img', tag_row ).attr( 'src' );
							
							<?php // Put the img information in the hidden QE field ?>
							$(document).on('show', '#edit-' + term_id, function(){
								$('input#ctc_tax_image-qe', this).val( img );
							});
							
							<?php // Restore the image to the column after closing the QE form ?>
							$(document).on('remove', '#edit-' + term_id, function(){								
								<?php // Detect form cancellation to only restore when needed ?>
								cancelled = $( '.column-name', $(this).prev() ).next().hasClass('ctc_tax_image');
								if( ! cancelled )
									$( '.column-name', $(this).prev() ).after( ctc_img_col );
							});
						};
					});
				})
			})(jQuery);
			</script>
		<?php 
		}
		
		/**
		 * Output field scripts 
		 *
		 * @since 1.5
		 */
		function img_tax_field_script(){
		?>
			<script>
			(function( $ ){
					
				if ( '' == $( '#ctc_tax_image' ).val() ){
					$( '#ctc_tax_img_delete' ).hide();
				} else {
					$('#ctc_tax_img_upload').val( '<?php _e( 'Replace Image', 'ctcex' ); ?>' );
				}
				
				// Uploading files
				var media_frame;

				$( '#ctc_tax_img' ).click( function( event ){
					$( '#ctc_tax_img_upload' ).click();
				});
				
				$( '#ctc_tax_img_upload' ).click( function( event ){
					event.preventDefault();

					if ( media_frame ) {
						media_frame.open();
						return;
					}

					// Show media selection
					media_frame = wp.media.frames.downloadable_file = wp.media({
						title: '<?php _e( 'Set Image', 'ctcex' ); ?>',
						button: {
							text: '<?php _e( 'Set Image', 'ctcex' ); ?>'
						},
						multiple: false
					});

					// Handle media selection
					media_frame.on( 'select', function() {
						var attachment = media_frame.state().get('selection').first().toJSON();
						var imgsrc = attachment.url;
						jQuery('#ctc_tax_image').val( imgsrc );
						jQuery('#ctc_tax_img').attr( 'src', imgsrc );
						jQuery('#ctc_tax_img_delete').show();
						jQuery('#ctc_tax_img_upload').val( '<?php _e( 'Replace Image', 'ctcex' ); ?>' );
					});

					// Finally, open the modal.
					media_frame.open();
				});
				
				// Handle the Remove button click
				$( '#ctc_tax_img_delete' ).click( function( event ){
					$('#ctc_tax_img').attr('src', '<?php echo plugins_url( 'placeholder.png', __FILE__ ) ?>');
					$('#ctc_tax_image').val('');
					$('#ctc_tax_img_delete').hide();
					$('#ctc_tax_img_upload').val( '<?php _e( 'Add Image', 'ctcex' ); ?>' );
					return false;
				});
				
			})( jQuery );
			</script>
			<div class="clear"></div>
			
		<?php	 
		}
		
		/**
		 * Output field description 
		 *
		 * @since 1.5
		 */
		function img_tax_field_description( $tax_label = 'term' ){
			return;
		?>
		
			<span class="description"><?php echo sprintf( __( 'Choose an image to associate with this %s.', 'ctcex' ), strtolower( $tax_label ) ); ?></span>
		
		<?php
		}
		
		/**
		 * Save image data
		 *
		 * @since 0.1
		 * @param mixed  $term_id       ID of the taxonomy term to update
		 */
		function save_tax_img( $term_id ) {
			
			$tax = isset( $_REQUEST['taxonomy' ] ) ? $_REQUEST['taxonomy' ] : false;
			if ( ! $tax ) return;
			
			$img = isset( $_REQUEST[ "{$tax}_image" ] ) ? $_REQUEST[ "{$tax}_image" ] : false;
			
			if ( $img ) {
				$this->update_image( $tax, $term_id, $img );
			} else {
				$this->delete_image( $tax, $term_id );
			}
			
		}

		/**
		 * Delete image data
		 *
		 * @since 0.1
		 * @param mixed  $term_id       ID of the taxonomy term to remove the image from
		 */
		function delete_tax_img( $term_id ){
			
			$tax = isset( $_REQUEST['taxonomy' ] ) ? $_REQUEST['taxonomy' ] : false;
			if ( ! $tax ) return;
			
			$this->delete_image( $tax, $term_id );
			
		} 

		/**
		 * Add an image column 
		 *
		 * @since 0.1
		 * @param array  $columns        Columns shown by default
		 */
		function img_tax_column( $columns ){
			
			if( empty( $columns) ) return $columns;
			
			$new_columns = array();
			$new_columns[ 'cb' ] = $columns[ 'cb' ];
			$new_columns[ 'name' ] = $columns[ 'name' ];
			$new_columns[ 'ctc_tax_image' ] = __( 'Image', 'ctcex' );
			
			unset( $columns[ 'cb' ] );			
			unset( $columns[ 'name' ] );				
			
			return array_merge( $new_columns, $columns );
			
		}

		/**
		 * Display an image in the column 
		 *
		 * @since 0.1
		 * @param mixed   $out          Column output
		 * @param string  $column_name  Slug of column
		 * @param mixed   $term_id      ID of the taxonomy term to remove the image from
		 */
		function edit_tax_img_column( $out, $column_name, $term_id ) {
			if( $column_name != 'ctc_tax_image' ) return $out;
			
			$tax = isset( $_REQUEST['taxonomy' ] ) ? $_REQUEST['taxonomy' ] : false;
			if ( ! $tax ) return $out;
			
			$imgsrc = $this->get_tax_image( $tax, $term_id );
			
			if( empty( $imgsrc ) ) {
				$imgsrc = plugins_url( 'placeholder.png', __FILE__ );
			}
				
			$out .= '<img src="' .  $imgsrc .'" class="wp-post-image" style="max-width:75px; max-height:75px;" width="75" />';
			
			return $out; 
			
		}
		
		/**
		 * Upgrade to use term meta 
		 *
		 * @since 1.5
		 */
		function upgrade(){
			// Upgrade the existing tax image to term_meta if the WP version allows it
			if( ! function_exists( 'add_term_meta') ) return;
			
			// Check if the upgrade has already been performed
			$update_complete = get_option( 'ctcex_taximage_update_complete' );
			if( $update_complete ) return;
			
			$update = true;
			foreach( $this->taxonomies as $tax ) {
				// Check every taxonomy
				$terms = get_terms( $tax );
				// Check every term in a taxonomy
				foreach ( $terms as $term ) {
					$id = $term->term_id;
					$img = get_option( "ctc_tax_img_$id" );
					// Check if term has an image
					if( $img ) {
						// Use term meta 
						$meta = add_term_meta( $id, "{$tax}_image", $img );
						
						// Delete the old style option if the term was successfully created
						$update &= ! is_wp_error( $meta );
						if( ! is_wp_error( $meta ) ) {
							delete_option( "ctc_tax_img_$id" );
						} else {
							$this->log( 'Upgrade error', $update );
						}
					}
				}
			}
			
			// Create an option to notify a later update of completed updates
			if( $update ){
				update_option( 'ctcex_taximage_update_complete', true );
			}
			
		}
		
		/**
		 * Fetch image from db 
		 *
		 * @since 1.5
		 * @param string  $tax          Taxonomy
		 * @param mixed   $term_id      ID of the taxonomy term to get the image from
		 */
		static function get_tax_image( $tax, $term_id ) {
			
			if( function_exists( 'get_term_meta' ) ) {
				$img = get_term_meta( $term_id, "{$tax}_image", true );
			} else {
				$img = get_option( "ctc_tax_img_{$term_id}" );
			}			
			
			return $img;
		}
		
		/**
		 * Update image in db 
		 *
		 * @since 1.5
		 * @param string  $tax          Taxonomy
		 * @param mixed   $term_id      ID of the taxonomy term to update the image
		 * @param string  $img          URL to term image
		 */
		function update_image( $tax, $term_id, $img ){
			
			if( function_exists( 'update_term_meta' ) ){
				$upd = update_term_meta( $term_id, "{$tax}_image", $img );
			} else{
				$upd = update_option( "ctc_tax_img_{$term_id}", $img );
			}
			
			if( ! $upd ){
				$this->log( 'Image update error', $upd );
			}
		}
		
		/**
		 * Delete image from db
		 *
		 * @since 1.5
		 * @param string  $tax          Taxonomy
		 * @param mixed   $term_id      ID of the taxonomy term to remove the image from
		 */
		function delete_image( $tax, $term_id ){
			
			$tax_image = $this->get_tax_image( $tax, $term_id );
			
			// Bail if nothing to delete
			if( empty( $tax_image ) ) return;
			
			if( function_exists( 'delete_term_meta' ) ) {
				$dl = delete_term_meta( $term_id, "{$tax}_image" );
			} else {
				$dl = delete_option( "ctc_tax_img_{$term_id}" );					
			}
			if( ! $dl ){
				$this->log( 'Image delete error', $dl );
			}
		}
		
		/**
		 * Error logging function 
		 *
		 * @since 1.5
		 * @param mixed  $msg           Message to display
		 * @param mixed  $vars          Variables to output
		 */
		function log( $msg, $vars ){
			$origin = debug_backtrace()[1]['function']; // function calling log 
			$calling = debug_backtrace()[2]['function']; // function that called $origin
			error_log( "
			>> Caller '{$origin}'
			>> Message: {$msg}
			>> Called from: '{$calling}'
			>> Data: " . json_encode( $vars ) );
		}
		
	}

}

