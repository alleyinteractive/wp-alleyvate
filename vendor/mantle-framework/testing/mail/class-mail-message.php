<?php
/**
 * Mail_Message class file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Mail;

use function Mantle\Support\Helpers\collect;

/**
 * Mail Message Record
 */
class Mail_Message {
	/**
	 * Constructor.
	 *
	 * @param array  $to      The recipient of the email.
	 * @param array  $cc      The CC recipient of the email.
	 * @param array  $bcc     The BCC recipient of the email.
	 * @param string $subject The subject of the email.
	 * @param string $body    The body of the email.
	 * @param string $header  The header of the email.
	 */
	public function __construct(
		public readonly array $to,
		public readonly array $cc,
		public readonly array $bcc,
		public readonly string $subject,
		public readonly string $body,
		public readonly string $header
	) {}

	/**
	 * Check if the email was sent to the given recipient.
	 *
	 * @param string $address The email address to check for.
	 */
	public function sent_to( string $address ): bool {
		return collect( $this->to )->pluck( 0 )->contains( $address );
	}
}
