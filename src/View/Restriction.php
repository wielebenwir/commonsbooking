<?php


namespace CommonsBooking\View;


class Restriction extends View {


	public static function content( \WP_Post $post ) {
		// TODO: Implement content() method.
	}

	/**
	 * Callback function send button.
	 *
	 * @param $field_args
	 * @param $field
	 */
	public static function renderSendButton( $field_args, $field ) {
		$id          = $field->args( 'id' );
		$label       = $field->args( 'name' );
		$postId = $field->object_id();
		$sent = get_post_meta( $postId, \CommonsBooking\Model\Restriction::META_SENT, true );

		if($sent) {
			$dateFormat = get_option( 'date_format' );
			$timeFormat = get_option( 'time_format' );
			$sent       = date( $dateFormat . ' | ' . $timeFormat, $sent );
		}

		?>
		<div class="cmb-row cmb-type-text">
			<div class="cmb-th">
				<label for="<?php echo $id; ?>"><?php echo $label; ?></label>
			</div>
			<div class="cmb-td">
				<input type="submit" name="<?php echo $id; ?>" value="<?php echo esc_html__( 'Send', 'commonsbooking' ); ?>" />
				<?php if($sent) { ?>
					<p class="cmb2-metabox-description">
                        <?php echo esc_html__( "Sent", 'commonsbooking' ) .": " . $sent; ?>
                    </p>
				<?php } ?>
			</div>
		</div>
		<?php
	}

}