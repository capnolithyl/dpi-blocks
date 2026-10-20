<?php
/**
 * Secure, progressively enhanced public staff contact form processing.
 *
 * The theme controls the form markup; this reusable service owns validation,
 * recipient resolution, anti-spam and delivery.
 *
 * @package DPI_Blocks
 */

declare(strict_types=1);

namespace DPI\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class StaffContact {
	/** Register the same endpoint for signed-in and anonymous visitors. */
	public function register(): void {
		add_action( 'admin_post_dpi_staff_contact', array( $this, 'submit' ) );
		add_action( 'admin_post_nopriv_dpi_staff_contact', array( $this, 'submit' ) );
	}

	/** Process and redirect without putting submitted personal data in the URL. */
	public function submit(): void {
		$back = get_post_type_archive_link( 'staff' );
		$back = $back ?: home_url( '/staff/' );

		$finish = static function ( string $status ) use ( $back ): void {
			wp_safe_redirect( add_query_arg( 'staff_message', $status, $back ) . '#staff-contact', 303 );
			exit;
		};

		if ( ! ContentTypes::owns( 'staff' ) || 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			$finish( 'error' );
		}

		$nonce = sanitize_text_field( wp_unslash( (string) ( $_POST['dpi_staff_nonce'] ?? '' ) ) );
		if ( ! wp_verify_nonce( $nonce, 'dpi_staff_contact' ) ) {
			$finish( 'error' );
		}

		// Bots tend to fill hidden fields, whereas real visitors leave them blank.
		if ( '' !== trim( (string) ( $_POST['company_website'] ?? '' ) ) ) {
			$finish( 'sent' );
		}

		$staff_id = absint( $_POST['staff_member'] ?? 0 );
		$name     = sanitize_text_field( wp_unslash( (string) ( $_POST['sender_name'] ?? '' ) ) );
		$email    = sanitize_email( wp_unslash( (string) ( $_POST['sender_email'] ?? '' ) ) );
		$phone    = sanitize_text_field( wp_unslash( (string) ( $_POST['sender_phone'] ?? '' ) ) );
		$message  = sanitize_textarea_field( wp_unslash( (string) ( $_POST['sender_message'] ?? '' ) ) );

		if ( ! $staff_id || 'staff' !== get_post_type( $staff_id ) || 'publish' !== get_post_status( $staff_id )
			|| '' === $name || strlen( $name ) > 120 || ! is_email( $email )
			|| strlen( $phone ) > 60 || '' === $message || strlen( $message ) > 5000
		) {
			$finish( 'error' );
		}

		// Limit submissions per visitor. Never store the submitted details here.
		$visitor = (string) ( $_SERVER['REMOTE_ADDR'] ?? '' );
		$key     = 'dpi_staff_contact_' . md5( wp_salt( 'nonce' ) . $visitor );
		if ( get_transient( $key ) ) {
			$finish( 'rate' );
		}

		$recipient = sanitize_email( dpi_blocks_get_staff_field( $staff_id, 'email' ) );
		if ( ! is_email( $recipient ) ) {
			$recipient = sanitize_email( (string) get_option( 'admin_email', '' ) );
		}
		if ( ! is_email( $recipient ) ) {
			$finish( 'error' );
		}

		$subject = sprintf( __( 'Staff inquiry for %s', 'dpi-blocks' ), get_the_title( $staff_id ) );
		$body    = sprintf(
			"Staff member: %s\nFrom: %s\nEmail: %s\nPhone: %s\n\nMessage:\n%s",
			get_the_title( $staff_id ),
			$name,
			$email,
			$phone,
			$message
		);
		$sent = wp_mail( $recipient, $subject, $body, array( 'Reply-To: ' . $email ) );
		if ( ! $sent ) {
			$finish( 'error' );
		}

		set_transient( $key, 1, MINUTE_IN_SECONDS );
		$finish( 'sent' );
	}
}
