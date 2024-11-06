<?php
/**
 * Mock_Mailer class file
 *
 * phpcs:disable WordPress.NamingConventions.ValidVariableName
 *
 * @package Mantle
 */

namespace Mantle\Testing\Mail;

if ( ! file_exists( ABSPATH . '/wp-includes/PHPMailer/PHPMailer.php' ) ) {
	// todo: add link to documentation when it is available.
	echo 'Core PHPMailer file not found. Is WordPress installed properly at ' . ABSPATH . "?\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

require_once ABSPATH . '/wp-includes/PHPMailer/PHPMailer.php';

/**
 * Mock PHPMailer class.
 *
 * @package Mantle
 */
class Mock_Mailer extends \PHPMailer\PHPMailer\PHPMailer {
	/**
	 * Mock sent emails.
	 *
	 * @var Mail_Message[]
	 */
	public array $mock_sent = [];

	/**
	 * Override preSend() method.
	 */
	public function preSend() {
		$this->Encoding = '8bit';

		return parent::preSend();
	}

	/**
	 * Override postSend() so mail isn't actually sent.
	 */
	public function postSend() {
		$this->mock_sent[] = new Mail_Message(
			to: $this->to,
			cc: $this->cc,
			bcc: $this->bcc,
			subject: $this->Subject,
			body: $this->Body,
			header: $this->MIMEHeader . $this->mailHeader
		);

		return true;
	}

	/**
	 * Decorator to return the information for a sent mock.
	 *
	 * @param int $index Optional. Array index of mock_sent value.
	 * @return object|false
	 */
	public function get_sent( $index = 0 ) {
		return $this->mock_sent[ $index ] ?? false;
	}

	/**
	 * Get a recipient for a sent mock.
	 *
	 * @param string $address_type    The type of address for the email such as to, cc or bcc.
	 * @param int    $mock_sent_index Optional. The sent_mock index we want to get the recipient for.
	 * @param int    $recipient_index Optional. The recipient index in the array.
	 * @return bool|object Returns object on success, or false if any of the indices don't exist.
	 */
	public function get_recipient( string $address_type, int $mock_sent_index = 0, $recipient_index = 0 ) {
		$retval = false;
		$mock   = $this->get_sent( $mock_sent_index );

		if ( $mock ) {
			if ( isset( $mock->{$address_type}[ $recipient_index ] ) ) {
				$address_index  = $mock->{$address_type}[ $recipient_index ];
				$recipient_data = [
					'address' => ( isset( $address_index[0] ) && ! empty( $address_index[0] ) ) ? $address_index[0] : 'No address set',
					'name'    => ( isset( $address_index[1] ) && ! empty( $address_index[1] ) ) ? $address_index[1] : 'No name set',
				];

				$retval = (object) $recipient_data;
			}
		}

		return $retval;
	}
}
