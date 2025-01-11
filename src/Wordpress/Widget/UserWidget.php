<?php

namespace CommonsBooking\Wordpress\Widget;

use CommonsBooking\Settings\Settings;
use WP_Widget;

/**
 * Class provides the commonsbooking user widget
 */
class UserWidget extends WP_Widget {

	function __construct() {

		parent::__construct(
			'commonsbooking-user-widget',  // Base ID
			'CommonsBooking User Widget',   // Name
			array( 'description' => esc_html__( 'Shows links to My Bookings, Login, Logout. Please set the Bookings Page in CommonsBooking Settings (General-Tab)', 'commonsbooking' ) )
		);

		add_action(
			'widgets_init',
			function () {
				register_widget( '\CommonsBooking\Wordpress\Widget\UserWidget' );
			}
		);
	}

	public $args = array(
		'before_title'  => '<h4 class="widgettitle">',
		'after_title'   => '</h4>',
		'before_widget' => '<div class="widget-wrap">',
		'after_widget'  => '</div></div>',
	);

	public function widget( $args, $instance ) {

		echo commonsbooking_sanitizeHTML( $args['before_widget'] );

		if ( ! empty( $instance['title'] ) ) {
			echo commonsbooking_sanitizeHTML( $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'] );
		}

		echo '<div class="textwidget">';

		echo commonsbooking_sanitizeHTML( $this->renderWidgetContent() );

		echo '</div>';

		echo commonsbooking_sanitizeHTML( $args['after_widget'] );
	}

	public function renderWidgetContent() {

		$content = '';

		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();

			$bookings_page_url = get_permalink( Settings::getOption( COMMONSBOOKING_PLUGIN_SLUG . '_options_general', 'bookings_page' ) );
			if ( empty( $bookings_page_url ) ) {
				$bookings_page_url = get_home_url();
			}

			// user name or email
			if ( ! empty( $current_user->first_name ) ) {
				$loginname = $current_user->first_name;
			} else {
				$loginname = $current_user->user_email;
			}

			// translators: $s = user first name or email
			$content .= sprintf( __( 'Welcome %s', 'commonsbooking' ), $loginname );
			$content .= ' <ul>';
			// translators: $s = bookings page url
			$content .= sprintf( __( '<li><a href="%s">My Bookings</a></li>', 'commonsbooking' ), $bookings_page_url );
			// translators: $s = user profile url
			$content .= sprintf( __( '<li><a href="%s">My Profile</a></li>', 'commonsbooking' ), get_edit_profile_url() );
			// translators: $s =  wp logout url
			$content .= sprintf( __( '<li><a href="%s">Log out</a></li>', 'commonsbooking' ), wp_logout_url() );
			$content .= '</ul>';
		} else {
			$content  = __( 'You are not logged in.', 'commonsbooking' );
			$content .= '<ul>';
			// translators: $s = wp login url
			$content .= sprintf( __( '<li><a href="%s">Login</a></li>', 'commonsbooking' ), wp_login_url() );
			// translators: $s = wp registration url
			$content .= sprintf( __( '<li><a href="%s">Register</a></li>', 'commonsbooking' ), wp_registration_url() );
			$content .= '</ul>';
		}

		return $content;
	}

	/**
	 * @param $instance
	 *
	 * @return string
	 */
	public function form( $instance ) {

		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( '', 'commonsbooking' );
		$text  = ! empty( $instance['text'] ) ? $instance['text'] : esc_html__( '', 'commonsbooking' );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php echo esc_html__( 'Title:', 'commonsbooking' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
					value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'Text' ) ); ?>"><?php echo esc_html__( 'Text:', 'commonsbooking' ); ?></label>
			<textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>"
						name="<?php echo esc_attr( $this->get_field_name( 'text' ) ); ?>" type="text" cols="30"
						rows="10"><?php echo esc_attr( $text ); ?></textarea>
		</p>
		<?php

		return ''; // Parent class returns string, not used
	}

	public function update( $new_instance, $old_instance ) {

		$instance = array();

		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['text']  = ( ! empty( $new_instance['text'] ) ) ? $new_instance['text'] : '';

		return $instance;
	}
}

$my_widget = new UserWidget();
