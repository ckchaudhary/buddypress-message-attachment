<?php
/**
 * Template file to load UI elements for attachments in message/reply forms.
 *
 * @package bpmsgat
 */

?>
<div class="bp_msgat_ui_wrapper">
	<label>
		<?php esc_html_e( 'Add an attachment', 'bp-msgat' ); ?>
	</label>
	<p>
		<button class="button button-secondary" id="btn_msgat_upload" name="btn_msgat_upload"><?php esc_html_e( 'Choose file', 'bp-msgat' ); ?></button>
		<small>
			<em>
				<?php
				esc_html_e( 'Allowed file types : ', 'bp-msgat' );
				echo esc_html( implode( ', ', bp_message_attachment()->option( 'file-types' ) ) );
				?>
			</em>
		</small>
	</p>
</div>
