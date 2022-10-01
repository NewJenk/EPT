<?php
/**
 * Class for adding column/s to the 'All Posts'.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists('en_p_t_pt') ) :

	class en_p_t_pt { // en_p_t_pt = Encrypted Post Type Post Table

		/**
		 * Adds register hooks.
		 */
		public function __construct() {

			// Register Modified Date Column for CPT
			add_filter( 'manage_en_p_t_posts_columns', array($this, 'en_p_t_pt_modified_col_register') );

			add_action( 'manage_en_p_t_posts_custom_column', array($this, 'en_p_t_pt_modified_col_display'), 10, 2 );

			add_filter( 'manage_edit-en_p_t_sortable_columns', array($this, 'en_p_t_pt_modified_col_sort') );

			add_action( 'pre_get_posts', array($this, 'en_p_t_pt_default_orderby'), 9 );


		}




		// Register Modified Date Column for CPT
		function en_p_t_pt_modified_col_register( $columns ) {

			$columns['created']  = __( 'Created', 'en-p-t' );
			$columns['modified'] = __( 'Last Updated', 'en-p-t' );

			unset($columns['date']);

			return $columns;

		}




		function en_p_t_pt_modified_col_display( $column_name, $post_id ) {

			switch ( $column_name ) {

				case 'created':

					global $post; 

					echo '<p class="mod-date">';

						echo sprintf( esc_html__('%s at %s', 'en-p-t'), get_the_date(), get_the_time() );

						/* if ( ! empty( get_the_author() ) ) {

							echo '<small>' . esc_html__( 'by', 'en-p-t' ) . ' <strong>' . get_the_author() . '<strong></small>';

						} else {

							echo '<small>' . esc_html__( 'by', 'en-p-t' ) . ' <strong>' . esc_html__( 'UNKNOWN', 'en-p-t' ) . '<strong></small>';

						} */

					echo '</p>';

					break;

				case 'modified':

					global $post; 

					echo '<p class="mod-date">';

						echo sprintf( esc_html__('%s at %s', 'en-p-t'), get_the_modified_date(), get_the_modified_time() );

						/* if ( ! empty( get_the_modified_author() ) ) {

							echo '<small>' . esc_html__( 'by', 'en-p-t' ) . ' <strong>' . get_the_modified_author() . '<strong></small>';

						} */

					echo '</p>';

					break; // end all case breaks

			}

		}




		function en_p_t_pt_modified_col_sort( $columns ) {

			$columns['created'] = 'created';
			$columns['modified'] = 'modified';

			return $columns;
			
		}




		/**
		 * Set default order of posts list by modified date (descending).
		 *
		 * @version 1.0.0
		 * @since 1.0.0
		 */
		function en_p_t_pt_default_orderby( $query ) {   

			if ( ! is_admin() ) {
				
				return;

			}

			// Nothing to do:  
			if ( ! $query->is_main_query() || 'en_p_t' != $query->get( 'post_type' )  ) {

				return;

			}

			$orderby = $query->get( 'orderby');      
		
			switch ( $orderby ) {
				case '' :  // <-- The default empty case
					$query->set( 'order', 'desc' );  
					$query->set( 'orderby',  'modified' );
					break;
			}
		}




	}

	// initialize
	$en_p_t_pt = new en_p_t_pt;

endif;
