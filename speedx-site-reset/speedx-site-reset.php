<?php
/**
 * Plugin Name:       SpeedX Site Reset
 * Description:       Reset/deletion modes for WordPress with strong safeguards.
 * Version:           1.1.0
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
	class SpeedX_Site_Reset {
		const MENU_SLUG = 'speedx-site-reset';
		const NONCE_ACTION = 'speedx_site_reset_nonce_action';

		public function __construct() {
			add_action( 'admin_menu', array( $this, 'register_menu_page' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_action( 'admin_post_speedx_site_reset_run', array( $this, 'handle_reset_submission' ) );
		}

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

		public function enqueue_admin_assets( $hook_suffix ) {
			if ( 'toplevel_page_' . self::MENU_SLUG !== $hook_suffix ) {
				return;
			}

			wp_enqueue_style( 'speedx-site-reset-admin', plugin_dir_url( __FILE__ ) . 'assets/admin.css', array(), '1.1.0' );
			wp_enqueue_script( 'speedx-site-reset-admin', plugin_dir_url( __FILE__ ) . 'assets/admin.js', array(), '1.1.0', true );

			wp_localize_script(
				'speedx-site-reset-admin',
				'SpeedXSiteReset',
				array(
					'confirmMessage' => __( 'Final warning: this destructive action cannot be undone. Continue?', 'speedx-site-reset' ),
				)
			);
		}

		public function render_admin_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to access this page.', 'speedx-site-reset' ) );
			}

			$reset_state = isset( $_GET['speedx_reset'] ) ? sanitize_key( wp_unslash( $_GET['speedx_reset'] ) ) : '';
			?>
			<div class="wrap speedx-reset-wrap">
				<h1><?php echo esc_html__( 'SpeedX Site Reset', 'speedx-site-reset' ); ?></h1>

				<?php if ( 'success' === $reset_state ) : ?>
					<div class="notice notice-success is-dismissible"><p><?php echo esc_html__( 'Selected action completed successfully.', 'speedx-site-reset' ); ?></p></div>
				<?php elseif ( 'failed' === $reset_state ) : ?>
					<div class="notice notice-error is-dismissible"><p><?php echo esc_html__( 'Action failed or was blocked by a security check.', 'speedx-site-reset' ); ?></p></div>
				<?php endif; ?>

				<div class="speedx-warning-box">
					<p class="speedx-warning-title"><?php echo esc_html__( 'Danger Zone', 'speedx-site-reset' ); ?></p>
					<p><?php echo esc_html__( 'All options below are destructive. Only proceed if you have backups.', 'speedx-site-reset' ); ?></p>
				</div>

				<form id="speedx-site-reset-form" class="speedx-reset-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="speedx_site_reset_run" />
					<?php wp_nonce_field( self::NONCE_ACTION, 'speedx_site_reset_nonce' ); ?>

					<div class="speedx-options-box">
						<p class="speedx-options-heading"><?php echo esc_html__( 'Select reset mode', 'speedx-site-reset' ); ?></p>

						<label class="speedx-option-card"><input type="radio" name="speedx_reset_mode" value="deactivate_plugins" /> <strong><?php echo esc_html__( 'Deactivate All Plugins', 'speedx-site-reset' ); ?></strong><span><?php echo esc_html__( 'Only deactivates all plugins except SpeedX Site Reset while action runs.', 'speedx-site-reset' ); ?></span></label>
						<label class="speedx-option-card"><input type="radio" name="speedx_reset_mode" value="delete_plugins" /> <strong><?php echo esc_html__( 'Delete All Plugins', 'speedx-site-reset' ); ?></strong><span><?php echo esc_html__( 'Deletes plugin files for all plugins except this plugin.', 'speedx-site-reset' ); ?></span></label>
						<label class="speedx-option-card"><input type="radio" name="speedx_reset_mode" value="deactivate_themes" /> <strong><?php echo esc_html__( 'Deactivate All Themes', 'speedx-site-reset' ); ?></strong><span><?php echo esc_html__( 'Switches to a safe default WordPress theme if available.', 'speedx-site-reset' ); ?></span></label>
						<label class="speedx-option-card"><input type="radio" name="speedx_reset_mode" value="delete_themes" /> <strong><?php echo esc_html__( 'Delete All Themes', 'speedx-site-reset' ); ?></strong><span><?php echo esc_html__( 'Deletes installed themes except the active and safe fallback theme.', 'speedx-site-reset' ); ?></span></label>
						<label class="speedx-option-card"><input type="radio" name="speedx_reset_mode" value="nuclear_reset" /> <strong><?php echo esc_html__( 'Nuclear Reset', 'speedx-site-reset' ); ?></strong><span><?php echo esc_html__( 'Deletes site content/media/taxonomies/plugins/themes/settings and keeps current admin + core files.', 'speedx-site-reset' ); ?></span></label>
					</div>

					<label for="speedx-reset-confirmation"><?php echo esc_html__( 'Type "RESET" to enable action button:', 'speedx-site-reset' ); ?></label>
					<input type="text" id="speedx-reset-confirmation" name="speedx_reset_confirmation" autocomplete="off" spellcheck="false" placeholder="RESET" />

					<button type="submit" id="speedx-reset-submit" class="button speedx-reset-button" disabled hidden>
						<?php echo esc_html__( 'Run Selected Action', 'speedx-site-reset' ); ?>
					</button>
				</form>
			</div>
			<?php
		}

		public function handle_reset_submission() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to perform this action.', 'speedx-site-reset' ) );
			}

			$nonce = isset( $_POST['speedx_site_reset_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['speedx_site_reset_nonce'] ) ) : '';
			if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
				$this->redirect_with_status( 'failed' );
			}

			$confirmation = isset( $_POST['speedx_reset_confirmation'] ) ? sanitize_text_field( wp_unslash( $_POST['speedx_reset_confirmation'] ) ) : '';
			$mode = isset( $_POST['speedx_reset_mode'] ) ? sanitize_key( wp_unslash( $_POST['speedx_reset_mode'] ) ) : '';

			$allowed_modes = array( 'deactivate_plugins', 'delete_plugins', 'deactivate_themes', 'delete_themes', 'nuclear_reset' );
			if ( 'RESET' !== $confirmation || ! in_array( $mode, $allowed_modes, true ) ) {
				$this->redirect_with_status( 'failed' );
			}

			$this->run_selected_mode( $mode );
			$this->redirect_with_status( 'success' );
		}

		private function run_selected_mode( $mode ) {
			switch ( $mode ) {
				case 'deactivate_plugins':
					$this->deactivate_other_plugins();
					break;
				case 'delete_plugins':
					$this->delete_all_plugins_except_self();
					break;
				case 'deactivate_themes':
					$this->switch_to_safe_theme();
					break;
				case 'delete_themes':
					$this->delete_all_themes_except_safe();
					break;
				case 'nuclear_reset':
					$this->run_nuclear_reset();
					break;
			}
		}

		private function run_nuclear_reset() {
			$current_user_id = get_current_user_id();
			$this->delete_all_content_posts();
			$this->delete_all_comments();
			$this->delete_all_terms();
			$this->delete_all_media_and_uploads();
			$this->delete_all_menus();
			$this->reset_widgets_and_sidebars();
			$this->reset_customizer_settings();
			$this->reset_common_options();
			$this->reset_transients();
			$this->delete_woocommerce_data();
			$this->delete_other_users( $current_user_id );
			$this->delete_all_plugins_except_self();
			$this->switch_to_safe_theme();
			$this->delete_all_themes_except_safe();
		}

		private function deactivate_other_plugins() {
			if ( ! function_exists( 'deactivate_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$active_plugins = (array) get_option( 'active_plugins', array() );
			$this_plugin = plugin_basename( __FILE__ );
			$plugins_to_deactivate = array_values( array_filter( $active_plugins, static function( $plugin_file ) use ( $this_plugin ) { return $plugin_file !== $this_plugin; } ) );
			if ( ! empty( $plugins_to_deactivate ) ) {
				deactivate_plugins( $plugins_to_deactivate, true, false );
			}
		}

		private function delete_all_plugins_except_self() {
			if ( ! function_exists( 'delete_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$this->deactivate_other_plugins();
			$plugins = get_plugins();
			$this_plugin = plugin_basename( __FILE__ );
			$to_delete = array();
			foreach ( array_keys( $plugins ) as $plugin_file ) {
				if ( $plugin_file !== $this_plugin ) {
					$to_delete[] = $plugin_file;
				}
			}
			if ( ! empty( $to_delete ) ) {
				delete_plugins( $to_delete );
			}
		}

		private function switch_to_safe_theme() {
			$safe = $this->get_safe_theme_slug();
			if ( $safe ) {
				switch_theme( $safe );
			}
		}

		private function get_safe_theme_slug() {
			$candidates = array( 'twentytwentysix', 'twentytwentyfive', 'twentytwentyfour', 'twentytwentythree', 'twentytwentytwo' );
			foreach ( $candidates as $slug ) {
				$theme = wp_get_theme( $slug );
				if ( $theme->exists() ) {
					return $slug;
				}
			}
			$current = wp_get_theme();
			return $current->exists() ? $current->get_stylesheet() : '';
		}

		private function delete_all_themes_except_safe() {
			if ( ! function_exists( 'delete_theme' ) ) {
				require_once ABSPATH . 'wp-admin/includes/theme.php';
			}
			$safe = $this->get_safe_theme_slug();
			$this->switch_to_safe_theme();
			$themes = wp_get_themes();
			$current = wp_get_theme()->get_stylesheet();
			foreach ( $themes as $slug => $theme_obj ) {
				if ( $slug === $safe || $slug === $current ) {
					continue;
				}
				delete_theme( $slug );
			}
		}

		private function delete_all_content_posts() { /* same behavior */
			$post_types = get_post_types( array(), 'names' );
			unset( $post_types['attachment'] );
			$post_ids = get_posts(array('post_type'=>array_values($post_types),'post_status'=>'any','numberposts'=>-1,'fields'=>'ids','no_found_rows'=>true,'suppress_filters'=>true));
			foreach ( (array) $post_ids as $post_id ) { wp_delete_post( (int) $post_id, true ); }
		}
		private function delete_all_comments() { $comments = get_comments(array('status'=>'all','fields'=>'ids')); foreach ( (array) $comments as $id ) { wp_delete_comment((int)$id,true); } }
		private function delete_all_terms() {
			foreach ( (array) get_taxonomies(array(),'names') as $taxonomy ) {
				if ( 'nav_menu' === $taxonomy ) { continue; }
				$terms = get_terms(array('taxonomy'=>$taxonomy,'hide_empty'=>false,'fields'=>'ids'));
				if ( is_wp_error( $terms ) ) { continue; }
				foreach ( (array) $terms as $term_id ) { wp_delete_term( (int) $term_id, $taxonomy ); }
			}
		}
		private function delete_all_media_and_uploads() {
			$attachments = get_posts(array('post_type'=>'attachment','post_status'=>'any','numberposts'=>-1,'fields'=>'ids','no_found_rows'=>true,'suppress_filters'=>true));
			foreach ( (array) $attachments as $id ) { wp_delete_attachment((int)$id,true); }
			$uploads = wp_upload_dir();
			if ( ! empty( $uploads['basedir'] ) && is_dir( $uploads['basedir'] ) ) { $this->delete_directory_contents( $uploads['basedir'] ); }
		}
		private function delete_directory_contents( $directory ) {
			$items = @scandir( $directory ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( false === $items ) { return; }
			foreach ( $items as $item ) {
				if ( '.' === $item || '..' === $item ) { continue; }
				$path = trailingslashit( $directory ) . $item;
				if ( is_dir( $path ) ) { $this->delete_directory_contents( $path ); @rmdir( $path ); } // phpcs:ignore
				elseif ( is_file( $path ) ) { @unlink( $path ); } // phpcs:ignore
			}
		}
		private function delete_all_menus() { foreach ( (array) wp_get_nav_menus() as $menu ) { if ( isset( $menu->term_id ) ) { wp_delete_nav_menu((int)$menu->term_id); } } }
		private function reset_widgets_and_sidebars() { global $wpdb; update_option( 'sidebars_widgets', array() ); foreach ( (array) $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'widget_%'" ) as $name ) { delete_option( $name ); } } // phpcs:ignore
		private function reset_customizer_settings() { global $wpdb; remove_theme_mods(); foreach ( (array) $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'theme_mods_%'" ) as $name ) { delete_option( $name ); } } // phpcs:ignore
		private function delete_other_users( $current_user_id ) { if ( ! function_exists( 'wp_delete_user' ) ) { require_once ABSPATH . 'wp-admin/includes/user.php'; } foreach ( (array) get_users(array('fields'=>array('ID'))) as $u ) { $uid=(int)$u->ID; if ( $uid && $uid !== (int) $current_user_id ) { wp_delete_user( $uid, (int) $current_user_id ); } } }
		private function reset_common_options() { update_option('blogname','My WordPress'); update_option('blogdescription','Just another WordPress site'); update_option('show_on_front','posts'); update_option('page_on_front',0); update_option('page_for_posts',0); update_option('permalink_structure',''); delete_option('rewrite_rules'); set_theme_mod('nav_menu_locations',array()); flush_rewrite_rules(); }
		private function reset_transients() { global $wpdb; $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%' OR option_name LIKE '_site_transient_%'" ); } // phpcs:ignore
		private function delete_woocommerce_data() {
			if ( ! class_exists( 'WooCommerce' ) ) { return; }
			foreach ( array( 'product', 'product_variation', 'shop_order', 'shop_order_refund', 'shop_coupon' ) as $ptype ) {
				$ids = get_posts(array('post_type'=>$ptype,'post_status'=>'any','numberposts'=>-1,'fields'=>'ids','no_found_rows'=>true,'suppress_filters'=>true));
				foreach ( (array) $ids as $id ) { wp_delete_post((int)$id,true); }
			}
			foreach ( array( 'woocommerce_%', 'wc_%' ) as $pattern ) {
				global $wpdb;
				$opts = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $pattern ) ); // phpcs:ignore
				foreach ( (array) $opts as $opt ) { delete_option( $opt ); }
			}
		}

		private function redirect_with_status( $status ) {
			wp_safe_redirect( add_query_arg( array( 'page' => self::MENU_SLUG, 'speedx_reset' => sanitize_key( $status ) ), admin_url( 'admin.php' ) ) );
			exit;
		}

		public static function uninstall() {}
	}

	new SpeedX_Site_Reset();
	register_uninstall_hook( __FILE__, array( 'SpeedX_Site_Reset', 'uninstall' ) );
}
