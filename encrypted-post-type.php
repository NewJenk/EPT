<?php
/*
Plugin Name: Encrypted Post Type
Plugin URI: https://encryptedposttype.com
Description: Encrypted Post Type provides a custom post type where the content of each post is encrypted.
Version: 1.0.1
Author: Shaun Jenkins
Author URI: https://github.com/NewJenk
Text Domain: encrypted-post-type
License: GPL3
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * On plugin activation create the "encryption key encryptor" and save it in the options table AND create a directory that will be used to store the encryption keys.
 */
register_activation_hook(   __FILE__, array( 'en_p_t', 'en_p_t_initialise' ) );
/**
 * Flushes rewrite rules on activation/deactivation so user doesn't have to do it manually
 *
 * @link https://wordpress.stackexchange.com/a/25979/115004
 */
register_activation_hook(   __FILE__, array( 'en_p_t_r', 'en_p_t_r_flush_rewrites_activate' ) );
register_deactivation_hook( __FILE__, array( 'en_p_t_r', 'en_p_t_r_flush_rewrites_deactivate' ) );

if ( ! class_exists('en_p_t') ) :

	/**
	 * CLASSES
	 */

	require_once plugin_dir_path( __FILE__ ) . 'inc/classes/class-register-post-type.php';
	require_once plugin_dir_path( __FILE__ ) . 'inc/classes/class-posts-table.php';

	/**
	 * CLASSES - END
	 */

	class en_p_t {

		/**
		 * Adds register hooks.
		 */
		public function __construct() {

			// vars
			$this->settings = array(
				'plugin'			=> 'Encrypted Post Type',
				'version'			=> '1.0.1',
				'url'				=> plugin_dir_url( __FILE__ ),
				'path'				=> plugin_dir_path( __FILE__ ),
			);

			// Set text domain
			load_plugin_textdomain( 'encrypted-post-type', false, dirname( plugin_basename(__FILE__) ) . '/languages/' );

			// Decrypt the content to display in the block editor (Gutenberg).
			add_filter( 'rest_prepare_en_p_t', array($this, 'en_p_t_prepare_filter'), 10, 3 );

			// Enqueue scripts/styles (admin)
			add_action( 'admin_enqueue_scripts', array( $this, 'en_p_t_scripts_and_styles_admin' ), 99 );

			// Create a unique key for this post and save it as a file.
			add_action( 'wp_insert_post', array( $this, 'en_p_t_create_post_key'), 10, 3 );

			add_action( 'save_post', array( $this, 'en_p_t_encrypt_the_post'), 10, 2 );

			// add_filter( 'wp_get_revision_ui_diff', array( $this, 'en_p_t_display_decrypted_revision'), 10, 3 );

			// Found this filter in wp-admin/includes/revision.php.
			add_filter( '_wp_post_revision_field_post_content', array( $this, 'en_p_t_display_decrypted_revision_with_diffs'), 10, 4 );

			add_action( 'save_post', array( $this, 'en_p_t_save_meta_to_revision'), 12, 2  );

			add_action( 'wp_restore_post_revision', array( $this, 'en_p_t_restore_revision'), 10, 2 );

			add_filter( 'wp_insert_post_data', array( $this, 'en_p_t_set_post_to_private' ) );

			add_action( 'template_redirect', array( $this, 'en_p_t_redirect_404_to_edit_screen') );

		}




		/**
		 * Create a random token.
		 * 
		 * @version 1.0.0
		 * @since 1.0.0
		 *
		 * @link https://www.php.net/manual/en/function.random-bytes.php
		 */
		function en_p_t_random_token($length = 32) {

			if ( function_exists('random_bytes') ) {

				return bin2hex(random_bytes($length));

			}

			if ( function_exists('mcrypt_create_iv') ) {

				return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));

			}

			if ( function_exists('openssl_random_pseudo_bytes') ) {

				return bin2hex(openssl_random_pseudo_bytes($length));

			}

		}




		/**
		 * Enqueue scripts/styles (admin)
		 * 
		 * @version 1.0.0
		 * @since 1.0.0
		 */
		function en_p_t_scripts_and_styles_admin() {

			$screen = get_current_screen();

			wp_enqueue_style(
				'encrypted-post-type',
				plugin_dir_url( __FILE__ ) .  'assets/css/admin/admin-style.css',
				'',
				$this->settings['version'],
				'all'
			);

			// This conditional is used as this js is only needed on the edit screen
			if ( get_current_screen()->is_block_editor() && $screen->post_type == 'en_p_t' ) {

				wp_enqueue_script(
					'encrypted-post-type-edit-screen',
					plugin_dir_url( __FILE__ ) .  'assets/js/admin/onEditScreen.js',
					array('react', 'react-dom', 'wp-data', 'wp-edit-post'),
					'1.0.0',
					true
				);

			}

		}




		/**
		 * Create the "encryption key encryptor" and save it in the options table AND create a directory that will be used to store the encryption keys.
		 *
		 * NOTE: The content in this function was originally in "en_p_t_initialize_multisite" but I've put it in a separate function because I'm also
		 * going to call it in "en_p_t_create_post_key" just to make sure the main key and directory get added even if this plugin gets installed
		 * and activated AFTER a subsite has been created.
		 * 
		 * @version 1.0.0
		 * @since 1.0.0
		 */
		function en_p_t_init_encryption_setup() {

			// Only do this if the option doesn't already exist - just incase this action fires when a subsite already exists
			if ( ! get_option('en_p_t_ek_e') ) :

				// @link https://www.php.net/manual/en/function.random-bytes.php
				$key = $this->en_p_t_random_token(32);

				add_option( 'en_p_t_ek_e', $key );

				/**
				 * Create a directory that will be used to store the encryption keys.
				 *
				 * NOTE: This has been tested locally and the dir gets created fine, but if I have issues with getting the correct
				 * subsite uploads dir then this stack question/answer may come in handy: https://stackoverflow.com/a/35784388/6561019
				 *
				 * @link https://stackoverflow.com/a/45507980/6561019
				 */
				$uploads_dir = trailingslashit( wp_upload_dir()['basedir'] ) . 'en_p_t_ek_config';
				wp_mkdir_p( $uploads_dir );

				// Create an index.php to add to the "en_p_t_ek_config" directory
				global $wp_filesystem;
				WP_Filesystem(); // Initial WP file system
				$wp_filesystem->put_contents( $uploads_dir . '/index.php', '', 0644 ); // Store the empty index.php file

			endif;

		}




		/**
		 * On plugin activation create the "encryption key encryptor" and save it in the options table AND create a directory that will be used to store the encryption keys.
		 *
		 * @version 1.0.0
		 * @since 1.0.0
		 */
		public static function en_p_t_initialise() {

			$plugin_class = new en_p_t();

			$plugin_class->en_p_t_init_encryption_setup();

		}




		/**
		 * @version 1.0.0
		 * @since 1.0.0
		 *
		 * @link https://github.com/WordPress/gutenberg/issues/12081#issuecomment-451631170
		 */
		function en_p_t_prepare_filter( $response, $post, $request ) {

			if ( isset( $response->data['content'] ) ) {
			// if ( in_array( 'content', $response->data) ) { // This didn't work for en_p_t post type, but worked fine for post and page?!

				$post_id = $post->ID;
				$encrypted_content = $response->data['content']['raw'];
				$decrypted_content = $this->en_p_t_decrypt_data($post_id, $encrypted_content);
	
				if ( $decrypted_content ) {

					$response->data['content']['raw'] = $decrypted_content;

				}

			}

			return $response;

		}




		/**
		 * Create a unique key for this post, encrypt it and save it as a separate file (It's VERY important that this is NOT
		 * stored in the DB).
		 *
		 * @version 1.0.0
		 * @since 1.0.0
		 *
		 * @link https://developer.wordpress.org/reference/hooks/wp_insert_post/
		 */
		function en_p_t_create_post_key( $post_id, $post, $update ) {
 
			// If this is a revision, don't do anything
			if ( wp_is_post_revision( $post_id ) || $post->post_type == 'revision' ) {

				return;

			}

			/**
			 * Not the correct post type so don't do anything.
			 */
			if ( get_post_type( $post_id ) !== 'en_p_t' ) {

				return;

			}

			/**
			 * Only do this if the metadata "en_p_t_has_ek" doesn't exist. That meta data is added the first time this function
			 * is triggered (see "update_post_meta" below).
			 */
			if ( ! metadata_exists( 'post', $post_id, 'en_p_t_has_ek' ) ) {

				/**
				 * If there isn't a master key saved in the db then this will do that (it's basically triggering the function that fires on initial setup, there's a check for meta data in the
				 * function so won't fire again if already fired).
				 */
				$this->en_p_t_init_encryption_setup();

				// This is used to make sure the encryption key is only added once.
				add_post_meta( $post_id, 'en_p_t_has_ek', 1 );

				// Get the key that'll be used to encrypt the encryption key
				$encryption_key = get_option('en_p_t_ek_e');

				// @link https://www.php.net/manual/en/function.openssl-encrypt.php
				$cipher = "aes128";

				// Create a unique key for this post
				$key_for_this_post_only = $this->en_p_t_random_token(32);

				/**
				 * Encrypt the encryption key. This encrypts the key saved in a file with the key that's saved in the DB.
				 *
				 * @link https://stackoverflow.com/a/63569169/6561019
				 * @link https://www.php.net/manual/en/function.openssl-encrypt.php
				 */
				$iv_length = openssl_cipher_iv_length($cipher);
				$iv = openssl_random_pseudo_bytes($iv_length);
				/**
				 * This is the key that will be used to encrypt responses for this post, but the key is first encrypted with
				 * $encryption_key before saving it.
				 */
				$encrypted_message = openssl_encrypt($key_for_this_post_only, $cipher, $encryption_key, 0, $iv);

				/**
				 * This is needed as $iv is binary, it needs to be converted to a hexadecimal representation
				 * so it can be stored for later use (the later use being decryption).
				 *
				 * @link https://www.php.net/manual/en/function.bin2hex.php
				 */
				$escaped_iv = bin2hex($iv);

				/**
				 * Using RKM to save the encrypted encryption key and iv on RKM site.
				 */
				if ( $this->en_p_t_rkm_create($post_id, $encrypted_message, $escaped_iv) ) {

					// No need to go any further because the encrypted key and iv have been saved via RKM.
					return;

				/**
				 * Not using RKM so save the encrypted encryption key and iv in a file.
				 */
				} else {

					$key_for_file = '$ek_for_' . $post_id . ' = \'' . $encrypted_message . '\';';
					$iv_for_file = '$iv_for_' . $post_id . ' = \'' . $escaped_iv . '\';';

					/**
					 * Commented out as the use of HEREDOC or NOWDOC syntax is not permitted in wp.org plugins. Using
					 * $content_for_file var immediately below this one instead.
					 */
					/* $content_for_file = <<<EOF
					<?php
					// Exit if accessed directly
					if( ! defined( 'ABSPATH' ) ) exit;
					
					$key_for_file
					$iv_for_file

					EOF; */

					$content_for_file = "<?php\n// Exit if accessed directly\nif( ! defined( 'ABSPATH' ) ) exit;\n\n$key_for_file\n$iv_for_file\n";

					/**
					 * Add a file with the encrypted encryption key in it along with the iv which will be used for
					 * decryption.
					 */
					require_once(ABSPATH . 'wp-admin/includes/file.php');
					$uploads_dir = trailingslashit( wp_upload_dir()['basedir'] ) . 'en_p_t_ek_config';
					global $wp_filesystem;
					WP_Filesystem(); // Initial WP file system
					$wp_filesystem->put_contents( $uploads_dir . '/ek_' . $post_id . '.php', $content_for_file, 0644 ); // Store the empty index.php file

				}

			} else {

				return;

			}

		}



		/**
		 * Get the key and iv. Will either be via REST API or locally.
		 *
		 * @version 1.0.0
		 * @since 1.0.0
		 */
		function en_p_t_get_the_post_key_and_iv($post_id) {

			// Init var.
			$key_and_iv = false;
			// This was added when the post was initially created and the key and iv were successfully saved via the REST API.
			$rest_post_name = get_post_meta( $post_id, '_en_p_t_r_id', true);

			if ( $this->en_p_t_is_rkm() && $rest_post_name ) {

				// Get the REST class.
				$rest_class = new en_p_t_rest;

				$rkm_key_and_iv = $rest_class->en_p_t_rest_get_key($rest_post_name);

				if ( array($rkm_key_and_iv) ) {

					// Init array.
					$key_and_iv = array();

					// Get the variables from the included file.
					$key_and_iv['ek'] = $rkm_key_and_iv[0];
					$key_and_iv['iv'] = $rkm_key_and_iv[1];

					// We've got the key and iv via RKM so let's return them (because we're returning now, none of the code below will execute).
					return $key_and_iv;

				}

			}

			// Get the directory
			$uploads_dir = trailingslashit( wp_upload_dir()['basedir'] ) . 'en_p_t_ek_config';

			// Check that the directory exists
			if ( is_dir($uploads_dir) ) {

				$dir = new DirectoryIterator($uploads_dir);

				// Loop through the files in the directory
				foreach ( $dir as $fileinfo ) {

					if ( ! $fileinfo->isDot() ) {

						// Check the filename is this format
						$file_to_get = preg_match('/^ek_[0-9]+.php+$/', $fileinfo->getFilename());

						// If the file is the correct format then include it
						if ( $file_to_get && $fileinfo->getFilename() == 'ek_' . esc_html($post_id) . '.php' ) {

							include( $fileinfo->getPathname() );

							// Init array.
							$key_and_iv = array();

							$ek_var_name = 'ek_for_' . esc_html($post_id);
							$iv_var_name = 'iv_for_' . esc_html($post_id);

							// Get the variables from the included file.
							$key_and_iv['ek'] = ${$ek_var_name};
							$key_and_iv['iv']  = ${$iv_var_name};

							// No need to continue iterating through files as we've found the one we need.
							break;

						}

					}

				}

			}

			return $key_and_iv;

		}




		/**
		 * @version 1.0.0
		 * @since 1.0.0
		 */
		function en_p_t_key_to_use_to_encrypt_data($ek_to_use, $iv_to_use) {

			$encrypted_key = $ek_to_use;
			// @link https://www.php.net/manual/en/function.openssl-encrypt.php
			$cipher = "aes128";
			// Get the key that was used to encrypt the encryption key
			$encryption_key = get_option('en_p_t_ek_e');
			$iv = $iv_to_use;
			$iv_to_binary = hex2bin($iv_to_use);

			$decrypted_key = openssl_decrypt($encrypted_key, $cipher, $encryption_key, 0, $iv_to_binary);

			return $decrypted_key;

		}




		/**
		 * $revision_id is needed because when comparing revisions the IV needs to come from the revision and not the post.
		 *
		 * @version 1.0.0
		 * @since 1.0.0
		 */
		function en_p_t_decrypt_data($post_id, $content, $revision_id = false) {

			// Get the encrypted encryption key and iv.
			$encrypted_ek_and_iv = $this->en_p_t_get_the_post_key_and_iv($post_id);

			$encrypted_encryption_key = $encrypted_ek_and_iv['ek'];
			$encrypted_iv = $encrypted_ek_and_iv['iv'];

			if ( $encrypted_ek_and_iv ) {
		
				/**
				 * This is the key that's saved in a file for this specific post. BUT, it's encrypted with a key that's
				 * saved in the options table, so it needs to be decrypted before it can be used.
				 */
				$decryption_key = $this->en_p_t_key_to_use_to_encrypt_data($encrypted_encryption_key, $encrypted_iv);

				$cipher				= "aes128";
				$escaped_iv_1 		= $revision_id ? get_metadata( 'post', $revision_id, '_en_p_t_e_iv', true ) : get_post_meta( $post_id, '_en_p_t_e_iv', true);
				$iv_to_binary 		= hex2bin($escaped_iv_1);
				$encrypted_content	= $content;

				/**
				 * In ver 1.0.0 encrypted content was prepended with 'en.p.t' but this was removed in ver 1.0.1.
				 */
				if ( substr($content, 0, 6) == 'en.p.t' ) {

					$encrypted_content = substr($content, 6);
	
				}

				return openssl_decrypt($encrypted_content, $cipher, $decryption_key, 0, $iv_to_binary);

			} else {

				return false;

			}

		}




		/**
		 * This function encrypts the post content.
		 *
		 * @version 1.0.0
		 * @since 1.0.0
		 */
		function en_p_t_encrypt_the_post($post_id, $post) {

			// Init var.
			$is_revision = false;

			/**
			 * The content is encrypted so doesn't need to be encrypted again. has_blocks checks if there are blocks and
			 * encrypted content won't have any blocks (because it's ciphertext).
			 *
			 * @link https://developer.wordpress.org/reference/functions/has_blocks/
			 */
			if ( ! has_blocks($post->post_content) ) {

				return;

			}

			// If this is a revision, get real post ID
			if ( $parent_id = wp_is_post_revision( $post_id ) )  {

				$post_id = $parent_id;

				$is_revision = true;

			}

			/**
			 * Not the correct post type so don't do anything.
			 */
			if ( get_post_type( $post_id ) !== 'en_p_t' ) {

				return;

			}

			/**
			 * There is an encryption key available for this post so we're going to go ahead and encrypt the data.
			 */
			if ( metadata_exists( 'post', $post_id, 'en_p_t_has_ek' ) ) {

				// Get the encrypted encryption key and iv.
				$encrypted_ek_and_iv = $this->en_p_t_get_the_post_key_and_iv($post_id);
	
				$encrypted_encryption_key = $encrypted_ek_and_iv['ek'];
				$encrypted_iv = $encrypted_ek_and_iv['iv'];

				if ( $encrypted_ek_and_iv ) {
			
					/**
					 * This is the key that's saved in a file for this specific post. BUT, it's encrypted with a key that's
					 * saved in the options table (database), so it needs to be decrypted before it can be used.
					 */
					$encryption_key = $this->en_p_t_key_to_use_to_encrypt_data($encrypted_encryption_key, $encrypted_iv);
	
					/**
					 * Create iv, which will be used now for encryption and will also be saved as meta data to this specific post for use
					 * during decryption.
					 */
					$cipher		= "aes128";
					$iv_length	= openssl_cipher_iv_length($cipher);
					$iv 		= openssl_random_pseudo_bytes($iv_length);
					$escaped_iv = bin2hex($iv); //$iv is binary, it needs to be converted to a hexadecimal representation so it can be stored for later use (the later use being decryption).

					update_post_meta( $post_id, '_en_p_t_e_iv', $escaped_iv );

				}

				/**
				 * The post has just been updated, but we're just about to update it with the encrypted value (see wp_update_post below) so we need to delete
				 * the revision that was just created because it isn't encrypted.
				 * 
				 * @note If I continue to have problems with revisions then perhaps give this a go: https://github.com/woocommerce/woocommerce/issues/13800#issuecomment-851542026
				 *
				 * @link https://wordpress.stackexchange.com/a/102464
				 */
				$latest_revision = current(wp_get_post_revisions($post_id));

				if ( $latest_revision ) {

					// if ( substr($latest_revision->post_content, 0, 6) !== 'en.p.t' ) {

						wp_delete_post($latest_revision->ID, true);
		
					// }

				}

				/**
				 * Make sure the post has content before encrypting it, otherwise it'll encrypt nothingness.
				 */
				if ( ! empty($post->post_content) ) {

					// Unhook this function otherwise it will trigger again because of wp_update_post.
					remove_action( 'save_post', array( $this, 'en_p_t_encrypt_the_post'), 10, 2 );

					/**
					 * @link https://wordpress.stackexchange.com/a/337417
					 */
					if ( did_action('save_post') === 1 ) {

						do_action( 'en_p_t_before_save', $post->post_content, $post_id );

					}

					// Encrypt the post.
					wp_update_post( array( 'ID' => $post_id, 'post_content' => openssl_encrypt($post->post_content, $cipher, $encryption_key, 0, $iv) ) );

				}

			}

		}




		/**
		 * Decrypt revisions on the fly so revisions visually show the change/s to post. Despite this function looking
		 * relatively streamlined it took a fair few hours to figure out how to do this. I nearly gave up at least
		 * 10 times and even began writing a blog outlining why it would't be possible to decrypt data on the
		 * revisions screen. Anywho, got there in the end!
		 * 
		 * NOTE: No longer using this as have managed to get diffs working properly using 'en_p_t_display_decrypted_revision_with_diffs' below.
		 *
		 * @version 1.0.0
		 * @since 1.0.0
		 */
		function en_p_t_display_decrypted_revision( $return, $compare_from, $compare_to ) {

			$the_diff = $return[1]['diff']; // Revision data saved in 'diff', hence variable name.

			$encrypted_post_content_from = $compare_from->post_content;
			$post_id_from = $compare_from->post_parent; // Need to get revision parent to get the id of the real post
			$decrypted_content_from = $this->en_p_t_decrypt_data($post_id_from, $encrypted_post_content_from, $compare_from->ID);

			$encrypted_post_content_to = $compare_to->post_content;
			$post_id_to = $compare_to->post_parent; // Need to get revision parent to get the id of the real post
			$decrypted_content_to = $this->en_p_t_decrypt_data($post_id_to, $encrypted_post_content_to, $compare_to->ID);

			/**
			 * I was using 'if ( $decrypted_content_from && $decrypted_content_to ) {' but on the first revision there's
			 * no content in $decrypted_content_from so the first revision wasn't showing the decrypted data, so
			 * have removed  $decrypted_content_from from the if statement.
			 */
			if ( $decrypted_content_to ) {

				$counter = 0;

				foreach ( $return as $r ) {

					foreach ( $r as $key => $value ) {

						if ( $key == 'id' && $value == 'post_content' ) {

							$return[$counter]['diff'] = str_replace(array($encrypted_post_content_from, $encrypted_post_content_to), array($decrypted_content_from, $decrypted_content_to), $the_diff);

							// Don't need to do any further as have found "post_content".
							break;

						}

					}

					$counter++;

				}

			}

			return $return;
		
		}




		/**
		 * Get the revision with diffs! Replaces 'en_p_t_display_decrypted_revision' above.
		 */
		function en_p_t_display_decrypted_revision_with_diffs( $field, $field_type, $post, $context ) {

			$content = $field;

			// post_parent is the id of the actual post (as opposed to the revision id)
			$post_id = $post->post_parent;

			// Only decrypt if this is the en_p_t post type
			if ( get_post_type($post_id) == 'en_p_t' ) {

				$post_content = $post->post_content;

				$content = $this->en_p_t_decrypt_data($post_id, $post_content, $post->ID);

			}

			return $content;

		}




		/**
		 * IV changes on each save so it needs to be saved as meta to the revision.
		 *
		 * @version 1.0.0
		 * @since 1.0.0
		 *
		 * @link https://johnblackbourn.com/post-meta-revisions-wordpress/
		 */
		function en_p_t_save_meta_to_revision( $post_id, $post ) {

			$parent_id = wp_is_post_revision( $post_id );
		
			if ( $parent_id ) {
		
				$parent  = get_post( $parent_id );
				$iv_meta = get_post_meta( $parent->ID, '_en_p_t_e_iv', true );
		
				if ( false !== $iv_meta )  {

					add_metadata( 'post', $post_id, '_en_p_t_e_iv', $iv_meta );

				}
		
			}
		
		}




		/**
		 * Set post to published and remove 'Auto Draft' title. Although setting the post to private would make more sense given
		 * that they won't display on the front-end, if they are set to private then the Gutenberg paragraph block won't
		 * display them in the link pop-up, and the link pop-up is pretty cool to use! So, they are set to published
		 * but the post type has a whole bunch of args set to false so the notes cannot be viewed on the front-end.
		 *
		 * @version 1.0.0
		 * @since 1.0.0
		 */
		function en_p_t_set_post_to_private( $post ) {

			if ( $post['post_type'] == 'en_p_t' && $post['post_status'] !== 'trash' ) {

				$post['post_status'] = 'publish';

				if ( $post['post_title'] == 'Auto Draft' && empty($post['post_content']) ) {

					$post['post_title'] = '';

				}

			}

			return $post;

		}




		/**
		 * When a revision is restored also restore the escaped_iv that was saved as meta data to the revision.
		 *
		 * @version 1.0.0
		 * @since 1.0.0
		 *
		 * @link https://johnblackbourn.com/post-meta-revisions-wordpress/
		 */
		function en_p_t_restore_revision( $post_id, $revision_id ) {

			$iv_meta  = get_metadata( 'post', $revision_id, '_en_p_t_e_iv', true );
		
			if ( $iv_meta ) {

				update_post_meta( $post_id, '_en_p_t_e_iv', $iv_meta );

				do_action( 'en_p_t_after_revision_restore', $post_id, $revision_id );

			}

		}




		/**
		 * The link component of the Gutenberg paragraph block links to the front-end version of a post, but there
		 * is no front-end version of Encrypted Post Type posts so a 404 page will display instead of the post
		 * (and in any event, the post is encrypted so would just churn out an encrypted string), to get
		 * around that when a 404 page is displayed for an EPT post it's redirected to the edit screen.
		 *
		 * @version 1.0.0
		 * @since 1.0.0
		 */
		function en_p_t_redirect_404_to_edit_screen() {

			// Make sure the user is logged in.
			if ( is_404() && is_user_logged_in() ) {

				$post_type = sanitize_key(($_GET['post_type']));
				$p = sanitize_key(($_GET['p']));

				// This is an EPT post so redirect to the edit screen of the post.
				if ( isset($post_type) && $post_type == 'en_p_t' && isset($p) ) {

					wp_redirect( get_edit_post_link($p, '' ) );
					die;

				}

			}

		}




		/**
		 * Will create the RKM post and return true if using RKM.
		 *
		 * @version 1.0.0
		 * @since 1.0.0
		 */
		function en_p_t_rkm_create($post_id, $encrypted_message, $escaped_iv) {

			/**
			 * The REST plugin is active and the RKM constants are defined so let's get this show on the road.
			 */
			if ( $this->en_p_t_is_rkm() ) {

				// This will be used as the post name on the RKM site.
				$rest_post_name = $this->en_p_t_random_token(4) . $post_id;
				// Get the REST class.
				$rest_class = new en_p_t_rest;

				// Create the post on the RKM site to store the encrypted key and iv.
				if ( $rest_class->en_p_t_rest_create_key($rest_post_name, $encrypted_message, $escaped_iv) ) {

					// Save the RKM post name as meta to the post as it'll be needed when getting the key and iv via RKM to decrypt the data.
					add_post_meta( $post_id, '_en_p_t_r_id', $rest_post_name );

					return true;

				// Couldn't create RKM post so return false.
				} else {

					return false;

				}

			// The REST plugin isn't active and/or the constants don't exist so return false.
			} else {

				return false;

			}

		}



		/**
		 * Checks if the REST plugin is active and the RKM constants are defined and returns true if so; if not returns false.
		 */
		function en_p_t_is_rkm() {

			/**
			 * The REST plugin is active and the RKM constants are defined so let's get this show on the road.
			 */
			if ( class_exists('en_p_t_rest') && defined('RKM_URL') && defined('RKM_USER') && defined('RKM_PASS') ) {

				return true;

			} else {

				return false;

			}

		}




	}

	// initialize
	$en_p_t = new en_p_t;

endif;
