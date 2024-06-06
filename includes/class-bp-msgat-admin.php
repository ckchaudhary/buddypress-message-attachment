<?php
/**
 * Admin Class.
 *
 * @package bpmsgat
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Msgat_Admin' ) ) :

	/**
	 * Admin class
	 */
	class BP_Msgat_Admin {

		/**
		 * Plugin options
		 *
		 * @var array
		 */
		public $options = array();

		/**
		 * Is the plugin network activated?
		 *
		 * @var boolean
		 */
		private $network_activated = false;

		/**
		 * Settings screen slug.
		 *
		 * @var string
		 */
		private $plugin_slug = 'bp-msgat';

		/**
		 * Menu hook.
		 *
		 * @var string
		 */
		private $menu_hook = 'admin_menu';

		/**
		 * Settings page.
		 *
		 * @var string
		 */
		private $settings_page = 'options-general.php';

		/**
		 * User capability to access settings screen.
		 *
		 * @var string
		 */
		private $capability = 'manage_options';

		/**
		 * Where does the settings form submit to?
		 *
		 * @var string
		 */
		private $form_action = 'options.php';

		/**
		 * Url for plugin settings screen.
		 *
		 * @var string
		 */
		private $plugin_settings_url = '';

		/**
		 * Empty constructor function to ensure a single instance
		 */
		public function __construct() {
			// ... leave empty, see Singleton below
		}

		/**
		 * Get the single instance of this class.
		 *
		 * @return \BP_Msgat_Admin
		 */
		public static function instance() {
			static $instance = null;

			if ( null === $instance ) {
				$instance = new BP_Msgat_Admin();
				$instance->setup();
			}

			return $instance;
		}

		/**
		 * Get a settings value.
		 *
		 * @param string $key settings name.
		 * @return mixed
		 */
		public function option( $key ) {
			$value = bp_message_attachment()->option( $key );
			return $value;
		}

		/**
		 * Setup everything.
		 *
		 * @return void
		 */
		public function setup() {
			if ( ( ! is_admin() && ! is_network_admin() ) || ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$this->plugin_settings_url = admin_url( 'options-general.php?page=' . $this->plugin_slug );

			$this->network_activated = $this->is_network_activated();

			// if the plugin is activated network wide in multisite, we need to override few variables.
			if ( $this->network_activated ) {
				// Main settings page - menu hook.
				$this->menu_hook = 'network_admin_menu';

				// Main settings page - parent page.
				$this->settings_page = 'settings.php';

				// Main settings page - Capability.
				$this->capability = 'manage_network_options';

				// Settins page - form's action attribute.
				$this->form_action = 'edit.php?action=' . $this->plugin_slug;

				// Plugin settings page url.
				$this->plugin_settings_url = network_admin_url( 'settings.php?page=' . $this->plugin_slug );
			}

			// If the plugin is activated network wide in multisite, we need to process settings form submit ourselves.
			if ( $this->network_activated ) {
				add_action( 'network_admin_edit_' . $this->plugin_slug, array( $this, 'save_network_settings_page' ) );
			}

			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( $this->menu_hook, array( $this, 'admin_menu' ) );

			add_filter( 'plugin_action_links', array( $this, 'add_action_links' ), 10, 2 );
			add_filter( 'network_admin_plugin_action_links', array( $this, 'add_action_links' ), 10, 2 );
		}

		/**
		 * Check if the plugin is activated network wide(in multisite).
		 *
		 * @return boolean
		 */
		private function is_network_activated() {
			$network_activated = false;
			if ( is_multisite() ) {
				if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
					require_once ABSPATH . '/wp-admin/includes/plugin.php';
				}

				if ( is_plugin_active_for_network( 'buddypress-message-attachment/loader.php' ) ) {
					$network_activated = true;
				}
			}
			return $network_activated;
		}

		/**
		 * Add admin menu item.
		 *
		 * @return void
		 */
		public function admin_menu() {
			add_submenu_page(
				$this->settings_page,
				__( 'BP Message Attachments', 'bp-msgat' ),
				__( 'Message Attachments', 'bp-msgat' ),
				$this->capability,
				$this->plugin_slug,
				array( $this, 'options_page' ),
			);
		}

		/**
		 * Load the main settings screen.
		 *
		 * @return void
		 */
		public function options_page() {
			?>
			<div class="wrap">
				<h2><?php esc_html_e( 'BuddyPress Message Attachments', 'bp-msgat' ); ?></h2>
				<form method="post" action="<?php echo esc_attr( $this->form_action ); ?>">

					<?php
					// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified elsewhere.
					if ( $this->network_activated && isset( $_GET['updated'] ) ) {
						echo '<div class="updated"><p>' . esc_attr__( 'Settings updated.', 'bp-msgat' ) . '</p></div>';
					}
					?>

					<?php settings_fields( 'bp_msgat_plugin_options' ); ?>
					<?php do_settings_sections( __FILE__ ); ?>

					<p class="submit">
						<input name="bp_msgat_submit" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
					</p>
				</form>
			</div>
			<?php
		}

		/**
		 * Setup admin stuff
		 *
		 * @return void
		 */
		public function admin_init() {
			register_setting( 'bp_msgat_plugin_options', 'bp_msgat_plugin_options', array( $this, 'plugin_options_validate' ) );

			add_settings_section( 'general_section', __( 'Attachment Settings', 'bp-msgat' ), array( $this, 'section_general' ), __FILE__ );
			add_settings_field( 'file-types', __( 'Allowed File Types', 'bp-msgat' ), array( $this, 'setting_file_types' ), __FILE__, 'general_section' );
			add_settings_field( 'max-size', __( 'Maximum Size', 'bp-msgat' ), array( $this, 'setting_max_size' ), __FILE__, 'general_section' );

			add_settings_section( 'misc_section', __( 'Miscellaneous', 'bp-msgat' ), array( $this, 'section_misc' ), __FILE__ );
			add_settings_field( 'load-css', __( 'Load CSS', 'bp-msgat' ), array( $this, 'setting_load_css' ), __FILE__, 'misc_section' );

			add_filter( 'wp_get_attachment_image_attributes', array( $this, 'attachment_image_attributes_add_info_icon' ), 15, 2 );
			add_action( 'admin_footer', array( $this, 'admin_footer' ) );
		}

		/**
		 * Undocumented function
		 *
		 * @return void
		 */
		public function section_general() {
			// nothing.
		}

		/**
		 * Undocumented function
		 *
		 * @return void
		 */
		public function section_misc() {
			// nothing.
		}

		/**
		 * Get the maximum file size that can be uploaded.
		 *
		 * @return array
		 */
		private function file_upload_max_size() {
			// Start with post_max_size.
			$post_max_size = $this->return_bytes( ini_get( 'post_max_size' ) );
			$max_size_calculated = $post_max_size;

			// If upload_max_size is less, then reduce. Except if upload_max_size is
			// zero, which indicates no limit.
			$upload_max = $this->return_bytes( ini_get( 'upload_max_filesize' ) );
			if ( $upload_max > 0 && $upload_max < $post_max_size ) {
				$max_size_calculated = $upload_max;
			}

			return array(
				'post_max_size' => $post_max_size,
				'upload_max_filesize' => $upload_max,
				'max_size_calculated' => $max_size_calculated,
			);
		}

		/**
		 * Helper function to convert sizes.
		 *
		 * @param string $val input file size.
		 * @return string
		 */
		private function return_bytes( $val ) {
			preg_match( '/(?<value>\d+)(?<option>.?)/i', trim( $val ), $matches );
			$inc = array(
				'g' => 1073741824, // (1024 * 1024 * 1024)
				'm' => 1048576, // (1024 * 1024)
				'k' => 1024,
			);

			$value = (int) $matches['value'];
			$key = strtolower( trim( $matches['option'] ) );
			if ( isset( $inc[ $key ] ) ) {
				$value *= $inc[ $key ];
			}

			return $value;
		}

		/**
		 * Validate plugin settings before saving.
		 *
		 * @param array $input all settings.
		 * @return mixed
		 */
		public function plugin_options_validate( $input ) {
			$input['max-size'] = (float) $input['max-size'] ? (float) $input['max-size'] : 2;

			/* check for maximum post size and upload size restriction */
			$info = $this->file_upload_max_size();
			if ( $info['max_size_calculated'] < ( $input['max-size'] * 1024 * 1024 ) ) {
				$input['max-size'] = $info['max_size_calculated'] / ( 1024 * 1024 );
				$input['max-size'] = number_format( $input['max-size'], 2 );
			}

			if ( ! isset( $input['load-css'] ) || ! $input['load-css'] ) {
				$input['load-css'] = 'no';
			}
			return $input;// no validations for now.
		}

		/**
		 * Which file types are allowed?
		 *
		 * @return void
		 */
		public function setting_file_types() {
			$selected_extensions = $this->option( 'file-types' );

			$all_file_types = bp_message_attachment()->all_file_types();

			foreach ( $all_file_types as $group_key => $group ) {
				echo '<p><strong>' . esc_html( $group['label'] ) . '</strong></p>';

				$extensions = array_unique( $group['extensions'] );
				// Sort alphabatically.
				asort( $extensions );

				foreach ( $extensions as $extension ) {
					$checked = in_array( $extension, $selected_extensions ) ? ' checked' : '';
					echo '<label><input type="checkbox" name="bp_msgat_plugin_options[file-types][]" value="' . esc_attr( $extension ) . '" ' . esc_attr( $checked ) . '>' . esc_html( $extension ) . '</label>&nbsp;&nbsp;&nbsp;&nbsp;';
				}
				echo '<br><br>';
			}
		}

		/**
		 * How much maximum file size is allowed to be uploaded?
		 *
		 * @return void
		 */
		public function setting_max_size() {
			$max_size = $this->option( 'max-size' );
			echo "<input name='bp_msgat_plugin_options[max-size]' type='text' min='1' value='" . esc_attr( $max_size ) . "' />MB";
			echo '<p class="description">' . esc_html__( 'Maximum size(in MB) allowed per file.', 'bp-msgat' ) . '</p>';

			echo "<p class='notice notice-info'>";

			$info = $this->file_upload_max_size();
			$max_size_possible = $info['max_size_calculated'] / ( 1024 * 1024 );
			$max_size_possible = number_format( $max_size_possible, 2 );

			// translators: integer depicting maximum file size.
			printf( esc_html__( 'Based on your php configuration, maximum file size can not exceed %s MB.', 'bp-msgat' ), $max_size_possible );// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</p>';
		}

		/**
		 * Whether to load plugin's css file or not?
		 *
		 * @return void
		 */
		public function setting_load_css() {
			$load_css = $this->option( 'load-css' );
			$checked = 'yes' === $load_css ? ' checked' : '';
			echo "<input name='bp_msgat_plugin_options[load-css]' type='checkbox' value='yes' {$checked} />" . esc_html__( 'Yes', 'bp-msgat' );// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<p class="description">' . esc_html__( 'Whether to load plugin\'s css file or not. If you have overriden plugin\'s css rules in your theme, you can uncheck this.', 'bp-msgat' ) . '</p>';
		}

		/**
		 * Save network settings
		 *
		 * @return void
		 */
		public function save_network_settings_page() {
			if ( ! check_admin_referer( 'bp_msgat_plugin_options-options' ) ) {
				return;
			}

			if ( ! current_user_can( $this->capability ) ) {
				die( 'Access denied!' );
			}

			if ( isset( $_POST['bp_msgat_submit'] ) && isset( $_POST['bp_msgat_plugin_options'] ) ) {
				$submitted = stripslashes_deep( $_POST['bp_msgat_plugin_options'] );//phpcs:ignore
				$submitted = $this->plugin_options_validate( $submitted );

				update_site_option( 'bp_msgat_plugin_options', $submitted );
			}

			// Where are we redirecting to?
			$base_url = trailingslashit( network_admin_url() ) . 'settings.php';
			$redirect_url = add_query_arg(
				array(
					'page' => $this->plugin_slug,
					'updated' => 'true',
				),
				$base_url
			);

			// Redirect.
			wp_redirect( $redirect_url );
			die();
		}

		/**
		 * Add plugins settings link etc on plugins listing page.
		 *
		 * @param array  $links existing links.
		 * @param string $file plugin base file name.
		 * @return array
		 */
		public function add_action_links( $links, $file ) {
			// Return normal links if not this plugin.
			if ( plugin_basename( basename( constant( 'BPMSGAT_PLUGIN_DIR' ) ) . '/loader.php' ) !== $file ) {
				return $links;
			}

			$mylinks = array(
				'<a href="' . esc_url( $this->plugin_settings_url ) . '">' . esc_html__( 'Settings', 'bp-msgat' ) . '</a>',
			);

			return array_merge( $links, $mylinks );
		}

		/**
		 * Undocumented function
		 *
		 * @param array  $attrs attributes of the image.
		 * @param object $attachment attachment object.
		 * @return array
		 */
		public function attachment_image_attributes_add_info_icon( $attrs, $attachment ) {
			$is_bp_msg_at = get_post_meta( $attachment->ID, '_is_bp_msgat', true );
			if ( $is_bp_msg_at ) {
				$attrs['class'] .= ' is_bp_msgat';
				$msg_id = get_post_meta( $attachment->ID, '_bp_message_id', true );
				if ( $msg_id ) {
					$attrs['class'] .= ' has_bp_msg';
				} else {
					$attrs['class'] .= ' no_bp_msg';
				}
			}

			return $attrs;
		}

		/**
		 * Print inline css in admin footer, on some pages.
		 *
		 * @return boolean
		 */
		public function admin_footer() {
			global $current_screen;
			if ( 'upload' !== $current_screen->id ) {
				return false;
			}
			?>
			<style type="text/css">
			.media-icon:has( > img.is_bp_msgat ){
				position: relative;
			}
			.media-icon:has( > img.is_bp_msgat ):before{
				position: absolute;
				top: 0;
				left: 0;
				content: "\f465";
				font-family: dashicons;
				font-size: 20px;
				color: #999;
				background-color: #fff;
			}
			.media-icon:has( > img.is_bp_msgat.no_bp_msg ):after{
				position: absolute;
				top: -6px;
				left: -4px;
				content: "\f158";
				font-family: dashicons;
				font-size: 10px;
				color: red;
			}
			</style>
			<?php
		}
	}

	// end class.

endif;