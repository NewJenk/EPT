<?php
/**
 * Class for registering the post type(s) and taxonom(y/ies).
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists('en_p_t_r') ) :

	class en_p_t_r {

		/**
		 * Adds register hooks.
		 */
		public function __construct() {

            // Use constant names if they exist, otherwise use Note/Notes.
            $name = defined('EN_P_T_NAME') ? EN_P_T_NAME : esc_html__( 'Notes', 'en-p-t' );
            $singular_name = defined('EN_P_T_S_NAME') ? EN_P_T_S_NAME : esc_html__( 'Note', 'en-p-t' );
            // Icon constant
            $icon = defined('EN_P_T_ICON') ? EN_P_T_ICON : 'dashicons-category';

			// vars
			$this->settings = array(
				'name'			    => esc_html($name),
                'singular_name'	    => esc_html($singular_name),
                'icon'              => esc_html($icon),
			);

            // Register CPT.
			add_action( 'init', array($this, 'en_p_t_r_post_type'), 0 );

            // Add messages for the CPT.
			add_filter( 'post_updated_messages', array($this, 'en_p_t_r_messages') );

            // Register taxonomy.
			add_action( 'init', array($this, 'en_p_t_r_taxonomy'), 0 );

            // Add tag taxonomy to sidebar.
            add_action( 'admin_menu', array($this, 'en_p_t_r_add_tags_to_menu_item') );

            // When on a tag set the sidebar active state.
            add_filter( 'parent_file', array($this, 'en_p_t_r_set_sidebar_active_item'), 10, 2 );

            // Set parent menu item when on taxonomy screen.
            add_filter( 'submenu_file', array($this, 'en_p_t_r_set_sidebar_active_item_tax') );

		}




        /*
		 * Custom post type.
		 */
		function en_p_t_r_post_type() {

			$main_plugin_class = new en_p_t();

			include_once( $main_plugin_class->settings['path'] . 'inc/classes/class-rest-posts-controller.php' );

			$labels = array(
				'name'                => esc_html($this->settings['name']),
				'singular_name'       => esc_html($this->settings['singular_name']),
				'menu_name'           => esc_html($this->settings['name']),
				'parent_item_colon'   => sprintf( __('Parent %s:', 'en-p-t'), esc_html($this->settings['singular_name']) ),
				'all_items'           => sprintf( __('All %s', 'en-p-t'), esc_html($this->settings['name']) ),
				'view_item'           => sprintf( __('View %s', 'en-p-t'), esc_html($this->settings['singular_name']) ),
				'view_items'          => sprintf( __('View %s', 'en-p-t'), esc_html($this->settings['name']) ),
				'add_new_item'        => sprintf( __('Add New %s', 'en-p-t'), esc_html($this->settings['singular_name']) ),
				'add_new'             => __( 'Add New', 'en-p-t' ),
				'edit_item'           => sprintf( __('Edit %s', 'en-p-t'), esc_html($this->settings['singular_name']) ),
				'update_item'         => sprintf( __('Update %s', 'en-p-t'), esc_html($this->settings['singular_name']) ),
				'search_items'        => sprintf( __('Search %s', 'en-p-t'), esc_html($this->settings['name']) ),
				'not_found'           => __( 'Not Found', 'en-p-t' ),
				'not_found_in_trash'  => __( 'Not Found in Trash', 'en-p-t' ),
			);
			
			$args = array(
				'label'               => esc_html($this->settings['singular_name']),
				'description'         => esc_html($this->settings['name']),
				'labels'              => $labels,
				'supports'            => array( 'title', 'editor', 'revisions' ),
				'public'              => true,
				'publicly_queryable'  => false,
                'has_archive'         => false,
                'query_var'           => false,
				'rewrite'			  => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => true,
				'menu_position'       => 5,
				'exclude_from_search' => true,
				'show_in_rest'        => true,
				/**
				 * REST API endpoint/s only available to logged in (authenticated) users.
				 *
				 * @link https://wordpress.stackexchange.com/a/232654
				 */
				'rest_controller_class' => 'EN_P_T_Private_Posts_Controller',
				'capability_type'	  => 'page',
				'menu_icon'	          => esc_html($this->settings['icon']),
			);

			register_post_type( 'en_p_t', $args );

		}




		/*
		 * Custom post type messages.
		 */
		function en_p_t_r_messages( $messages ) {

		  global $post, $post_ID;

		  $messages['en_p_t'] = array(

			0 => '', 
			1 => sprintf( __('%1$d Updated. <a href="%2$d">View %1$d</a>', 'en-p-t'), esc_html($this->settings['singular_name']), esc_url( get_permalink($post_ID) ) ),
			2 => __('Custom Field Updated.', 'en-p-t'),
			3 => __('Custom Field Deleted.', 'en-p-t'),
			4 => sprintf( __('%s Updated.', 'en-p-t'), esc_html($this->settings['singular_name']) ),
			5 => isset($_GET['revision']) ? sprintf( __('%1$d Restored to Revision From %2$d', 'en-p-t'), esc_html($this->settings['singular_name']), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __('%1$d Added. <a href="%2$d">View %1$d</a>', 'en-p-t'), esc_html($this->settings['singular_name']), esc_url( get_permalink($post_ID) ) ),
			7 => sprintf( __('%s Saved.', 'en-p-t'), esc_html($this->settings['singular_name']) ),
			8 => sprintf( __('%1$d Submitted. <a target="_blank" href="%2$d">Preview %1$d</a>', 'en-p-t'), esc_html($this->settings['singular_name']), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			9 => sprintf( __('%1$d Scheduled for: <strong>%2$s</strong>. <a target="_blank" href="%3$s">Preview %1$d</a>', 'en-p-t'), esc_html($this->settings['singular_name']), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			10 => sprintf( __('%1$d Draft Updated. <a target="_blank" href="%2$d">Preview %1$d</a>', 'en-p-t'), esc_html($this->settings['singular_name']), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),

		  );

		  return $messages;

		}




        /**
		 * Register taxonomy
		 *
		 * @link https://developer.wordpress.org/reference/functions/register_taxonomy/#comment-399
		 *
		 */
		function en_p_t_r_taxonomy() {

			// Labels for "Tag" taxonomy
			$labels = array(
				'name'              => _x( 'Tags', 'taxonomy general name', 'en-p-t' ),
				'singular_name'     => _x( 'Tag', 'taxonomy singular name', 'en-p-t' ),
				'search_items'      => __( 'Search Tags', 'en-p-t' ),
				'all_items'         => __( 'All Tags', 'en-p-t' ),
				'parent_item'       => __( 'Parent Tag', 'en-p-t' ),
				'parent_item_colon' => __( 'Parent Tag:', 'en-p-t' ),
				'edit_item'         => __( 'Edit Tag', 'en-p-t' ),
				'update_item'       => __( 'Update Tag', 'en-p-t' ),
				'add_new_item'      => __( 'Add New Tag', 'en-p-t' ),
				'new_item_name'     => __( 'New Tag Name', 'en-p-t' ),
				'menu_name'         => __( 'Manage Tags', 'en-p-t' ),
			);
		
			$args = array(
				'hierarchical'      => false,
				'labels'            => $labels,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'		=> true,
				'query_var'			=> true,
				'public'			=> false,
				'rewrite'           => false,
			);

			// Register "Type" taxonomy for Video post type
			register_taxonomy( 'en_p_t_tag', array( 'en_p_t' ), $args );
			
		}



        /**
         * Display each tag in the sidebar
         */
        function en_p_t_r_add_tags_to_menu_item() {

            // remove_submenu_page( 'edit.php?post_type=en_p_t', 'post-new.php?post_type=en_p_t' );

            $terms = get_terms( 'en_p_t_tag', array( 'hide_empty' => false, 'orderby' => 'name', 'order' => 'DESC' ) );

            foreach ( $terms as $term ) {

				$archived_label = __('archived', 'en-p-t');

				// Don't display label if the description is $archived_label.
				if ( strtolower($term->description) !== esc_html($archived_label) ) {

					add_submenu_page(
						'edit.php?post_type=en_p_t',
						'',
						$term->name,
						'read',
						'edit.php?post_type=en_p_t&en_p_t_tag=' . $term->slug,
						'',
						2
					);

				}

            }

        }




        /**
         * When on a tag set the sidebar active state.
         */
        function en_p_t_r_set_sidebar_active_item() {
        
            global $submenu_file;
        
            if (
                isset( $_GET['post_type'] ) &&
                $_GET['post_type'] === 'en_p_t' &&
                isset( $_GET['en_p_t_tag'] )
            ) {
                $submenu_file .= '&en_p_t_tag=' . $_GET['en_p_t_tag'];
            }
        
            return $parent_file;

        }




        /**
         * @link https://stackoverflow.com/a/63496393
         */
        function en_p_t_r_set_sidebar_active_item_tax($submenu_file) {

            global $parent_file;
        
            $edit_cpt = 'edit.php?post_type=en_p_t';

            $tag_taxonomy = 'edit-tags.php?taxonomy=en_p_t_tag&post_type=en_p_t';
            $tag_taxonomy_alt = 'edit-tags.php?taxonomy=en_p_t_tag';

            if ( esc_html($edit_cpt) == $submenu_file ) {

                $parent_file = $edit_cpt;

            } elseif ( esc_html($tag_taxonomy) == $submenu_file ) {

                $parent_file = $edit_cpt;
                $submenu_file = esc_html($tag_taxonomy);

            } elseif ( esc_html($tag_taxonomy_alt) == $submenu_file ) {

                $parent_file = $edit_cpt;
                $submenu_file = esc_html($tag_taxonomy);

            }
            
            return $submenu_file;

        }




        /**
		 * Flushes rewrite rules on activation/deactivation so user doesn't have to do it manually
		 *
		 * @link https://codex.wordpress.org/Function_Reference/flush_rewrite_rules
		 *
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public static function en_p_t_r_flush_rewrites_activate() {

			$cpt_class = new en_p_t_r();
			$cpt_class->en_p_t_r_post_type();
			$cpt_class->en_p_t_r_taxonomy();
			flush_rewrite_rules();

		}




        /**
		 * Flushes rewrite rules on activation/deactivation so user doesn't have to do it manually
		 *
		 * @link https://codex.wordpress.org/Function_Reference/flush_rewrite_rules
		 *
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public static function en_p_t_r_flush_rewrites_deactivate() {

			flush_rewrite_rules();

		}




	}

	// initialize
	$en_p_t_r = new en_p_t_r;

endif;
