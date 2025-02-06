<?php


namespace CommonsBooking\View;

class Restriction extends View {

	/**
	 * Callback function send button.
	 *
	 * @param $field_args
	 * @param $field
	 */
	public static function renderSendButton( $field_args, $field ) {
		$id     = $field->args( 'id' );
		$label  = $field->args( 'name' );
		$desc   = $field->args( 'desc' );
		$postId = $field->object_id();
		$sent   = get_post_meta( $postId, \CommonsBooking\Model\Restriction::META_SENT, true );

		if ( $sent ) {
			$dateFormat = esc_html( get_option( 'date_format' ) );
			$timeFormat = esc_html( get_option( 'time_format' ) );
			$sent       = date( $dateFormat . ' | ' . $timeFormat, $sent );
		}

		?>
		<div class="cmb-row cmb-type-text">
			<div class="cmb-th">
				<label for="<?php echo esc_attr( $id ); ?>"><?php echo commonsbooking_sanitizeHTML( $label ); ?></label>
			</div>
			<div class="cmb-td">
				<input type="submit" name="<?php echo esc_attr( $id ); ?>"
						value="<?php echo esc_html__( 'Send', 'commonsbooking' ); ?>"/>
				<?php if ( $desc ) { ?>
					<p class="cmb2-metabox-description">
						<?php echo commonsbooking_sanitizeHTML( $desc ); ?>
					</p>
				<?php } ?>
				<?php if ( $sent ) { ?>
					<p class="cmb2-metabox-description">
						<?php echo esc_html__( 'Sent', 'commonsbooking' ) . ': ' . $sent; ?>
					</p>
				<?php } ?>
			</div>
		</div>
		<?php
	}
}