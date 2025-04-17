<?php

namespace CommonsBooking\Messages;

use CommonsBooking\Model\MessageRecipient;
use PHPMailer\PHPMailer\PHPMailer;
use WP_Error;
use function commonsbooking_parse_template;

/**
 * This is the base class for all messages
 */
abstract class Message {

	/**
	 * The actions that are valid for this message. Usually a string.
	 *
	 * @var array
	 */
	protected $validActions = [];

	/**
	 * The action that is used for this message. Needs to be contained in $validActions
	 *
	 * @var
	 */
	protected $action;

	/**
	 * The post that this message is about
	 *
	 * @var
	 */
	protected $post;

	/**
	 * The recipient(s) of this message
	 *
	 * @var
	 */
	protected $to;

	/**
	 * The e-mail headers
	 *
	 * @var
	 */
	protected $headers;

	/**
	 * The subject text of this message
	 *
	 * @var
	 */
	protected $subject;

	/**
	 * The body text of this message
	 *
	 * @var
	 */
	protected $body;

	/**
	 * An associative array of a string attachment.
	 *    'string' => String attachment data (required)
	 *    'filename' => Name for the attachment (required)
	 *    'encoding' => File encoding (defaults to 'base64')
	 *    'type' => File MIME type (if left unspecified, PHPMailer will try to work it out from the file name)
	 *    'disposition' => Disposition to use (defaults to 'attachment')
	 *
	 * @var array
	 */
	protected $attachment = [];
	private $postId;

	/**
	 * @param $postId
	 * @param $action
	 */
	public function __construct( $postId, $action ) {
		$this->postId = $postId;

		global $post;
		$post = $this->getPost();

		$this->action = $action;
	}

	public function getAction() {
		return $this->action;
	}

	/**
	 * Returns recipient address of the message.
	 *
	 * @return string|null
	 */
	public function getTo() {
		$recipient     = $this->to;
		$messageAction = $this->getAction();
		/**
		* E-Mail message recipient
		*
		* @since 2.7.3 refactored filter name from cb_mail_to
		* @since 2.1.1
		*
		* @param string $recipient email address recipient
		* @param string $messageAction email action (see valid actions of the implementing message class)
		*/
		return apply_filters( 'commonsbooking_mail_to', $recipient, $messageAction );
	}

	public function getHeaders() {
		return $this->headers;
	}

	/**
	 * Subject of the email message.
	 *
	 * @return string|null
	 */
	public function getSubject() {
		$subject       = $this->subject;
		$messageAction = $this->getAction();
		/**
		 * E-Mail message subject
		 *
		 * @since 2.7.3 refactored filter name from cb_mail_subject
		 * @since 2.1.1
		 *
		 * @param string $subject email subject
		 * @param string $messageAction email action (see valid actions of the implementing message class)
		 */
		return apply_filters( 'commonsbooking_mail_subject', $subject, $messageAction );
	}

	/**
	 * Return message body.
	 *
	 * @return string|null
	 */
	public function getBody() {
		$body          = $this->body;
		$messageAction = $this->getAction();
		/**
		 * E-Mail message body
		 *
		 * @since 2.7.3 refactored filter name from cb_mail_body
		 * @since 2.1.1
		 *
		 * @param string $body email body
		 * @param string $messageAction email action (see valid actions of the implementing message class)
		 */
		return apply_filters( 'commonsbooking_mail_body', $body, $messageAction );
	}

	/**
	 * Return array of attachment
	 *
	 * @return array
	 */
	public function getAttachment(): array {
		$attachment    = $this->attachment;
		$messageAction = $this->getAction();
		/**
		 * E-Mail attachment
		 *
		 * @since 2.7.3
		 *
		 * @param array|null $attachment
		 * @param string $messageAction email action (see valid actions of the implementing message class)
		 */
		return apply_filters( 'commonsbooking_mail_attachment', $attachment, $messageAction );
	}

