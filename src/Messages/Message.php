<?php

namespace CommonsBooking\Messages;

use WP_Error;
use function commonsbooking_parse_template;

abstract class Message {

	protected $validActions = [];

	protected $postId;

	protected $action;

	protected $post;

	protected $to;

	protected $headers;

	protected $subject;

	protected $body;

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

	/**
	 * @return mixed
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * Setup the email template, headers (BCC)
	 * 
	 * @param object $recipientUser User-Object
	 * @param string $template_body template string
	 * @param string $template_subject template string 
	 * @param string $from_headers From-Header (From:xxx)
	 * @param string $bcc_adresses comma separated string with e-mail adresses 
	 * @param object $objects objects used in parse template function 
	 */
	protected function prepareMail(
		$recipientUser,
		$template_body,
		$template_subject,
		$from_headers,
		$bcc_adresses = null,
		$objects = []
	): void {
		// Setup email: Recipient
		$this->to = sprintf( '%s <%s>', $recipientUser->user_nicename, $recipientUser->user_email );

		// WPML: Switch system language to user´s set lang https://wpml.org/documentation/support/sending-emails-with-wpml/
		do_action( 'wpml_switch_language_for_email', $recipientUser->user_email );

		// check if templates are available
		if ( ! $template_body or ! $template_subject ) {
			new WP_Error( 'e-mail ', esc_html( __( "Could not send email because mail-template was not available. Check options -> templates", "commonsbooking" ) ) );
		}

		// parse templates & replaces template tags (e.g. {{item:name}})
		$this->body    = commonsbooking_sanitizeHTML( commonsbooking_parse_template( $template_body, $objects ) );
		$this->subject = commonsbooking_sanitizeHTML( commonsbooking_parse_template( $template_subject, $objects ) );

		// Setup mime type
		$this->headers[] = "MIME-Version: 1.0";
		$this->headers[] = "Content-Type: text/html";

		// Setup email: From
		$this->headers[] = $from_headers;

		
		// add bcc adresses 
		if ( ! empty ( $bcc_adresses ) ) {
			$addresses_array = explode( ',', $bcc_adresses );
			$this->add_bcc( $addresses_array );
		}
	}

	
	/**
	 * Send the email using wp_mail function
	 *
	 * @return void
	 */
	public function SendNotificationMail() {
		$to      = apply_filters( 'cb_mail_to', $this->to );
		$subject = apply_filters( 'cb_mail_subject', $this->subject );
		$body    = apply_filters( 'cb_mail_body', $this->body );
		$headers = implode( "\r\n", $this->headers );

		$result = wp_mail( $to, $subject, $body, $headers );

		// WPML: Reset system lang
		do_action( 'wpml_reset_language_after_mailing' );
		do_action( 'commonsbooking_mail_sent', $this->getAction(), $result );
	}

	abstract public function sendMessage();

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
	public function add_bcc( array $address_array ) {
		// sanitize emails
		$address_array = array_filter( array_map( 'sanitize_email', $address_array) );
		$this->headers[] = sprintf( "BCC:%s", implode(',', $address_array ) );
	}

	/**
	 * @return array
	 */
	public function getValidActions(): array {
		return $this->validActions;
	}

}
