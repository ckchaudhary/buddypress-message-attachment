<?php
/**
 * Actions Class.
 *
 * @package bpmsgat
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_Msgat_Action' ) ) :

	/**
	 * Class to handle most of core functionality.
	 *
	 * @since 1.0.0
	 */
	class BP_Msgat_Action {
		/**
		 * Meta key name for saving attachment ids against a message id.
		 *
		 * @var string
		 */
		private $meta_key = 'message_attachments';

		/**
		 * Empty constructor function to ensure a single instance
		 */
		public function __construct() {
			// ... leave empty, see Singleton below
		}

		/**
		 * Get the single instance of this class
		 *
		 * @return \BP_Msgat_Action
		 */
		public static function instance() {
			static $instance = null;

			if ( null === $instance ) {
				$instance = new BP_Msgat_Action();
				$instance->setup();
			}

			return $instance;
		}

		/**
		 * Get a settings values
		 *
		 * @param string $key setting name.
		 * @return mixed
		 */
		public function option( $key ) {
			$value = bp_message_attachment()->option( $key );
			return $value;
		}

		/**
		 * Initialize everything.
		 *
		 * @return void
		 */
		public function setup() {
			if ( ! is_admin() && ! is_network_admin() ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'add_css_js' ) );
			}

			$display_hooks = apply_filters( 'bp_msgat_form_display_hooks', array( 'bp_after_messages_compose_content', 'bp_after_message_reply_box' ) );
			foreach ( $display_hooks as $action_name ) {
				add_action( $action_name, array( $this, 'show_attachment_form' ) );
			}

			add_action( 'wp_ajax_bp_msgat_upload', array( $this, 'ajax_upload_file' ) );
			add_action( 'messages_message_after_save', array( $this, 'add_attachments' ) );
			add_action( 'bp_after_message_content', array( $this, 'show_attachments' ) );
		}

		/**
		 * Load css and javascript files.
		 *
		 * @return void
		 */
		public function add_css_js() {
			if ( ! bp_is_current_component( 'messages' ) ) {
				return;
			}

			wp_enqueue_script( 'bp-msgat', BPMSGAT_PLUGIN_URL . 'assets/js/script.min.js', array( 'jquery', 'plupload-all' ), '3.0.0', true );
			// phpcs:ignore
			// wp_enqueue_script( 'bp-msgat', BPMSGAT_PLUGIN_URL . 'assets/js/script.js', array( 'jquery', 'plupload-all' ), time(), true );

			if ( $this->option( 'load-css' ) === 'yes' ) {
				wp_enqueue_style( 'bp-msgat', BPMSGAT_PLUGIN_URL . 'assets/css/style.css', array(), '2.0' );
			}

			$data = apply_filters(
				'bp_msgat_script_data',
				array(
					'uploader' => array(
						'max_file_size' => (int) $this->option( 'max-size' ) . 'mb',
						'multiselect' => false, // can enable it in future.
						'nonce' => wp_create_nonce( 'bp_msgat_upload' ),
						'flash_swf_url' => includes_url( 'js/plupload/plupload.flash.swf' ),
						'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
						'filters' => array(
							array(
								'title'         => __( 'Allowed Files', 'bp-msgat' ),
								'extensions'    => implode( ',', $this->option( 'file-types' ) ),
								'max_file_size' => (int) $this->option( 'max-size' ) . 'mb',
							),
						),
					),
					'selectors' => array(
						'form_message' => '#send_message_form',
						'form_reply' => '#send-reply',
					),
					'lang' => array(
						'upload_error' => array(
							// translators: warning about chosen file exceeding maximum file size limit.
							'file_size' => sprintf( __( 'Uploaded file must not be more than %s mb', 'bp-msgat' ), $this->option( 'max-size' ) ),
							// translators: warning about undesired file type.
							'file_type' => sprintf( __( 'Selected file not allowed to be uploaded. It must be one of the following: %s', 'bp-msgat' ), implode( ', ', $this->option( 'file-types' ) ) ),
							'generic' => __( 'Error! File could not be uploaded.', 'bp-msgat' ),
						),
						'remove' => __( 'Remove', 'bp-msgat' ),
						'uploading' => __( 'Uploading...', 'bp-msgat' ),
					),
					'current_action' => bp_current_action(),
				),
			);
			wp_localize_script( 'bp-msgat', 'BPMsgAt_Util', $data );
		}

		/**
		 * Show the form
		 *
		 * @return void
		 */
		public function show_attachment_form() {
			if ( ! _device_can_upload() ) {
				return; // we can't do anything.
			}

			echo "<input type='hidden' name='bp_msgat_attachment_ids' value=''>";
			bp_msgat_buffer_template_part( 'form' );
		}

		/**
		 * Process file upload
		 *
		 * @return boolean
		 */
		public function ajax_upload_file() {
			// Check the nonce.
			check_ajax_referer( 'bp_msgat_upload' );

			if ( ! is_user_logged_in() ) {
				echo '-1';
				return false;
			}

			if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/media.php';
			}

			if ( ! function_exists( 'media_handle_upload' ) ) {
				require_once ABSPATH . 'wp-admin/includes/admin.php';
			}

			$aid = media_handle_upload( 'file', 0 );
			if ( is_wp_error( $aid ) ) {
				$result = array(
					'status' => false,
					'attachment_id' => 0,
					'name' => '',
					'message' => $aid->get_error_message(),
				);
			} else {
				\update_post_meta( $aid, '_is_bp_msgat', true );
				$file_info = wp_prepare_attachment_for_js( $aid );

				$result = array(
					'status' => ( null !== $aid ),
					'attachment_id' => (int) $aid,
					'name' => $file_info['title'],
				);
			}

			die( wp_json_encode( $result ) );
		}

		/**
		 * Save attachment ids in meta.
		 *
		 * @param object $msg the message object.
		 * @return void
		 */
		public function add_attachments( $msg ) {
			$attachment_ids = '';
			// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified elsewhere.
			if ( isset( $_POST['meta'] ) && isset( $_POST['meta']['bp_msgat_attachment_ids'] ) && ! empty( $_POST['meta']['bp_msgat_attachment_ids'] ) ) {
				$attachment_ids = sanitize_text_field( wp_unslash( $_POST['meta']['bp_msgat_attachment_ids'] ) );
			} else if ( isset( $_POST['bp_msgat_attachment_ids'] ) && ! empty( $_POST['bp_msgat_attachment_ids'] ) ) {
				$attachment_ids = sanitize_text_field( wp_unslash( $_POST['bp_msgat_attachment_ids'] ) );
			}
			// phpcs:enable

			if ( empty( $attachment_ids ) ) {
				return;
			}

			$attachment_ids_csv = trim( $attachment_ids, ',' );
			if ( $attachment_ids_csv && ! empty( $attachment_ids_csv ) ) {
				$attachment_ids_temp = explode( ',', $attachment_ids_csv );
				$attachment_ids = array();
				foreach ( $attachment_ids_temp as $a_id ) {
					$a_id = absint( trim( $a_id ) );
					if ( $a_id ) {
						$attachment_ids[] = $a_id;
					}
				}

				if ( ! empty( $attachment_ids ) ) {
					bp_messages_update_meta( $msg->id, $this->meta_key, $attachment_ids );

					foreach ( $attachment_ids as $a_id ) {
						update_post_meta( $a_id, '_bp_message_id', $msg->id );
					}
				}
			}
		}

		/**
		 * Show attachment after message content.
		 *
		 * @return void
		 */
		public function show_attachments() {
			global $thread_template;
			$message_id = $thread_template->message->id;
			$org_message_id = $message_id;

			$attachment_ids = bp_messages_get_meta( $message_id, $this->meta_key );

			if ( empty( $attachment_ids ) ) {
				return;
			}

			echo '<div class="attachments-wrapper">';

			do_action( 'bp_msgat_before_attachments_list' );

			foreach ( $attachment_ids as $a_id ) {
				$file_info = wp_prepare_attachment_for_js( $a_id );
				$file_type_group = bp_message_attachment()->get_file_type_group( $file_info['subtype'] );

				$file_info['file_type_group'] = $file_type_group;
				msgat_the_attachment( $file_info );

				bp_msgat_buffer_template_part( 'file', $file_type_group );
			}

			do_action( 'bp_msgat_after_attachments_list' );

			echo '</div><!-- .attachments-wrapper -->';
		}
	}//end class

endif;