	/**
	 * Setup the email template, headers (BCC)
	 *
	 * @param MessageRecipient $recipientUser User-Object
	 * @param string           $template_body template string
	 * @param string           $template_subject template string
	 * @param string           $from_headers From-Header (From:xxx)
	 * @param string|null      $bcc_adresses comma separated string with e-mail adresses
	 * @param object[]         $objects objects used in parse template function
	 * @param array|null       $attachment
	 */
	protected function prepareMail(
		MessageRecipient $recipientUser,
		string $template_body,
		string $template_subject,
		string $from_headers,
		string $bcc_adresses = null,
		array $objects = [],
		array $attachment = null
	): void {
		// Setup email: Recipient
		$this->to = sprintf( '%s <%s>', $recipientUser->getNiceName(), $recipientUser->getEmail() );

		// WPML: Switch system language to user´s set lang https://wpml.org/documentation/support/sending-emails-with-wpml/
		do_action( 'wpml_switch_language_for_email', $recipientUser->getEmail() );

		// check if templates are available
		if ( ! $template_body or ! $template_subject ) {
			new WP_Error( 'e-mail ', commonsbooking_sanitizeHTML( __( 'Could not send email because mail-template was not available. Check options -> templates', 'commonsbooking' ) ) );
		}

		// parse templates & replaces template tags (e.g. {{item:name}})
		// 'body' is HTML. 'subject' is not HTML needs alternative sanitation such that characters like &
		// do not get converted to HTML-entities like &amp;
		$this->body    = commonsbooking_sanitizeHTML( commonsbooking_parse_template( $template_body, $objects ) );
		$this->subject = sanitize_text_field( commonsbooking_parse_template( $template_subject, $objects, 'sanitize_text_field' ) );

		// Setup mime type
		$this->headers[] = 'MIME-Version: 1.0';
		$this->headers[] = 'Content-Type: text/html';

		// Setup email: From
		$this->headers[] = $from_headers;

		// add bcc adresses
		if ( ! empty( $bcc_adresses ) ) {
			$addresses_array = explode( ',', $bcc_adresses );
			$this->add_bcc( $addresses_array );
		}

		// add attachment when it exists
		if ( ! empty( $attachment ) ) {
			$this->attachment = $attachment;
		}
	}


	/**
	 * Send the email using wp_mail function
	 *
	 * You need to run prepareMail() before calling this function
	 *
	 * @return void
	 */
	public function sendNotificationMail() {
		$to         = $this->getTo();
		$subject    = $this->getSubject();
		$body       = $this->getBody();
		$attachment = $this->getAttachment();
		$headers    = implode( "\r\n", $this->headers );

		if ( ! empty( $attachment ) ) { // When attachment exists, modify wp_mail function to support attachment strings
			add_filter( 'wp_mail', array( $this, 'addStringAttachments' ), 25 ); // add arbitrary priority to identify filter for removal
			$result = wp_mail( $to, $subject, $body, $headers, $attachment );
			remove_filter( 'wp_mail', array( $this, 'addStringAttachments' ), 25 ); // remove filter directly after attachment is sent
		} else { // Sends regular mail, when no attachment present
			$result = wp_mail( $to, $subject, $body, $headers );
		}
		// WPML: Reset system lang
		do_action( 'wpml_reset_language_after_mailing' );
		do_action( 'commonsbooking_mail_sent', $this->getAction(), $result );
	}

	abstract public function sendMessage();

	/**
	 * Only send mail if action is valid
	 *
	 * @return void
	 */
	public function triggerMail(): void {
		if ( in_array( $this->getAction(), $this->getValidActions() ) ) {
			$this->sendMessage();
		}
	}

	/**
	 * @return mixed
	 */
	public function getPost() {
		if ( $this->post == null ) {
			$this->post = get_post( $this->getPostId() );
		}

		return $this->post;
	}

	/**
	 * @return mixed
	 */
	public function getPostId() {
		return $this->postId;
	}

	/**
	 * @param array $address_array
	 *
	 * @return void
	 */
	protected function add_bcc( array $address_array ) {
		// sanitize emails
		$address_array   = array_filter( array_map( 'sanitize_email', $address_array ) );
		$this->headers[] = sprintf( 'BCC:%s', implode( ',', $address_array ) );
	}

