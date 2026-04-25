<?php
/**
 * Plugin Name:       SpeedX Site Reset
 * Description:       Reset a WordPress site to a near-fresh state from the admin area.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            SpeedX
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       speedx-site-reset
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SpeedX_Site_Reset' ) ) {
	/**
	 * Main plugin class.
	 */
	class SpeedX_Site_Reset {
		/**
		 * Slug used by menu page.
		 *
		 * @var string
		 */
		const MENU_SLUG = 'speedx-site-reset';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'register_menu_page' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_action( 'admin_post_speedx_site_reset_run', array( $this, 'handle_reset_submission' ) );
		}

		/**
		 * Register admin menu page.
		 *
		 * @return void
		 */
		public function register_menu_page() {
			add_menu_page(
				esc_html__( 'SpeedX Site Reset', 'speedx-site-reset' ),
				esc_html__( 'SpeedX Site Reset', 'speedx-site-reset' ),
				'manage_options',
				self::MENU_SLUG,
				array( $this, 'render_admin_page' ),
				'dashicons-warning',
				80
			);
		}

		/**
		 * Enqueue styles and scripts for plugin admin page.
		 *
		 * @param string $hook_suffix Current admin page hook.
		 * @return void
		 */
		public function enqueue_admin_assets( $hook_suffix ) {
			if ( 'toplevel_page_' . self::MENU_SLUG !== $hook_suffix ) {
				return;
			}

			wp_enqueue_style(
				'speedx-site-reset-admin',
				plugin_dir_url( __FILE__ ) . 'assets/admin.css',
				array(),
				'1.0.0'
			);

			wp_enqueue_script(
				'speedx-site-reset-admin',
				plugin_dir_url( __FILE__ ) . 'assets/admin.js',
				array(),
				'1.0.0',
				true
			);

			wp_localize_script(
				'speedx-site-reset-admin',
				'SpeedXSiteReset',
				array(
					'confirmMessage' => __( 'Final warning: this will permanently reset your site. Do you want to continue?', 'speedx-site-reset' ),
				)
			);
		}

		/**
		 * Render plugin admin page.
		 *
		 * @return void
		 */
		public function render_admin_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'speedx-site-reset' ) );
			}

			$reset_state = isset( $_GET['speedx_reset'] ) ? sanitize_key( wp_unslash( $_GET['speedx_reset'] ) ) : '';
			?>
			<div class="wrap speedx-reset-wrap">
				<h1><?php echo esc_html__( 'SpeedX Site Reset', 'speedx-site-reset' ); ?></h1>

				<?php if ( 'success' === $reset_state ) : ?>
					<div class="notice notice-success is-dismissible">
						<p><?php echo esc_html__( 'Site reset completed successfully.', 'speedx-site-reset' ); ?></p>
					</div>
				<?php elseif ( 'failed' === $reset_state ) : ?>
					<div class="notice notice-error is-dismissible">
						<p><?php echo esc_html__( 'Site reset failed or was blocked by security checks.', 'speedx-site-reset' ); ?></p>
					</div>
				<?php endif; ?>

				<div class="speedx-warning-box">
					<p class="speedx-warning-title"><?php echo esc_html__( 'Danger Zone', 'speedx-site-reset' ); ?></p>
					<p>
						<?php echo esc_html__( 'This action will permanently delete website content, media, settings, users except the current admin, menus, widgets, theme customizer settings, and deactivate plugins. This cannot be undone.', 'speedx-site-reset' ); ?>
					</p>
				</div>

				<form id="speedx-site-reset-form" class="speedx-reset-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="speedx_site_reset_run" />
					<?php wp_nonce_field( 'speedx_site_reset_nonce_action', 'speedx_site_reset_nonce' ); ?>

					<label for="speedx-reset-confirmation"><?php echo esc_html__( 'Type "reset" to enable reset button:', 'speedx-site-reset' ); ?></label>
					<input
						type="text"
						id="speedx-reset-confirmation"
						name="speedx_reset_confirmation"
						autocomplete="off"
						spellcheck="false"
						placeholder="reset"
					/>

					<button type="submit" id="speedx-reset-submit" class="button button-primary speedx-reset-button" disabled hidden>
						<?php echo esc_html__( 'Reset Website Now', 'speedx-site-reset' ); ?>
					</button>
				</form>
			</div>
			<?php
		}

		/**
		 * Handle form submission and site reset.
		 *
		 * @return void
		 */
		public function handle_reset_submission() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to perform this action.', 'speedx-site-reset' ) );
			}

			$nonce = isset( $_POST['speedx_site_reset_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['speedx_site_reset_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, 'speedx_site_reset_nonce_action' ) ) {
				$this->redirect_with_status( 'failed' );
			}

			$confirmation = isset( $_POST['speedx_reset_confirmation'] ) ? sanitize_text_field( wp_unslash( $_POST['speedx_reset_confirmation'] ) ) : '';
			if ( 'reset' !== $confirmation ) {
				$this->redirect_with_status( 'failed' );
			}

			$this->run_site_reset();
			$this->redirect_with_status( 'success' );
		}

		/**
		 * Perform site reset actions.
		 *
		 * @return void
		 */
		private function run_site_reset() {
			$current_user_id = get_current_user_id();

			// Deactivate all plugins except this reset plugin during execution.
			$this->deactivate_other_plugins();

			// Remove posts/pages/custom post types (excluding attachments).
			$this->delete_all_content_posts();

			// Remove comments.
			$this->delete_all_comments();

			// Remove taxonomies/terms.
			$this->delete_all_terms();

			// Remove media items and uploaded files.
			$this->delete_all_media_and_uploads();

			// Remove navigation menus.
			$this->delete_all_menus();

			// Reset widgets and sidebars.
			$this->reset_widgets_and_sidebars();

			// Reset customizer settings.
			$this->reset_customizer_settings();

			// Remove users except current admin.
			$this->delete_other_users( $current_user_id );

			// Reset common options and rewrite settings.
			$this->reset_common_options();
		}

		/**
		 * Deactivate all plugins except this plugin.
		 *
		 * @return void
		 */
		private function deactivate_other_plugins() {
			if ( ! function_exists( 'deactivate_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$active_plugins = (array) get_option( 'active_plugins', array() );
			if ( empty( $active_plugins ) ) {
				return;
			}

			$this_plugin = plugin_basename( __FILE__ );
			$plugins_to_deactivate = array_filter(
				$active_plugins,
				static function ( $plugin_file ) use ( $this_plugin ) {
					return $plugin_file !== $this_plugin;
				}
			);

			if ( ! empty( $plugins_to_deactivate ) ) {
				deactivate_plugins( $plugins_to_deactivate, true, false );
			}
		}

		/**
		 * Delete all posts except attachments.
		 *
		 * @return void
		 */
		private function delete_all_content_posts() {
			$post_types = get_post_types( array(), 'names' );
			unset( $post_types['attachment'] );

			if ( empty( $post_types ) ) {
				return;
			}

			$post_ids = get_posts(
				array(
					'post_type'        => array_values( $post_types ),
					'post_status'      => 'any',
					'numberposts'      => -1,
					'fields'           => 'ids',
					'no_found_rows'    => true,
					'suppress_filters' => true,
				)
			);

			if ( empty( $post_ids ) ) {
				return;
			}

			foreach ( $post_ids as $post_id ) {
				wp_delete_post( (int) $post_id, true );
			}
		}

		/**
		 * Delete all comments.
		 *
		 * @return void
		 */
		private function delete_all_comments() {
			$comments = get_comments(
				array(
					'status' => 'all',
					'fields' => 'ids',
				)
			);

			if ( empty( $comments ) ) {
				return;
			}

			foreach ( $comments as $comment_id ) {
				wp_delete_comment( (int) $comment_id, true );
			}
		}

		/**
		 * Delete all terms in all taxonomies except nav menu taxonomy.
		 *
		 * @return void
		 */
		private function delete_all_terms() {
			$taxonomies = get_taxonomies( array(), 'names' );
			if ( empty( $taxonomies ) ) {
				return;
			}

			foreach ( $taxonomies as $taxonomy ) {
				if ( 'nav_menu' === $taxonomy ) {
					continue;
				}

				$terms = get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'hide_empty' => false,
						'fields'     => 'ids',
					)
				);

				if ( is_wp_error( $terms ) || empty( $terms ) ) {
					continue;
				}

				foreach ( $terms as $term_id ) {
					wp_delete_term( (int) $term_id, $taxonomy );
				}
			}
		}

		/**
		 * Delete all media items and remove upload files.
		 *
		 * @return void
		 */
		private function delete_all_media_and_uploads() {
			$attachment_ids = get_posts(
				array(
					'post_type'        => 'attachment',
					'post_status'      => 'any',
					'numberposts'      => -1,
					'fields'           => 'ids',
					'no_found_rows'    => true,
					'suppress_filters' => true,
				)
			);

			if ( ! empty( $attachment_ids ) ) {
				foreach ( $attachment_ids as $attachment_id ) {
					wp_delete_attachment( (int) $attachment_id, true );
				}
			}

			$uploads = wp_upload_dir();
			if ( empty( $uploads['basedir'] ) || ! is_dir( $uploads['basedir'] ) ) {
				return;
			}

			$this->delete_directory_contents( $uploads['basedir'] );
		}

		/**
		 * Delete all files and directories inside a directory safely.
		 *
		 * @param string $directory Absolute directory path.
		 * @return void
		 */
		private function delete_directory_contents( $directory ) {
			$items = @scandir( $directory ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( false === $items ) {
				return;
			}

			foreach ( $items as $item ) {
				if ( '.' === $item || '..' === $item ) {
					continue;
				}

				$path = trailingslashit( $directory ) . $item;
				if ( is_dir( $path ) ) {
					$this->delete_directory_contents( $path );
					@rmdir( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				} elseif ( is_file( $path ) ) {
					@unlink( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				}
			}
		}

		/**
		 * Delete all navigation menus.
		 *
		 * @return void
		 */
		private function delete_all_menus() {
			$menus = wp_get_nav_menus();
			if ( empty( $menus ) || is_wp_error( $menus ) ) {
				return;
			}

			foreach ( $menus as $menu ) {
				if ( isset( $menu->term_id ) ) {
					wp_delete_nav_menu( (int) $menu->term_id );
				}
			}
		}

		/**
		 * Reset widgets and sidebars.
		 *
		 * @return void
		 */
		private function reset_widgets_and_sidebars() {
			global $wpdb;

			update_option( 'sidebars_widgets', array() );

			$widget_options = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'widget_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( empty( $widget_options ) ) {
				return;
			}

			foreach ( $widget_options as $option_name ) {
				delete_option( $option_name );
			}
		}

		/**
		 * Reset theme customizer settings.
		 *
		 * @return void
		 */
		private function reset_customizer_settings() {
			global $wpdb;

			remove_theme_mods();

			$theme_mod_options = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'theme_mods_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( empty( $theme_mod_options ) ) {
				return;
			}

			foreach ( $theme_mod_options as $option_name ) {
				delete_option( $option_name );
			}
		}

		/**
		 * Delete all users except current user.
		 *
		 * @param int $current_user_id Current logged-in user ID.
		 * @return void
		 */
		private function delete_other_users( $current_user_id ) {
			if ( ! function_exists( 'wp_delete_user' ) ) {
				require_once ABSPATH . 'wp-admin/includes/user.php';
			}

			$users = get_users(
				array(
					'fields' => array( 'ID' ),
				)
			);

			if ( empty( $users ) ) {
				return;
			}

			foreach ( $users as $user ) {
				$user_id = isset( $user->ID ) ? (int) $user->ID : 0;
				if ( $user_id > 0 && $user_id !== (int) $current_user_id ) {
					wp_delete_user( $user_id, (int) $current_user_id );
				}
			}
		}

		/**
		 * Reset common WordPress options.
		 *
		 * @return void
		 */
		private function reset_common_options() {
			update_option( 'blogname', 'My WordPress' );
			update_option( 'blogdescription', 'Just another WordPress site' );
			update_option( 'show_on_front', 'posts' );
			update_option( 'page_on_front', 0 );
			update_option( 'page_for_posts', 0 );

			update_option( 'posts_per_page', 10 );
			update_option( 'posts_per_rss', 10 );
			update_option( 'rss_use_excerpt', 0 );

			update_option( 'default_ping_status', 'open' );
			update_option( 'default_comment_status', 'open' );
			update_option( 'default_comment_pingback_flag', 1 );
			update_option( 'comment_moderation', 0 );
			update_option( 'comment_registration', 0 );
			update_option( 'thread_comments', 1 );

			update_option( 'permalink_structure', '' );
			delete_option( 'rewrite_rules' );

			// Reset menu locations assignment.
			set_theme_mod( 'nav_menu_locations', array() );

			flush_rewrite_rules();
		}

		/**
		 * Redirect back to admin page.
		 *
		 * @param string $status Query status.
		 * @return void
		 */
		private function redirect_with_status( $status ) {
			$url = add_query_arg(
				array(
					'page'        => self::MENU_SLUG,
					'speedx_reset' => sanitize_key( $status ),
				),
				admin_url( 'admin.php' )
			);

			wp_safe_redirect( $url );
			exit;
		}

		/**
		 * Uninstall safety callback.
		 *
		 * Intentionally does not reset the site on uninstall.
		 *
		 * @return void
		 */
		public static function uninstall() {
			// Safety-by-design: do nothing destructive during uninstall.
		}
	}

	new SpeedX_Site_Reset();
	register_uninstall_hook( __FILE__, array( 'SpeedX_Site_Reset', 'uninstall' ) );
}
