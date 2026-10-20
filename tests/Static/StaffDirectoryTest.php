<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/** Guard the public Staff schema and contact endpoint without a WP database. */
final class StaffDirectoryTest extends TestCase {
	private string $root;

	protected function setUp(): void {
		$this->root = dirname( __DIR__, 2 );
	}

	public function test_staff_has_every_requested_editable_field(): void {
		$schema = json_decode(
			(string) file_get_contents( $this->root . '/acf-json/group_dpi_staff_details.json' ),
			true,
			512,
			JSON_THROW_ON_ERROR
		);
		$this->assertSame( array( 'staff_position', 'staff_department', 'staff_email', 'staff_phone' ), array_column( $schema['fields'], 'name' ) );
		$this->assertSame( 'email', $schema['fields'][2]['type'] );
		$this->assertSame( 1, $schema['fields'][1]['required'], 'Department is required for every staff member.' );
		$this->assertSame( 0, $schema['fields'][0]['required'], 'Position is optional; department can be used as a card subtitle.' );
		$this->assertSame( 'post_type', $schema['location'][0][0]['param'] );
		$this->assertSame( 'staff', $schema['location'][0][0]['value'] );

		$types = (string) file_get_contents( $this->root . '/includes/ContentTypes.php' );
		$this->assertStringContainsString( "'has_archive'", $types );
		$this->assertStringContainsString( "'staff_department' => 'sanitize_text_field'", $types );
		$this->assertMatchesRegularExpression( "/'supports'\\s*=>\\s*array\\( 'title', 'editor', 'excerpt', 'thumbnail'/", $types );

		$helper = (string) file_get_contents( $this->root . '/includes/functions.php' );
		$this->assertStringContainsString( "'position', 'department', 'email', 'phone'", $helper );
	}

	public function test_contact_form_protects_public_submissions(): void {
		$php = (string) file_get_contents( $this->root . '/includes/StaffContact.php' );
		$this->assertStringContainsString( 'admin_post_nopriv_dpi_staff_contact', $php );
		$this->assertStringContainsString( "wp_verify_nonce( \$nonce, 'dpi_staff_contact' )", $php );
		$this->assertStringContainsString( 'get_post_status( $staff_id )', $php );
		$this->assertStringContainsString( 'sanitize_email(', $php );
		$this->assertStringContainsString( 'sanitize_textarea_field(', $php );
		$this->assertStringContainsString( "get_transient( \$key )", $php );
		$this->assertStringContainsString( 'set_transient( $key, 1, MINUTE_IN_SECONDS )', $php );
		$this->assertStringContainsString( 'wp_mail( $recipient, $subject, $body', $php );
		$this->assertStringContainsString( "wp_safe_redirect( add_query_arg( 'staff_message', \$status, \$back ) . '#staff-contact', 303 )", $php );
	}
}
