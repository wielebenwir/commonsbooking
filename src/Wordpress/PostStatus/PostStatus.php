<?php

namespace CommonsBooking\Wordpress\PostStatus;

class PostStatus {

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $label;

	/**
	 * @var bool
	 */
	protected $public;

	/**
	 * PostStatus constructor.
	 *
	 * @param $name
	 * @param $label
	 * @param bool  $public
	 */
	public function __construct( $name, $label, bool $public = true ) {
		$this->name   = $name;
		$this->label  = $label;
		$this->public = $public;

		$this->registerPostStatus();
		$this->addActions();
	}

	/**
	 * Registers current post status.
	 */
	public function registerPostStatus() {
		register_post_status(
			$this->name,
			array(
				'label'       => $this->label,
				'public'      => $this->public,
				'label_count' => _n_noop(
					$this->label . ' <span class="count">(%s)</span>',
					$this->label . ' <span class="count">(%s)</span>'
				),
			)
		);
	}

	/**
	 * Adds edit actions for post-status to backend.
	 */
	public function addActions() {
		add_action( 'admin_footer-edit.php', array( $this, 'addQuickedit' ) );
		add_action( 'admin_footer', array( $this, 'addOption' ) );
	}

	/**
	 * Adds poststatus option to backend.
	 */
	public function addOption() {
		global $post;

		$active = '';
		if ( $post ) {
			if ( $post->post_status == $this->name ) {
				$active = "jQuery( '#post-status-display' ).text( '" . $this->label . "' ); jQuery( 'select[name=\"post_status\"]' ).val('" . $this->name . "');";
			}

			echo "<script>
            jQuery(document).ready( function() {
                jQuery( 'select[name=\"post_status\"]' ).append( '<option value=\"" . commonsbooking_sanitizeHTML( $this->name ) . '">' . commonsbooking_sanitizeHTML( $this->label ) . "</option>' );
                " . commonsbooking_sanitizeHTML( $active ) . '
            });
        </script>';
		}
	}

	/**
	 * Adds poststatus quickedit to backend.
	 */
	public function addQuickedit() {
		echo "<script>
                jQuery(document).ready( function() {
                    jQuery( 'select[name=\"_status\"]' ).append( '<option value=\"" . commonsbooking_sanitizeHTML( $this->name ) . '">' . commonsbooking_sanitizeHTML( $this->label ) . "</option>' );
                });
            </script>";
	}
}
