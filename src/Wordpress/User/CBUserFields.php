<?php
/**
 * Add costum CommonsBooking internal user fields, e.g. for storing heavy-user score etc.
 */
namespace CommonsBooking\Wordpress\User;

class CBUserFields {

    function __construct() {
        
        // extra fields are only visible for administrators
        if ( ! commonsbooking_isCurrentUserAdmin() ) {
            return;
        }

        add_action( 'show_user_profile', array( $this, 'commonsbooking_extra_user_profile_fields' ) );
        add_action( 'edit_user_profile', array( $this, 'commonsbooking_extra_user_profile_fields' ) );

        add_action( 'personal_options_update', array( $this, 'save_extra_user_profile_fields' ) );
        add_action( 'edit_user_profile_update', array( $this, 'save_extra_user_profile_fields' ) );

        $this->extra_user_fields = array(
			'first_name' => array(
				'field_name'  => 'user_max_booking_days_advance',
				'label'       => commonsbooking_sanitizeHTML( __( 'User max booking days in advance', 'commonsbooking' ) ),
				'type'        => 'text',
				'description' => commonsbooking_sanitizeHTML( __( 'Set individual max booking days in advance restriction for this user. Set to 0 to disallow any booking. <br>Set to -1 to use the standard value set by timeframe configuration.', 'commonsbooking' ) ),
			),
		);

    }
	
	/**
	 * Adds custom user fields
	 *
	 * @param  mixed $user
	 * @return void
	 */
	public function commonsbooking_extra_user_profile_fields( $user ) {

        ?>
         <h3><?php echo commonsbooking_sanitizeHTML( __( 'CommonsBooking internal user fields', 'commonsbooking' ) ); ?>
        </h3>
         <table class="form-table">
        <?php
		foreach ( $this->extra_user_fields as $field ) {
            ?>
            <tr>
                <th><label for="<?php echo commonsbooking_sanitizeHTML( $field['field_name'] ); ?>"><?php echo commonsbooking_sanitizeHTML( $field['label'] ); ?></label></th>
                <td>
                    <input type="<?php echo commonsbooking_sanitizeHTML( $field['type'] ); ?>" name="<?php echo commonsbooking_sanitizeHTML( $field['field_name'] ); ?>" id="<?php echo commonsbooking_sanitizeHTML( $field['field_name'] ); ?>" 
                        value="<?php echo esc_attr( get_user_meta( $user->ID, $field['field_name'], true ) ); ?>" class="regular-text" /><br />
                    <span class="description"><?php echo commonsbooking_sanitizeHTML( $field['description'] ); ?></span>
                </td>
            </tr>
            <?php
        } // end foreach
        ?>
            </table>
        <?php
    }
    
    /**
     * Save extra user fields
     *
     * @param  mixed $user_id
     * @return void
     */
    public function save_extra_user_profile_fields( $user_id ) {

        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            return false;
        }

        update_user_meta( $user_id, 'user_max_booking_days_advance', intval( sanitize_text_field( $_POST['user_max_booking_days_advance'] ) ) );

    }
}
