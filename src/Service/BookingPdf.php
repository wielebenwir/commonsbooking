<?php

namespace CommonsBooking\Service;

use CommonsBooking\Dompdf\Dompdf;
use CommonsBooking\Dompdf\Options;
use CommonsBooking\Model\Booking as BookingModel;
use CommonsBooking\Repository\Booking as BookingRepository;
use CommonsBooking\Settings\Settings;
use CommonsBooking\Wordpress\CustomPostType\Booking as BookingPostType;
use CommonsBooking\Wordpress\CustomPostType\Timeframe;
use RuntimeException;
use WP_Query;

use function commonsbooking_parse_template;

/**
 * Renders booking form PDFs for confirmed booking emails and admin previews.
 */
class BookingPdf {

	public const OPTION_ATTACH     = 'emailtemplates_mail-booking_pdf_attach';
	public const OPTION_TEMPLATE   = 'emailtemplates_mail-booking_pdf_body';
	public const ACTION_PREVIEW    = 'commonsbooking_preview-booking-pdf';
	public const ERROR_TYPE        = 'commonsbooking-booking-pdf-error';
	private const DEFAULT_TEMPLATE = 'assets/global/templates/booking-pdf-default.html';
	private const LOGO_PLACEHOLDER = '%%COMMONSBOOKING_PDF_LOGO%%';

	/**
	 * Get the localized default template for the booking form PDF.
	 *
	 * @return string
	 */
	public static function getDefaultTemplate(): string {
		// Cache per locale: the template is built several times per admin render (CMB2 default
		// value and the reset button), but its labels depend on the current locale.
		static $cache = [];
		$locale = get_locale();
		if ( isset( $cache[ $locale ] ) ) {
			return $cache[ $locale ];
		}

		$template = self::getDefaultTemplateMarkup();

		$cache[ $locale ] = commonsbooking_sanitizeHTML( strtr( $template, self::getDefaultTemplatePlaceholders() ) );

		return $cache[ $locale ];
	}

