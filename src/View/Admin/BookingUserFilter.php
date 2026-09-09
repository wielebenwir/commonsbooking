<?php

namespace CommonsBooking\View\Admin;

use CommonsBooking\Repository\UserRepository;
use CommonsBooking\Wordpress\CustomPostType\Booking;
use WP_Query;
use WP_User;

/**
 * User filter for the booking list in the WordPress backend.
 */
class BookingUserFilter {

	public const AJAX_ACTION = 'cb_booking_user_search';

	private const FILTER_PARAM         = 'admin_filter_user';
	private const FILTER_USER_ID_PARAM = 'admin_filter_user_id';
	private const AUTOCOMPLETE_LIMIT   = 20;

	/**
	 * Registers the hooks required by the booking user filter.
	 */
	public static function initHooks(): void {
		add_action( 'restrict_manage_posts', array( self::class, 'renderFilter' ) );
		add_action( 'pre_get_posts', array( self::class, 'applyFilter' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueueAssets' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( self::class, 'ajaxSearchUsers' ) );
	}

	/**
	 * Renders the autocomplete input above the booking list.
	 */
	public static function renderFilter(): void {
		if ( ! self::isBookingListScreen() ) {
			return;
		}

		$value  = self::getRequestValue( self::FILTER_PARAM );
		$userId = self::getRequestUserId();
		$label  = esc_html__( 'Filter bookings by user or email', 'commonsbooking' );
		?>
		<label class="screen-reader-text" for="<?php echo esc_attr( self::FILTER_PARAM ); ?>">
			<?php echo esc_html( $label ); ?>
		</label>
		<input
			type="hidden"
			name="<?php echo esc_attr( self::FILTER_USER_ID_PARAM ); ?>"
			id="<?php echo esc_attr( self::FILTER_USER_ID_PARAM ); ?>"
			value="<?php echo esc_attr( (string) $userId ); ?>"
		/>
		<input
			type="search"
			name="<?php echo esc_attr( self::FILTER_PARAM ); ?>"
			id="<?php echo esc_attr( self::FILTER_PARAM ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			placeholder="<?php echo esc_attr__( 'User or email', 'commonsbooking' ); ?>"
			class="cb-booking-user-filter"
			autocomplete="off"
			aria-label="<?php echo esc_attr( $label ); ?>"
		/>
		<?php
	}

	/**
	 * Adds autocomplete configuration on the booking list screen.
	 *
	 * @param string $hookSuffix Current admin page hook.
	 */
	public static function enqueueAssets( string $hookSuffix ): void {
		if ( $hookSuffix !== 'edit.php' || ! self::isBookingListScreen() ) {
			return;
		}

		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_localize_script(
			'cb-scripts-admin',
			'cbBookingUserFilter',
			[
				'action'            => self::AJAX_ACTION,
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( self::AJAX_ACTION ),
				'inputId'           => self::FILTER_PARAM,
				'userIdInputId'     => self::FILTER_USER_ID_PARAM,
				'minimumLength'     => 2,
				'noResults'         => esc_html__( 'No matching users found.', 'commonsbooking' ),
				'oneResult'         => esc_html__( 'One user found. Use the arrow keys to navigate.', 'commonsbooking' ),
				/* translators: %d: Number of matching users. */
				'multipleResults'   => esc_html__( '%d users found. Use the arrow keys to navigate.', 'commonsbooking' ),
			]
		);
	}

	/**
	 * Returns matching users for the autocomplete field.
	 */
	public static function ajaxSearchUsers(): void {
		check_ajax_referer( self::AJAX_ACTION, 'nonce' );

		if ( ! self::currentUserCanAccessBookingList() ) {
			wp_send_json_error(
				[ 'message' => esc_html__( 'You are not allowed to filter bookings.', 'commonsbooking' ) ],
				403
			);
		}

		$term = isset( $_POST['term'] )
			? trim( sanitize_text_field( wp_unslash( $_POST['term'] ) ) )
			: '';
		if ( strlen( $term ) < 2 ) {
			wp_send_json_success( [] );
		}

		$results = array_map(
			array( self::class, 'formatUserResult' ),
			UserRepository::search( $term, self::AUTOCOMPLETE_LIMIT )
		);
		wp_send_json_success( $results );
	}

	/**
	 * Applies the selected user or a free-text user search to the booking list.
	 *
	 * @param WP_Query $query Current admin list query.
	 */
	public static function applyFilter( WP_Query $query ): void {
		if ( ! self::isBookingListQuery( $query ) ) {
			return;
		}

		$filterValue = self::getRequestValue( self::FILTER_PARAM );
		$searchValue = self::getRequestValue( 's' );
		$term        = $filterValue !== '' ? $filterValue : $searchValue;
		if ( $term === '' ) {
			return;
		}

		$selectedUserId = $filterValue !== '' ? self::getRequestUserId() : 0;
		$userIds        = $selectedUserId > 0 && get_user_by( 'ID', $selectedUserId )
			? [ $selectedUserId ]
			: UserRepository::searchIds( $term );

		if ( $userIds === [] ) {
			if ( $filterValue !== '' ) {
				$query->set( 'author__in', [ 0 ] );
				$query->set( 's', '' );
			}
			return;
		}

		$query->set( 'author__in', $userIds );
		$query->set( 's', '' );
	}

	/**
	 * Formats a user for jQuery UI autocomplete.
	 *
	 * @param WP_User $user User object.
	 * @return array{id: int, label: string, value: string}
	 */
	public static function formatUserResult( WP_User $user ): array {
		$details = array_values(
			array_filter(
				[
					$user->display_name !== $user->user_login ? $user->display_name : '',
					$user->user_email,
				]
			)
		);
		$label   = $user->user_login;
		if ( $details !== [] ) {
			$label .= ' — ' . implode( ' · ', $details );
		}

		return [
			'id'    => (int) $user->ID,
			'label' => $label,
			'value' => $user->user_login,
		];
	}

	private static function isBookingListScreen(): bool {
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();
		return $screen && $screen->id === 'edit-' . Booking::getPostType();
	}

	private static function isBookingListQuery( WP_Query $query ): bool {
		global $pagenow;

		if ( ! is_admin() || ! $query->is_main_query() || $pagenow !== 'edit.php' ) {
			return false;
		}

		$postType = $query->get( 'post_type' );
		if ( is_array( $postType ) ) {
			$postType = reset( $postType );
		}
		if ( ! $postType ) {
			$postType = self::getRequestValue( 'post_type' );
		}

		return $postType === Booking::getPostType();
	}

	private static function getRequestValue( string $key ): string {
		if ( ! isset( $_GET[ $key ] ) ) {
			return '';
		}

		return trim( sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) );
	}

	private static function getRequestUserId(): int {
		if ( ! isset( $_GET[ self::FILTER_USER_ID_PARAM ] ) ) {
			return 0;
		}

		return absint( wp_unslash( $_GET[ self::FILTER_USER_ID_PARAM ] ) );
	}

	private static function currentUserCanAccessBookingList(): bool {
		$postType = get_post_type_object( Booking::getPostType() );
		return $postType && current_user_can( $postType->cap->edit_posts );
	}
}
