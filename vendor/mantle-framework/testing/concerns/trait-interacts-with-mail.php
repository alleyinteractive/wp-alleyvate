<?php
/**
 * Interacts_With_Mail trait file.
 *
 * @package Mantle
 *
 * @phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 */

namespace Mantle\Testing\Concerns;

use Mantle\Support\Collection;
use Mantle\Testing\Mail\Mail_Message;
use Mantle\Testing\Mail\Mock_Mailer;

use function Mantle\Support\Helpers\collect;

/**
 * Concern for interacting with the WordPress wp_mail() function.
 *
 * @mixin \Mantle\Testing\Test_Case
 */
trait Interacts_With_Mail {
	/**
	 * Setup the trait and replace the global phpmailer instance with a mock instance.
	 */
	public function interacts_with_mail_set_up(): void {
		reset_phpmailer_instance();
	}

	/**
	 * Assert that an email was sent to the given recipient.
	 *
	 * @param (callable(\Mantle\Testing\Mail\Mail_Message): bool)|string|null $address_or_callback The email address to check for, or a callback to perform custom assertions.
	 */
	public function assertMailSent( string|callable|null $address_or_callback = null ): void {
		$mailer = tests_retrieve_phpmailer_instance();

		if ( ! ( $mailer instanceof Mock_Mailer ) ) {
			$this->fail( 'Mail instance is not a MockPHPMailer instance.' );
		}

		if ( is_null( $address_or_callback ) ) {
			$this->assertNotEmpty( $mailer->mock_sent, 'No emails were sent.' );
			return;
		}

		$this->assertNotEmpty(
			$this->getSentMail( $address_or_callback ),
			is_string( $address_or_callback ) ? "No email was sent to [{$address_or_callback}]." : 'No email was sent matching the given callback function.'
		);
	}

	/**
	 * Assert that an email was not sent to the given recipient.
	 *
	 * @param (callable(\Mantle\Testing\Mail\Mail_Message): bool)|string|null $address_or_callback The email address to check for, or a callback to perform custom assertions.
	 */
	public function assertMailNotSent( string|callable|null $address_or_callback = null ): void {
		$mailer = tests_retrieve_phpmailer_instance();

		if ( ! ( $mailer instanceof Mock_Mailer ) ) {
			$this->fail( 'Mail instance is not a MockPHPMailer instance.' );
		}

		if ( is_null( $address_or_callback ) ) {
			$this->assertEmpty( $mailer->mock_sent, 'An email was sent.' );
			return;
		}

		$this->assertEmpty(
			$this->getSentMail( $address_or_callback ),
			is_string( $address_or_callback ) ? "An email was sent to [{$address_or_callback}]." : 'An email was sent matching the given callback function.'
		);
	}

	/**
	 * Assert that a specific number of emails were sent.
	 *
	 * @param int                                                             $expected_count The expected number of emails sent.
	 * @param (callable(\Mantle\Testing\Mail\Mail_Message): bool)|string|null $address_or_callback The email address to check for, or a callback to perform custom assertions.
	 */
	public function assertMailSentCount( int $expected_count, string|callable|null $address_or_callback = null ): void {
		$mailer = tests_retrieve_phpmailer_instance();

		if ( ! ( $mailer instanceof Mock_Mailer ) ) {
			$this->fail( 'Mail instance is not a MockPHPMailer instance.' );
		}

		if ( is_null( $address_or_callback ) ) {
			$actual = count( $mailer->mock_sent );
			$this->assertCount( $expected_count, $mailer->mock_sent, "Expected {$expected_count} emails to be sent, but only {$actual} were sent." );
			return;
		}

		$sent_mail = $this->getSentMail( $address_or_callback );
		$count     = count( $sent_mail );

		$this->assertCount( $expected_count, $sent_mail, "Expected {$expected_count} emails to be sent, but only {$count} were sent." );
	}

	/**
	 * Retrieve the sent mail for a given to address or callback function that
	 * performs a match against sent mail.
	 *
	 * @param (callable(\Mantle\Testing\Mail\Mail_Message): bool)|string $address_or_callback The email address to check for, or a callback to perform custom assertions.
	 * @return Collection<int, \Mantle\Testing\Mail\Mail_Message>
	 */
	protected function getSentMail( string|callable $address_or_callback = null ): Collection {
		$mailer = tests_retrieve_phpmailer_instance();

		if ( ! ( $mailer instanceof Mock_Mailer ) ) {
			$this->fail( 'Mail instance is not a MockPHPMailer instance.' );
		}

		return collect( $mailer->mock_sent )->filter(
			function ( Mail_Message $message ) use ( $address_or_callback ) {
				if ( is_string( $address_or_callback ) ) {
					return $message->sent_to( $address_or_callback );
				}

				return $address_or_callback( $message );
			}
		);
	}
}
