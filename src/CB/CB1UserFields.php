<?php
/*
 * CB1 Legacy User Profile Fields
 * Adapted from CB1
 */

namespace CommonsBooking\CB;

use CommonsBooking\Settings\Settings;

class CB1UserFields {

	/**
	 * @var false|mixed
	 */
	private mixed $termsservices_url;
	/**
	 * @var array|string[]
	 */
	private array $registration_fields;
	/**
	 * @var array|array[]
	 */
	private array $extra_profile_fields;
	/**
	 * @var array|string[]
	 */
	private array $registration_fields_required;
	/**
	 * @var array|array[]
	 */
	private array $user_fields;
	/**
	 * @var array|mixed
	 */
	private mixed $user_vars;

	public function __construct() {

		// Registration: Form fields
		add_action( 'register_form', array( $this, 'registration_add_fields' ) );
		// Registration: Validation
		add_filter( 'registration_errors', array( $this, 'registration_set_errors' ), 10, 3 );
		// Registration: Write meta
		add_action( 'user_register', array( $this, 'registration_add_meta' ) );
		add_action( 'personal_options_update', array( $this, 'save_extra_profile_fields' ) );

		// Backend user profile fields
		add_action( 'edit_user_profile_update', array( $this, 'save_extra_profile_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'show_extra_profile_fields' ) );
		add_action( 'show_user_profile', array( $this, 'show_extra_profile_fields' ) );

		$this->termsservices_url = Settings::getOption( 'commonsbooking_options_migration', 'cb1-terms-url' );

		$this->registration_fields = array(
			'username',
			'password',
			'email',
			'first_name',
			'last_name',
			'phone',
			'address',
			'terms_accepted',
		);

		$this->extra_profile_fields = array(
			'first_name'     => array(
				'field_name'   => 'first_name',
				'title'        => commonsbooking_sanitizeHTML( __( 'First Name', 'commonsbooking' ) ),
				'type'         => 'input',
				'description'  => '',
				'errormessage' => commonsbooking_sanitizeHTML( __( 'Please enter your first name', 'commonsbooking' ) ),
			),
			'last_name'      => array(
				'field_name'   => 'last_name',
				'title'        => commonsbooking_sanitizeHTML( __( 'Last Name', 'commonsbooking' ) ),
				'type'         => 'input',
				'description'  => '',
				'errormessage' => commonsbooking_sanitizeHTML( __( 'Please enter your last name', 'commonsbooking' ) ),
			),
			'phone'          => array(
				'field_name'   => 'phone',
				'title'        => commonsbooking_sanitizeHTML( __( 'Phone Number', 'commonsbooking' ) ),
				'type'         => 'input',
				'description'  => '',
				'errormessage' => commonsbooking_sanitizeHTML( __( 'Please enter your phone number', 'commonsbooking' ) ),
			),
			'address'        => array(
				'field_name'   => 'address',
				'title'        => commonsbooking_sanitizeHTML( __( 'Address', 'commonsbooking' ) ),
				'type'         => 'input',
				'description'  => '',
				'errormessage' => commonsbooking_sanitizeHTML( __( 'Please enter your address', 'commonsbooking' ) ),
			),
			'terms_accepted' => array(
				'title'        => commonsbooking_sanitizeHTML( __( 'Terms and Conditions', 'commonsbooking' ) ),
				'field_name'   => 'terms_accepted',
				'type'         => 'checkbox',
				'description'  => commonsbooking_sanitizeHTML( __( 'I accept the terms & conditions', 'commonsbooking' ) ),
				'errormessage' => commonsbooking_sanitizeHTML( __( 'Please accept the terms & conditions', 'commonsbooking' ) ),
			),
		);


		$this->registration_fields_required = $this->registration_fields;

		$this->user_fields = $this->get_extra_profile_fields();

	}

	/*
	*   Adds the user fields to the wordpress registration
	*
	* @since    0.6
	*
	*/

	/**
	 * Get the additional User fields
	 *
	 * @return array
	 * @since    0.6.
	 *
	 */
	public function get_extra_profile_fields(): array {
		return $this->extra_profile_fields;
	}

	/*
	*   Adds error handling
	*
	* @since    0.6
	*
	* @return    object
	*/

	public function registration_add_fields() {

		foreach ( $this->user_fields as $field ) {

			$row = ( ! empty( $_POST[ $field['field_name'] ] ) ) ? sanitize_text_field( trim( $_POST[ $field['field_name'] ] ) ): '';
			?>
            <p>
				<?php if ( $field['type'] == 'checkbox' ) { ?>
                    <label for="<?php esc_attr_e( $field['field_name'] ) ?>"><?php esc_attr_e(
							$field['title'],
							'commonsbooking'
						) ?><br/>
                        <input type="checkbox" name="<?php esc_attr_e( $field['field_name'] ) ?>"
                               id="<?php esc_attr_e( $field['field_name'] ) ?>" value="yes" <?php if ( $row == "yes" ) {
							echo "checked";
						} ?> /><?php esc_attr_e( $field['description'], 'commonsbooking' ) ?><br/>
                    </label>
					<?php echo commonsbooking_sanitizeHTML($this->get_termsservices_string()); ?>
				<?php } else { ?>
                    <label for="<?php esc_attr_e( $field['field_name'] ) ?>"><?php esc_attr_e($field['title'],'commonsbooking') ?><br/>
                        <input type="text" name="<?php esc_attr_e( $field['field_name'] ) ?>"
                               id="<?php esc_attr_e( $field['field_name'] ) ?>" class="input"
                               value="<?php echo esc_attr( wp_unslash( $row ) ); ?>" size="25"/><?php esc_attr_e($field['description'],'commonsbooking') ?>
                    </label>
				<?php } ?>
            </p>
			<?php
		}
	}

	/*
	*   Write user meta
	*
	* @since    0.6
	*
	*/

	/**
	 * Registration Form: Set terms & services String (Wrapped in URL)
	 *
	 * @return string
	 * @since    0.6
	 *
	 */
	public function get_termsservices_string(): string {
		if ( ! empty ( $this->termsservices_url ) ) {
			// translators: %s = terms and service url
			$string = sprintf(
				commonsbooking_sanitizeHTML( __( '<a href="%s" target=_blank">Read the terms and services</a>', 'commonsbooking' ) ),
				commonsbooking_sanitizeHTML( $this->termsservices_url )
			);
		} else {
			$string = "";
		}

		return $string;
	}

	public function registration_set_errors( $errors, $username, $email ) {

		foreach ( $this->user_fields as $field ) {

			if ( $field['type'] == 'checkbox' ) {
				if ( ! isset( $_POST[ $field['field_name'] ] ) ) {
					$errors->add( $field['field_name'] . '_error', $field['errormessage'] );
				}
			} else {
				if (
					empty( $_POST[ $field['field_name'] ] ) ||
					! empty( $_POST[ $field['field_name'] ] ) &&
					sanitize_text_field( trim( $_POST[ $field['field_name'] ] ) == '' ) ){
					$errors->add( $field['field_name'] . '_error', $field['errormessage'] );
				}
			}
		}

		return $errors;
	}

	public function registration_add_meta( $user_id ) {

		foreach ( $this->user_fields as $field ) {
			if ( ! empty( $_POST[ $field['field_name'] ] ) ) {
				$fieldName  = sanitize_text_field( $field['field_name'] );
				$fieldValue = sanitize_text_field( trim( $_POST[ $field['field_name'] ] ) );
				update_user_meta( $user_id, $fieldName, $fieldValue );
			}
		}
	}

	/**
	 * Sets a flat array of user field/value pairs
	 *
	 * @since    2.10 deprecated (cb_object_to_array is unspecified)
	 * @since    0.6
	 *
	 */
	public function set_basic_user_vars( $user_id ) {
		$user_basic = get_user_by( 'id', $user_id );
		$user_meta  = get_user_meta( $user_id );

		// transform from object to an array that the cb_replace_template_tags functions expects
		$user_basic_array = cb_object_to_array( $user_basic );

		$user_meta_array = array();
		foreach ( $user_meta as $key => $value ) {
			$user_meta_array[ $key ] = $value[0];
		}

		// merge the arrays
		$this->user_vars = array_merge( $user_basic_array['data'], $user_meta_array );
	}

	/**
	 * Add addiotinal key/value pairs to the user_vars array
	 *
	 * @since    0.5.3
	 *
	 */
	public function add_user_vars( $key, $value ) {

		$this->user_vars[ $key ] = $value;
	}

	/**
	 * Backend: Show the extra profile fields
	 *
	 * @since    0.2
	 *
	 */
	public function show_extra_profile_fields( $user ) { ?>

        <h3><?php _e( 'Extra Fields', 'commonsbooking' ); ?> </h3>

        <table class="form-table">
            <tr>
                <th><label for="phone"><?php esc_html_e( 'Phone number', 'commonsbooking' ); ?></label></th>
                <td>
                    <input type="text" name="phone" id="phone"
                           value="<?php echo esc_attr( get_the_author_meta( 'phone', $user->ID ) ); ?>"
                           class="regular-text"/><br/>
                </td>
            </tr>
            <tr>
                <th><label for="address"><?php esc_html_e( 'Address', 'commonsbooking' ); ?></label></th>
                <td>
                    <input type="textarea" name="address" id="address"
                           value="<?php echo esc_attr( get_the_author_meta( 'address', $user->ID ) ); ?>"
                           class="regular-text"/><br/>
                </td>
            </tr>
            <tr>
                <th><label for="terms_accepted"><?php esc_html_e( 'Terms and conditions', 'commonsbooking' ); ?></label>
                </th>
                <td>
                    <input type="checkbox" name="terms_accepted" id=" terms_accepted " disabled
                           value="yes" <?php if ( esc_attr( get_the_author_meta( "terms_accepted", $user->ID ) ) == "yes" ) {
						echo "checked";
					} ?> /><?php esc_html_e( 'Accepted Terms & Conditions', 'commonsbooking' ); ?><br/>
                </td>
            </tr>
        </table>
	<?php }

	/**
	 * Backend: Update the extra profile fields
	 *
	 * @since    0.2
	 *
	 */
	public function save_extra_profile_fields( $user_id ) {
		if ( current_user_can( 'edit_user', $user_id ) ) {
			$phone   = sanitize_text_field( $_POST['phone'] );
			$address = sanitize_text_field( $_POST['address'] );

			update_user_meta( $user_id, 'phone', $phone );
			update_user_meta( $user_id, 'address', $address );
		}
	}

}