	/**
	 * Get the default PDF template markup from the bundled asset file.
	 *
	 * @return string
	 */
	private static function getDefaultTemplateMarkup(): string {
		if ( ! defined( 'COMMONSBOOKING_PLUGIN_DIR' ) ) {
			return '';
		}

		$templateFile = COMMONSBOOKING_PLUGIN_DIR . self::DEFAULT_TEMPLATE;
		if ( ! is_readable( $templateFile ) ) {
			return '';
		}

		return (string) file_get_contents( $templateFile ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}

	/**
	 * Get translated placeholders used by the bundled default template.
	 *
	 * @return array
	 */
	private static function getDefaultTemplatePlaceholders(): array {
		return [
			'{{pdf:title}}'            => esc_html__( 'Rental form', 'commonsbooking' ),
			'{{pdf:loan}}'             => esc_html__( 'Loan', 'commonsbooking' ),
			'{{pdf:pickup_date}}'      => esc_html__( 'Pickup date', 'commonsbooking' ),
			'{{pdf:return_date}}'      => esc_html__( 'Return date', 'commonsbooking' ),
			'{{pdf:rental_item}}'      => esc_html__( 'Rental item', 'commonsbooking' ),
			'{{pdf:location}}'         => esc_html__( 'Location', 'commonsbooking' ),
			'{{pdf:booking}}'          => esc_html__( 'Booking', 'commonsbooking' ),
			'{{pdf:borrower}}'         => esc_html__( 'Borrower', 'commonsbooking' ),
			'{{pdf:name}}'             => esc_html__( 'Name', 'commonsbooking' ),
			'{{pdf:street}}'           => esc_html__( 'Street and house number', 'commonsbooking' ),
			'{{pdf:postcode_city}}'    => esc_html__( 'Postcode and city', 'commonsbooking' ),
			'{{pdf:email}}'            => esc_html__( 'Email', 'commonsbooking' ),
			'{{pdf:accessories}}'      => esc_html__( 'Booked accessories', 'commonsbooking' ),
			'{{pdf:accessory_1}}'      => esc_html__( 'Accessory 1', 'commonsbooking' ),
			'{{pdf:accessory_2}}'      => esc_html__( 'Accessory 2', 'commonsbooking' ),
			'{{pdf:accessory_3}}'      => esc_html__( 'Accessory 3', 'commonsbooking' ),
			'{{pdf:accessory_4}}'      => esc_html__( 'Accessory 4', 'commonsbooking' ),
			'{{pdf:accessory_5}}'      => esc_html__( 'Accessory 5', 'commonsbooking' ),
			'{{pdf:other}}'            => esc_html__( 'Other', 'commonsbooking' ),
			'{{pdf:consent}}'          => esc_html__( 'I know the return opening hours, accept the applicable terms of use, and agree that my data may be processed for this loan.', 'commonsbooking' ),
			'{{pdf:place_date}}'       => esc_html__( 'Place, date', 'commonsbooking' ),
			'{{pdf:signature}}'        => esc_html__( 'Borrower signature', 'commonsbooking' ),
			'{{pdf:return_heading}}'   => esc_html__( 'To be completed after return', 'commonsbooking' ),
			'{{pdf:damages}}'          => esc_html__( 'Damages', 'commonsbooking' ),
			'{{pdf:damages_reported}}' => esc_html__( 'I have reported these damages both to the station and by email to the provider.', 'commonsbooking' ),
			'{{pdf:date_time}}'        => esc_html__( 'Date, time', 'commonsbooking' ),
			'{{pdf:terms_heading}}'    => esc_html__( 'Terms and return notes', 'commonsbooking' ),
			'{{pdf:terms_intro}}'      => esc_html__( 'This summary is a template and not legal advice. Please adapt it to your local terms of use before using the form.', 'commonsbooking' ),
			'{{pdf:term_responsible}}' => esc_html__( 'The user is responsible for the rented item for the duration of the loan.', 'commonsbooking' ),
			'{{pdf:term_no_passing}}'  => esc_html__( 'Passing the rented item on to third parties is not permitted unless your terms explicitly allow it.', 'commonsbooking' ),
			'{{pdf:term_traffic}}'     => esc_html__( 'The user must follow the applicable traffic rules and use the rented item carefully.', 'commonsbooking' ),
			'{{pdf:term_return}}'      => esc_html__( 'The rented item and accessories must be returned completely and on time to the agreed location.', 'commonsbooking' ),
			'{{pdf:term_damage}}'      => esc_html__( 'Damage, loss, or theft must be reported to the provider immediately.', 'commonsbooking' ),
			'{{pdf:term_liability}}'   => esc_html__( 'The user may be liable for costs and damage caused by improper use.', 'commonsbooking' ),
			'{{pdf:logo}}'             => self::LOGO_PLACEHOLDER,
		];
	}

	/**
	 * Get the logo markup used in the default PDF template.
	 *
	 * @return string
	 */
	private static function getRenderedLogoMarkup(): string {
		$logoSource = self::getDefaultLogoSource();
		if ( ! $logoSource ) {
			return '';
		}

		if ( file_exists( $logoSource ) ) {
			$logoDataUri = self::getLogoDataUri( $logoSource );
			if ( $logoDataUri ) {
				$logoSource = $logoDataUri;
			}
		}

		return sprintf(
			'<img class="header-logo" src="%s" alt="%s">',
			esc_attr( $logoSource ),
			esc_attr( get_bloginfo( 'name' ) )
		);
	}

	/**
	 * Get the current site logo source with a plugin logo fallback.
	 *
	 * @return string
	 */
	private static function getDefaultLogoSource(): string {
		$customLogoId = absint( get_theme_mod( 'custom_logo' ) );
		if ( $customLogoId > 0 ) {
			$customLogoFile = get_attached_file( $customLogoId );
			if ( $customLogoFile && file_exists( $customLogoFile ) ) {
				return $customLogoFile;
			}

			$customLogoUrl = wp_get_attachment_image_url( $customLogoId, 'full' );
			if ( $customLogoUrl ) {
				return $customLogoUrl;
			}
		}

		if ( defined( 'COMMONSBOOKING_PLUGIN_DIR' ) ) {
			$pluginLogoFile = COMMONSBOOKING_PLUGIN_DIR . 'assets/global/cb-ci/logo.png';
			if ( file_exists( $pluginLogoFile ) ) {
				return $pluginLogoFile;
			}
		}

		if ( defined( 'COMMONSBOOKING_PLUGIN_ASSETS_URL' ) ) {
			return COMMONSBOOKING_PLUGIN_ASSETS_URL . 'global/cb-ci/logo.png';
		}

		return '';
	}

	/**
	 * Convert a local logo file to a small JPEG data URI for reliable Dompdf rendering.
	 *
	 * @param string $logoFile Local logo file path.
	 *
	 * @return string
	 */
	private static function getLogoDataUri( string $logoFile ): string {
		if ( ! function_exists( 'imagecreatefromstring' ) ) {
			return '';
		}

		$logoContent = file_get_contents( $logoFile ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! $logoContent ) {
			return '';
		}

		$sourceImage = imagecreatefromstring( $logoContent );
		if ( ! $sourceImage ) {
			return '';
		}

		$sourceWidth  = imagesx( $sourceImage );
		$sourceHeight = imagesy( $sourceImage );
		$scale        = min( 1, 220 / $sourceWidth, 60 / $sourceHeight );
		$targetWidth  = max( 1, (int) floor( $sourceWidth * $scale ) );
		$targetHeight = max( 1, (int) floor( $sourceHeight * $scale ) );
		$targetImage  = imagecreatetruecolor( $targetWidth, $targetHeight );

		$white = imagecolorallocate( $targetImage, 255, 255, 255 );
		imagefilledrectangle( $targetImage, 0, 0, $targetWidth, $targetHeight, $white );
		imagealphablending( $targetImage, true );
		imagecopyresampled( $targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight );

		ob_start();
		imagejpeg( $targetImage, null, 90 );
		$resizedLogoContent = ob_get_clean();

		if ( ! $resizedLogoContent ) {
			return '';
		}

		return 'data:image/jpeg;base64,' . base64_encode( $resizedLogoContent ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Render the configured PDF template for a booking.
	 *
	 * @param BookingModel $booking Booking model.
	 *
	 * @return string PDF binary string.
	 * @throws RuntimeException When the PDF template is empty.
	 */
	public static function renderForBooking( BookingModel $booking ): string {
		$template = Settings::getOption( 'commonsbooking_options_templates', self::OPTION_TEMPLATE );
		if ( ! $template ) {
			throw new RuntimeException( esc_html__( 'The booking form PDF template is empty. Save a template before opening the preview.', 'commonsbooking' ) );
		}

		$html     = commonsbooking_parse_template( $template, self::getTemplateObjects( $booking ) );
		$html     = self::renderEmptyFieldLines( commonsbooking_sanitizeHTML( $html ) );
		$html     = self::wrapHtmlDocument( $html );
		$html     = str_replace( self::LOGO_PLACEHOLDER, self::getRenderedLogoMarkup(), $html );

		self::registerFontLibAliases();

		$dompdf = new Dompdf( self::getDompdfOptions() );
		$dompdf->loadHtml( $html, 'UTF-8' );
		$dompdf->setPaper( 'A4', 'portrait' );
		$dompdf->render();
		$dompdf->addInfo( 'Title', self::getDocumentTitle( $html ) );

		return $dompdf->output();
	}

	/**
	 * Replace empty value fields with printable fill-in lines.
	 *
	 * @param string $html Parsed template HTML.
	 *
	 * @return string
	 */
	private static function renderEmptyFieldLines( string $html ): string {
		return preg_replace(
			'/<div class="field-value">\s*<\/div>/',
			'<div class="fill-line"></div>',
			$html
		);
	}

	/**
	 * Build a PHPMailer string attachment for a booking PDF.
	 *
	 * @param BookingModel $booking Booking model.
	 *
	 * @return array
	 */
	public static function getAttachmentForBooking( BookingModel $booking ): array {
		return [
			'string'      => self::renderForBooking( $booking ),
			'filename'    => self::getFilenameForBooking( $booking ),
			'encoding'    => 'base64',
			'type'        => 'application/pdf',
			'disposition' => 'attachment',
		];
	}

	/**
	 * Validate the booking PDF settings after the email templates options page is saved.
	 *
	 * Surfaces a configuration problem as an admin notice when "attach PDF" is enabled
	 * but the PDF cannot be produced (missing PHP extension, empty or broken template),
	 * so the issue is caught while the admin is present instead of silently dropping the
	 * attachment from confirmation emails later.
	 *
	 * @return void
	 */
	public static function validateSettingsOnSave(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- CMB2 verified the nonce before this save hook runs.
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
		if ( $action !== 'commonsbooking_options_templates' ) {
			return;
		}

		if ( Settings::getOption( 'commonsbooking_options_templates', self::OPTION_ATTACH ) !== 'on' ) {
			return;
		}

		$error = self::getConfigurationError();
		if ( $error !== '' ) {
			set_transient( self::ERROR_TYPE, commonsbooking_sanitizeHTML( $error ), 45 );
		}
	}

	/**
	 * Get a configuration problem for the booking PDF feature, or an empty string when it is ready.
	 *
	 * @return string
	 */
	private static function getConfigurationError(): string {
		$missingExtensions = array_values(
			array_filter(
				[ 'mbstring', 'dom' ],
				static function ( string $extension ): bool {
					return ! extension_loaded( $extension );
				}
			)
		);
		if ( $missingExtensions ) {
			return sprintf(
				/* translators: %s: comma-separated list of PHP extension names. */
				__( 'The booking form PDF cannot be created because these PHP extensions are missing: %s. Ask your hosting provider to enable them or disable the PDF attachment.', 'commonsbooking' ),
				implode( ', ', $missingExtensions )
			);
		}

		if ( ! Settings::getOption( 'commonsbooking_options_templates', self::OPTION_TEMPLATE ) ) {
			return __( 'You enabled the booking form PDF attachment, but the PDF template is empty. Save a template or disable the attachment.', 'commonsbooking' );
		}

		// Without a confirmed booking we cannot test-render; the extension and template checks above still apply.
		$booking = self::getLatestConfirmedBooking();
		if ( ! $booking ) {
			return '';
		}

		try {
			self::renderForBooking( $booking );
		} catch ( \Throwable $e ) {
			return sprintf(
				/* translators: %s: error message from the PDF renderer. */
				__( 'The booking form PDF could not be rendered from the saved template: %s', 'commonsbooking' ),
				esc_html( $e->getMessage() )
			);
		}

		return '';
	}

	/**
	 * Record a failed booking PDF attachment so the failure is never lost silently.
	 *
	 * Always logs the error and queues an admin notice, so an enabled PDF attachment
	 * that stops working becomes visible instead of confirmation emails quietly going
	 * out without the PDF.
	 *
	 * @param BookingModel $booking Booking whose confirmation mail lost its PDF.
	 * @param \Throwable   $e       Rendering error.
	 *
	 * @return void
	 */
	public static function reportAttachmentFailure( BookingModel $booking, \Throwable $e ): void {
		error_log( sprintf( 'CommonsBooking: booking form PDF attachment failed for booking #%d: %s', $booking->ID, $e->getMessage() ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

		set_transient(
			self::ERROR_TYPE,
			commonsbooking_sanitizeHTML(
				sprintf(
					/* translators: 1: booking ID, 2: error message from the PDF renderer. */
					__( 'The confirmation email for booking #%1$d was sent without the booking form PDF because it could not be created: %2$s Check the booking form PDF template in the CommonsBooking email settings.', 'commonsbooking' ),
					$booking->ID,
					esc_html( $e->getMessage() )
				)
			),
			WEEK_IN_SECONDS
		);
	}

	/**
	 * Streams a booking PDF preview in the WordPress admin.
	 *
	 * @return void
	 */
	public static function previewAction(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			self::dieWithPreviewError( esc_html__( 'You are not allowed to preview booking PDFs.', 'commonsbooking' ), 403 );
			return;
		}

		$nonce = array_key_exists( '_wpnonce', $_GET ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! wp_verify_nonce( $nonce, self::ACTION_PREVIEW ) ) {
			self::dieWithPreviewError(
				esc_html__( 'The PDF preview link has expired. Reload the CommonsBooking settings page and try again.', 'commonsbooking' ),
				403
			);
			return;
		}

		try {
			$booking = self::getPreviewBooking();
			$pdf     = self::renderForBooking( $booking );
		} catch ( RuntimeException $e ) {
			self::dieWithPreviewError( $e->getMessage(), 400 );
			return;
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'CommonsBooking booking PDF preview failed: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			self::dieWithPreviewError(
				esc_html__( 'The booking form PDF could not be rendered. Check the saved template and try again.', 'commonsbooking' ),
				500
			);
			return;
		}

		$filename = self::getFilenameForBooking( $booking );

		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: inline; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode( $filename ) );
		header( 'Content-Length: ' . strlen( $pdf ) );
		echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Render the CMB2 row for the PDF preview button.
	 *
	 * @param array $field_args CMB2 field args.
	 * @param mixed $field CMB2 field object.
	 *
	 * @return void
	 */
	public static function renderPreviewRow( $field_args, $field ): void {
		unset( $field_args, $field );

		$template               = Settings::getOption( 'commonsbooking_options_templates', self::OPTION_TEMPLATE );
		$latestBooking          = self::getLatestConfirmedBooking();
		$previewDisabledMessage = '';
		if ( ! $template ) {
			$previewDisabledMessage = esc_html__( 'Save a booking form PDF template before opening the preview.', 'commonsbooking' );
		} elseif ( ! $latestBooking ) {
			$previewDisabledMessage = esc_html__( 'Create or confirm a booking before opening the PDF preview.', 'commonsbooking' );
		}
		$isPreviewDisabled = $previewDisabledMessage !== '';
		$previewUrl        = add_query_arg(
			[
				'action'   => self::ACTION_PREVIEW,
				'_wpnonce' => wp_create_nonce( self::ACTION_PREVIEW ),
			],
			admin_url( 'admin.php' )
		);
		$linkAttributes    = $isPreviewDisabled ? 'aria-disabled="true" tabindex="-1" ' : 'target="_blank" rel="noopener noreferrer" ';
		?>
		<div class="cmb-row cmb-type-text">
			<div class="cmb-th">
				<label for="booking-pdf-preview-id"><?php echo esc_html__( 'Booking form PDF preview', 'commonsbooking' ); ?></label>
			</div>
			<div class="cmb-td">
				<input
					type="number"
					min="1"
					id="booking-pdf-preview-id"
					placeholder="<?php echo esc_attr__( 'Latest confirmed booking', 'commonsbooking' ); ?>"
					<?php disabled( $isPreviewDisabled ); ?>
				>
				<a
					href="<?php echo esc_url( $isPreviewDisabled ? '#' : $previewUrl ); ?>"
					class="button button-secondary<?php echo $isPreviewDisabled ? ' disabled' : ''; ?>"
					<?php echo $linkAttributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					id="booking-pdf-preview-link"
					data-preview-url="<?php echo esc_url( $previewUrl ); ?>"
				>
					<?php echo esc_html__( 'Preview booking form PDF', 'commonsbooking' ); ?>
				</a>
				<button
					type="button"
					class="button button-secondary"
					id="booking-pdf-template-reset"
					data-default-template="<?php echo esc_attr( wp_json_encode( self::getDefaultTemplate() ) ); ?>"
				>
					<?php echo esc_html__( 'Reset to default template', 'commonsbooking' ); ?>
				</button>
				<?php if ( $previewDisabledMessage ) : ?>
					<div class="notice notice-warning inline">
						<p><?php echo esc_html( $previewDisabledMessage ); ?></p>
					</div>
				<?php endif; ?>
				<p class="cmb2-metabox-description">
					<?php
					if ( $latestBooking ) {
						echo esc_html(
							sprintf(
								/* translators: %d: latest confirmed booking ID. */
								__( 'Leave the booking ID empty to preview the latest confirmed booking (#%d).', 'commonsbooking' ),
								$latestBooking->ID
							)
						);
					} else {
						echo esc_html__( 'The preview uses a confirmed booking. You can enter a booking ID after at least one confirmed booking exists.', 'commonsbooking' );
					}
					?>
				</p>
				<p class="cmb2-metabox-description">
					<?php echo esc_html__( 'Reset replaces the template field with the default booking form template. Save the settings afterwards to keep the change.', 'commonsbooking' ); ?>
				</p>
				<?php
				ob_start();
				?>
					(function () {
						var input = document.getElementById('booking-pdf-preview-id');
						var link = document.getElementById('booking-pdf-preview-link');
						var reset = document.getElementById('booking-pdf-template-reset');
						var template = document.getElementById('<?php echo esc_js( self::OPTION_TEMPLATE ); ?>');
						if (!input || !link || !reset || !template) {
							return;
						}
						link.addEventListener('click', function (event) {
							if (link.getAttribute('aria-disabled') === 'true') {
								event.preventDefault();
								return;
							}
							var previewUrl = new URL(link.dataset.previewUrl, window.location.href);
							if (input.value) {
								previewUrl.searchParams.set('booking_id', input.value);
							}
							link.href = previewUrl.toString();
						});
						reset.addEventListener('click', function () {
							template.value = JSON.parse(reset.dataset.defaultTemplate);
							template.dispatchEvent(new Event('change', { bubbles: true }));
							template.focus();
						});
					})();
				<?php
				wp_print_inline_script_tag( ob_get_clean() );
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Show a helpful admin error for PDF preview failures.
	 *
	 * @param string $message  Error message.
	 * @param int    $response HTTP response code.
	 *
	 * @return void
	 */
	private static function dieWithPreviewError( string $message, int $response ): void {
		$messageHtml = sprintf(
			'<p>%s</p><p><a href="%s">%s</a></p>',
			esc_html( $message ),
			esc_url( admin_url( 'admin.php?page=commonsbooking_options_templates' ) ),
			esc_html__( 'Back to CommonsBooking email templates', 'commonsbooking' )
		);

		wp_die(
			wp_kses_post( $messageHtml ),
			esc_html__( 'Booking form PDF preview unavailable', 'commonsbooking' ),
			[
				'back_link' => true,
				'response'  => absint( $response ),
			]
		);
	}

	/**
	 * Build the template parser object map used by booking mail templates.
	 *
	 * @param BookingModel $booking Booking model.
	 *
	 * @return array
	 */
	private static function getTemplateObjects( BookingModel $booking ): array {
		return [
			'booking'  => $booking,
			'item'     => $booking->getItem(),
			'location' => $booking->getLocation(),
			'user'     => $booking->getUserData(),
		];
	}

	/**
	 * Get the document title shown by PDF viewers.
	 *
	 * @param string $html Rendered HTML.
	 *
	 * @return string
	 */
	private static function getDocumentTitle( string $html ): string {
		if ( preg_match( '/<h1[^>]*>(.*?)<\/h1>/is', $html, $matches ) === 1 ) {
			$title = trim( wp_strip_all_tags( $matches[1] ) );
			if ( $title !== '' ) {
				return $title;
			}
		}

		return self::getDefaultDocumentTitle();
	}

	/**
	 * Get the localized default document title.
	 *
	 * @return string
	 */
	private static function getDefaultDocumentTitle(): string {
		return __( 'Rental form', 'commonsbooking' );
	}

	/**
	 * Get the PDF filename used for previews and mail attachments.
	 *
	 * @param BookingModel $booking Booking model.
	 *
	 * @return string
	 */
	private static function getFilenameForBooking( BookingModel $booking ): string {
		return sanitize_file_name(
			sprintf(
				'%s-%s.pdf',
				self::getDefaultDocumentTitle(),
				$booking->post_name
			)
		);
	}

	/**
	 * Resolve the booking used for the preview request.
	 *
	 * @return BookingModel
	 * @throws RuntimeException When no confirmed preview booking can be found.
	 */
	private static function getPreviewBooking(): BookingModel {
		$bookingId = array_key_exists( 'booking_id', $_GET ) ? absint( $_GET['booking_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $bookingId > 0 ) {
			$booking = BookingRepository::getPostById( $bookingId );
			if ( ! $booking instanceof BookingModel || ! $booking->isConfirmed() ) {
				throw new RuntimeException( esc_html__( 'The selected booking does not exist or is not confirmed. Enter the ID of a confirmed booking.', 'commonsbooking' ) );
			}
			return $booking;
		}

		$booking = self::getLatestConfirmedBooking();
		if ( ! $booking ) {
			throw new RuntimeException( esc_html__( 'No confirmed booking found for the PDF preview. Confirm a booking first, then reload the CommonsBooking settings page.', 'commonsbooking' ) );
		}

		return $booking;
	}

	/**
	 * Get the latest confirmed booking for the default preview.
	 *
	 * @return BookingModel|null
	 */
	private static function getLatestConfirmedBooking(): ?BookingModel {
		$query = new WP_Query(
			[
				'post_type'      => BookingPostType::$postType,
				'post_status'    => 'confirmed',
				'posts_per_page' => 1,
				'orderby'        => 'ID',
				'order'          => 'DESC',
				'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'key'     => 'type',
						'value'   => Timeframe::BOOKING_ID,
						'compare' => '=',
					],
				],
			]
		);

		if ( ! $query->have_posts() ) {
			return null;
		}

		return new BookingModel( $query->posts[0] );
	}

	/**
	 * Ensure Dompdf receives a full UTF-8 HTML document.
	 *
	 * @param string $html Rendered template HTML.
	 *
	 * @return string
	 */
	private static function wrapHtmlDocument( string $html ): string {
		if ( preg_match( '/<html[\s>]/i', $html ) ) {
			return $html;
		}

		return sprintf(
			'<!doctype html><html><head><meta charset="utf-8"><title>%s</title><base href="%s"></head><body>%s</body></html>',
			esc_html( self::getDocumentTitle( $html ) ),
			esc_url( home_url( '/' ) ),
			$html
		);
	}

	/**
	 * Build secure Dompdf options for CommonsBooking PDFs.
	 *
	 * @return Options
	 */
	private static function getDompdfOptions(): Options {
		$allowedRemoteHosts = self::getAllowedRemoteHosts();

		$options = new Options();
		$options->setIsPhpEnabled( false );
		$options->setIsJavascriptEnabled( false );
		$options->setIsRemoteEnabled( ! empty( $allowedRemoteHosts ) );
		$options->setAllowedRemoteHosts( $allowedRemoteHosts );
		$options->setChroot( self::getChrootPaths() );

		return $options;
	}

	/**
	 * Map Dompdf's dynamically referenced FontLib classes to their prefixed names.
	 *
	 * Strauss prefixes static class references, but php-font-lib builds some class
	 * names dynamically from strings such as "FontLib\\TrueType\\File".
	 *
	 * @return void
	 */
	private static function registerFontLibAliases(): void {
		static $registered = false;

		if ( $registered ) {
			return;
		}

		spl_autoload_register(
			function ( string $className ): void {
				if (
					$className === 'FontLib\\FontLib\\TableDirectoryEntry' &&
					class_exists( 'CommonsBooking\\FontLib\\TrueType\\TableDirectoryEntry' )
				) {
					class_alias( 'CommonsBooking\\FontLib\\TrueType\\TableDirectoryEntry', $className, false );
					return;
				}

				if ( str_starts_with( $className, 'FontLib\\' ) && ! class_exists( $className, false ) ) {
					$prefixedClassName = 'CommonsBooking\\' . $className;
					if ( class_exists( $prefixedClassName ) ) {
						class_alias( $prefixedClassName, $className, false );
					}
				}
			}
		);

		$registered = true;
	}

	/**
	 * Get same-site hosts that Dompdf may load remote assets from.
	 *
	 * @return array
	 */
	private static function getAllowedRemoteHosts(): array {
		$uploadDir = wp_upload_dir();
		$urls      = [
			home_url( '/' ),
			site_url( '/' ),
			$uploadDir['baseurl'],
		];

		$hosts = array_filter(
			array_map(
				function ( $url ) {
					return wp_parse_url( $url, PHP_URL_HOST );
				},
				$urls
			)
		);

		return array_values( array_unique( $hosts ) );
	}

	/**
	 * Get local paths Dompdf may read assets from.
	 *
	 * @return array
	 */
	private static function getChrootPaths(): array {
		return array_values(
			array_filter(
				array_unique(
					[
						defined( 'ABSPATH' ) ? ABSPATH : '',
						defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : '',
						defined( 'COMMONSBOOKING_PLUGIN_DIR' ) ? COMMONSBOOKING_PLUGIN_DIR : '',
					]
				)
			)
		);
	}
}