	/**
	 * @return array
	 */
	public function getValidActions(): array {
		return $this->validActions;
	}

	/**
	 * Adds support for defining attachments as data arrays in wp_mail().
	 * Allows us to send string-based or binary attachments (non-filesystem)
	 * and gives us more control over the attachment data.
	 *
	 * @param array $atts  Array of the `wp_mail()` arguments.
	 *    - string|string[] $to          Array or comma-separated list of email addresses to send message.
	 *    - string          $subject     Email subject.
	 *    - string          $message     Message contents.
	 *    - string|string[] $headers     Additional headers.
	 *    - string|string[] $attachments Paths to files to attach.
	 *
	 * @see https://gist.github.com/thomasfw/5df1a041fd8f9c939ef9d88d887ce023/
	 */
	public function addStringAttachments( $atts ) {
		$attachment_arrays = [];
		if ( ! empty( $atts['attachments'] ) ) {
			$attachments = $atts['attachments'];
			if ( is_array( $attachments ) ) {
				// Is the $attachments array a single array of attachment data, or an array containing multiple arrays of
				// attachment data? (note that the array may also be a one-dimensional array of file paths, as-per default usage).
				$is_multidimensional_array = count( $attachments ) == count( $attachments, COUNT_RECURSIVE ) ? false : true;
				if ( ! $is_multidimensional_array ) {
					$attachments = [ $attachments ];
				}
				// Work out which attachments we want to process here. If the value is an array with either
				// a 'path' or 'path' key, then we'll process it separately and remove it from the
				// $atts['attachments'] so that WP doesn't try to process it in wp_mail().
				foreach ( $attachments as $index => $attachment ) {
					if ( is_array( $attachment ) && ( array_key_exists( 'path', $attachment ) || array_key_exists( 'string', $attachment ) ) ) {
						$attachment_arrays[] = $attachment;
						if ( $is_multidimensional_array ) {
							unset( $atts['attachments'][ $index ] );
						} else {
							$atts['attachments'] = [];
						}
					}
				}
			}

			// Set the $wp_mail_attachments global to our attachment data.
			// We'll read this later to check if any extra attachments should
			// be added to the email. The value will be reset every time wp_mail()
			// is called.
			global $wp_mail_attachments;
			$wp_mail_attachments = $attachment_arrays;

			// We can't use the global $phpmailer to add our attachments directly in the 'wp_mail' filter callback because WP calls $phpmailer->clearAttachments()
			// after this filter runs. Instead, we now hook into the 'phpmailer_init' action (triggered right before the email is sent), and read
			// the $wp_mail_attachments global to check for any additional attachments to add.
			add_action(
				'phpmailer_init',
				/**
				 * @var $phpmailer PHPMailer
				 */
				function ( $phpmailer ) {
					// Check the $wp_mail_attachments global for any attachment data, and reset it for good measure.
					$attachment_arrays = [];
					if ( array_key_exists( 'wp_mail_attachments', $GLOBALS ) ) {
						global $wp_mail_attachments;
						$attachment_arrays   = $wp_mail_attachments;
						$wp_mail_attachments = [];
					}

					// Loop through our attachment arrays and attempt to add them using PHPMailer::addAttachment() or PHPMailer::addStringAttachment():
					foreach ( $attachment_arrays as $attachment ) {
						$is_filesystem_attachment = array_key_exists( 'path', $attachment ) ? true : false;
						try {
							$encoding    = $attachment['encoding'] ?? $phpmailer::ENCODING_BASE64;
							$type        = $attachment['type'] ?? '';
							$disposition = $attachment['disposition'] ?? 'attachment';
							if ( $is_filesystem_attachment ) {
								$phpmailer->addAttachment( ( $attachment['path'] ?? null ), ( $attachment['name'] ?? '' ), $encoding, $type, $disposition );
							} else {
								$phpmailer->addStringAttachment( ( $attachment['string'] ?? null ), ( $attachment['filename'] ?? '' ), $encoding, $type, $disposition );
							}
						} catch ( \Exception $e ) {
							continue;
						}
					}
				}
			);
		}
		return $atts;
	}
}
