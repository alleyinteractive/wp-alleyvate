<?php
/**
 * WP_Http class file
 *
 * @package wp-concurrent-remote-requests
 */

namespace Alley\WP\Concurrent_Remote_Requests;

use Requests_Exception;
use Requests_Proxy_HTTP;
use Requests_Response;
use Requests;
use WP_Error;
use WP_HTTP_Proxy;
use WP_HTTP_Requests_Hooks;
use WP_HTTP_Requests_Response;

/**
 * Example Plugin
 */
class WP_Http extends \WP_Http {
	/**
	 * Send an HTTP request to a URI.
	 *
	 * Please note: The only URI that are supported in the HTTP Transport implementation
	 * are the HTTP and HTTPS protocols.
	 *
	 * @since 2.7.0
	 *
	 * @param string       $url  The request URL.
	 * @param string|array $args {
	 *     Optional. Array or string of HTTP request arguments.
	 *
	 *     @type string       $method              Request method. Accepts 'GET', 'POST', 'HEAD', 'PUT', 'DELETE',
	 *                                             'TRACE', 'OPTIONS', or 'PATCH'.
	 *                                             Some transports technically allow others, but should not be
	 *                                             assumed. Default 'GET'.
	 *     @type float        $timeout             How long the connection should stay open in seconds. Default 5.
	 *     @type int          $redirection         Number of allowed redirects. Not supported by all transports.
	 *                                             Default 5.
	 *     @type string       $httpversion         Version of the HTTP protocol to use. Accepts '1.0' and '1.1'.
	 *                                             Default '1.0'.
	 *     @type string       $user-agent          User-agent value sent.
	 *                                             Default 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ).
	 *     @type bool         $reject_unsafe_urls  Whether to pass URLs through wp_http_validate_url().
	 *                                             Default false.
	 *     @type bool         $blocking            Whether the calling code requires the result of the request.
	 *                                             If set to false, the request will be sent to the remote server,
	 *                                             and processing returned to the calling code immediately, the caller
	 *                                             will know if the request succeeded or failed, but will not receive
	 *                                             any response from the remote server. Default true.
	 *     @type string|array $headers             Array or string of headers to send with the request.
	 *                                             Default empty array.
	 *     @type array        $cookies             List of cookies to send with the request. Default empty array.
	 *     @type string|array $body                Body to send with the request. Default null.
	 *     @type bool         $compress            Whether to compress the $body when sending the request.
	 *                                             Default false.
	 *     @type bool         $decompress          Whether to decompress a compressed response. If set to false and
	 *                                             compressed content is returned in the response anyway, it will
	 *                                             need to be separately decompressed. Default true.
	 *     @type bool         $sslverify           Whether to verify SSL for the request. Default true.
	 *     @type string       $sslcertificates     Absolute path to an SSL certificate .crt file.
	 *                                             Default ABSPATH . WPINC . '/certificates/ca-bundle.crt'.
	 *     @type bool         $stream              Whether to stream to a file. If set to true and no filename was
	 *                                             given, it will be dropped it in the WP temp dir and its name will
	 *                                             be set using the basename of the URL. Default false.
	 *     @type string       $filename            Filename of the file to write to when streaming. $stream must be
	 *                                             set to true. Default null.
	 *     @type int          $limit_response_size Size in bytes to limit the response to. Default null.
	 *
	 * }
	 * @return array|WP_Error Array or array of arrays containing 'headers', 'body', 'response', 'cookies', 'filename'.
	 *                        A WP_Error instance upon error.
	 */
	public function request( $url, $args = array() ) {
		if ( is_array( $url ) ) {
			$pending_requests = array();
			$responses        = array();

			foreach ( $url as $index => $request ) {
				// Support an array of string URLs.
				if ( ! is_array( $request ) ) {
					$request = array( $request, array() );
				}

				$request = $this->format_request( $request[0], isset( $request[1] ) ? $request[1] : array() );

				// Handle an error in the pre-request step. Also allow the request to be
				// circumvented if the response was short-circuited.
				if ( is_wp_error( $request ) ) {
					$responses[ $index ] = $request;
					continue;
				} elseif ( isset( $request['response'] ) ) {
					$responses[ $index ] = $request['response'];
					continue;
				}

				$pending_requests[ $index ] = $request;
			}

			if ( ! empty( $pending_requests ) ) {
				// Avoid issues where mbstring.func_overload is enabled.
				mbstring_binary_safe_encoding();

				try {
					$raw_responses = Requests::request_multiple( $pending_requests );
				} catch ( Requests_Exception $e ) {
					$raw_responses = new WP_Error( 'http_request_failed', $e->getMessage() );
				}

				reset_mbstring_encoding();

				if ( is_wp_error( $raw_responses ) ) {
					return $raw_responses;
				}

				foreach ( $pending_requests as $index => $request ) {
					$responses[ $index ] = $this->format_response(
						$raw_responses[ $index ],
						$request['args'],
						$request['url']
					);
				}
			}

			return $responses;
		}

		$formatted = $this->format_request( $url, $args );

		// Handle an error in the pre-request step. Also allow the request to be
		// circumvented if the response was short-circuited.
		if ( is_wp_error( $formatted ) ) {
			return $formatted;
		} elseif ( isset( $formatted['response'] ) ) {
			return $formatted['response'];
		}

		// Avoid issues where mbstring.func_overload is enabled.
		mbstring_binary_safe_encoding();

		try {
			$response = Requests::request(
				$formatted['url'],
				$formatted['headers'],
				$formatted['data'],
				$formatted['type'],
				$formatted['options']
			);
		} catch ( Requests_Exception $e ) {
			$response = new WP_Error( 'http_request_failed', $e->getMessage() );
		}

		reset_mbstring_encoding();

		return $this->format_response( $response, $formatted['args'], $formatted['url'] );
	}

	/**
	 * Format a request to be passed to Requests.
	 *
	 * @param string $url  URL to request.
	 * @param array  $args Arguments to send to request.
	 * @return array {
	 *     Array of formatted arguments for the request.
	 *
	 *     @type string $url  URL to request.
	 *     @type array  $headers Array of headers for the request.
	 *     @type string $data Data to send with the request.
	 *     @type string $type The type of request to make.
	 *     @type array  $options Array of options for the request.
	 * }
	 */
	protected function format_request( $url, $args ) {
		$defaults = array(
			'method'              => 'GET',
			/**
			 * Filters the timeout value for an HTTP request.
			 *
			 * @since 2.7.0
			 * @since 5.1.0 The `$url` parameter was added.
			 *
			 * @param float  $timeout_value Time in seconds until a request times out. Default 5.
			 * @param string $url           The request URL.
			 */
			'timeout'             => apply_filters( 'http_request_timeout', 5, $url ),
			/**
			 * Filters the number of redirects allowed during an HTTP request.
			 *
			 * @since 2.7.0
			 * @since 5.1.0 The `$url` parameter was added.
			 *
			 * @param int    $redirect_count Number of redirects allowed. Default 5.
			 * @param string $url            The request URL.
			 */
			'redirection'         => apply_filters( 'http_request_redirection_count', 5, $url ),
			/**
			 * Filters the version of the HTTP protocol used in a request.
			 *
			 * @since 2.7.0
			 * @since 5.1.0 The `$url` parameter was added.
			 *
			 * @param string $version Version of HTTP used. Accepts '1.0' and '1.1'. Default '1.0'.
			 * @param string $url     The request URL.
			 */
			'httpversion'         => apply_filters( 'http_request_version', '1.0', $url ),
			/**
			 * Filters the user agent value sent with an HTTP request.
			 *
			 * @since 2.7.0
			 * @since 5.1.0 The `$url` parameter was added.
			 *
			 * @param string $user_agent WordPress user agent string.
			 * @param string $url        The request URL.
			 */
			'user-agent'          => apply_filters( 'http_headers_useragent', 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ), $url ),
			/**
			 * Filters whether to pass URLs through wp_http_validate_url() in an HTTP request.
			 *
			 * @since 3.6.0
			 * @since 5.1.0 The `$url` parameter was added.
			 *
			 * @param bool   $pass_url Whether to pass URLs through wp_http_validate_url(). Default false.
			 * @param string $url      The request URL.
			 */
			'reject_unsafe_urls'  => apply_filters( 'http_request_reject_unsafe_urls', false, $url ),
			'blocking'            => true,
			'headers'             => array(),
			'cookies'             => array(),
			'body'                => null,
			'compress'            => false,
			'decompress'          => true,
			'sslverify'           => true,
			'sslcertificates'     => ABSPATH . WPINC . '/certificates/ca-bundle.crt',
			'stream'              => false,
			'filename'            => null,
			'limit_response_size' => null,
		);

		// Pre-parse for the HEAD checks.
		$args = wp_parse_args( $args );

		// By default, HEAD requests do not cause redirections.
		if ( isset( $args['method'] ) && 'HEAD' === $args['method'] ) {
			$defaults['redirection'] = 0;
		}

		$parsed_args = wp_parse_args( $args, $defaults );

		/**
		 * Filters the arguments used in an HTTP request.
		 *
		 * @since 2.7.0
		 *
		 * @param array  $parsed_args An array of HTTP request arguments.
		 * @param string $url         The request URL.
		 */
		$parsed_args = apply_filters( 'http_request_args', $parsed_args, $url );

		// The transports decrement this, store a copy of the original value for loop purposes.
		if ( ! isset( $parsed_args['_redirection'] ) ) {
			$parsed_args['_redirection'] = $parsed_args['redirection'];
		}

		/**
		 * Filters the preemptive return value of an HTTP request.
		 *
		 * Returning a non-false value from the filter will short-circuit the HTTP request and return
		 * early with that value. A filter should return one of:
		 *
		 *  - An array containing 'headers', 'body', 'response', 'cookies', and 'filename' elements
		 *  - A WP_Error instance
		 *  - boolean false to avoid short-circuiting the response
		 *
		 * Returning any other value may result in unexpected behaviour.
		 *
		 * @since 2.9.0
		 *
		 * @param false|array|WP_Error $preempt     A preemptive return value of an HTTP request. Default false.
		 * @param array                $parsed_args HTTP request arguments.
		 * @param string               $url         The request URL.
		 */
		$pre = apply_filters( 'pre_http_request', false, $parsed_args, $url );

		if ( false !== $pre ) {
			return array(
				'response' => $pre,
			);
		}

		if ( function_exists( 'wp_kses_bad_protocol' ) ) {
			if ( $parsed_args['reject_unsafe_urls'] ) {
				$url = wp_http_validate_url( $url );
			}
			if ( $url ) {
				$url = wp_kses_bad_protocol( $url, array( 'http', 'https', 'ssl' ) );
			}
		}

		$parsed_url = wp_parse_url( $url );

		if ( empty( $url ) || empty( $parsed_url['scheme'] ) ) {
			$response = new WP_Error( 'http_request_failed', __( 'A valid URL was not provided.' ) );
			/** This action is documented in wp-includes/class-wp-http.php */
			do_action( 'http_api_debug', $response, 'response', 'Requests', $parsed_args, $url );
			return $response;
		}

		if ( $this->block_request( $url ) ) {
			$response = new WP_Error( 'http_request_not_executed', __( 'User has blocked requests through HTTP.' ) );
			/** This action is documented in wp-includes/class-wp-http.php */
			do_action( 'http_api_debug', $response, 'response', 'Requests', $parsed_args, $url );
			return $response;
		}

		// If we are streaming to a file but no filename was given drop it in the WP temp dir
		// and pick its name using the basename of the $url.
		if ( $parsed_args['stream'] ) {
			if ( empty( $parsed_args['filename'] ) ) {
				$parsed_args['filename'] = get_temp_dir() . basename( $url );
			}

			// Force some settings if we are streaming to a file and check for existence
			// and perms of destination directory.
			$parsed_args['blocking'] = true;
			if ( ! wp_is_writable( dirname( $parsed_args['filename'] ) ) ) {
				$response = new WP_Error( 'http_request_failed', __( 'Destination directory for file streaming does not exist or is not writable.' ) );
				/** This action is documented in wp-includes/class-wp-http.php */
				do_action( 'http_api_debug', $response, 'response', 'Requests', $parsed_args, $url );
				return $response;
			}
		}

		if ( is_null( $parsed_args['headers'] ) ) {
			$parsed_args['headers'] = array();
		}

		// WP allows passing in headers as a string, weirdly.
		if ( ! is_array( $parsed_args['headers'] ) ) {
			$processed_headers      = self::processHeaders( $parsed_args['headers'] );
			$parsed_args['headers'] = $processed_headers['headers'];
		}

		// Setup arguments.
		$headers = $parsed_args['headers'];
		$data    = $parsed_args['body'];
		$type    = $parsed_args['method'];
		$options = array(
			'timeout'   => $parsed_args['timeout'],
			'useragent' => $parsed_args['user-agent'],
			'blocking'  => $parsed_args['blocking'],
			'hooks'     => new WP_HTTP_Requests_Hooks( $url, $parsed_args ),
		);

		// Ensure redirects follow browser behaviour.
		$options['hooks']->register( 'requests.before_redirect', array( get_class(), 'browser_redirect_compatibility' ) );

		// Validate redirected URLs.
		if ( function_exists( 'wp_kses_bad_protocol' ) && $parsed_args['reject_unsafe_urls'] ) {
			$options['hooks']->register( 'requests.before_redirect', array( get_class(), 'validate_redirects' ) );
		}

		if ( $parsed_args['stream'] ) {
			$options['filename'] = $parsed_args['filename'];
		}
		if ( empty( $parsed_args['redirection'] ) ) {
			$options['follow_redirects'] = false;
		} else {
			$options['redirects'] = $parsed_args['redirection'];
		}

		// Use byte limit, if we can.
		if ( isset( $parsed_args['limit_response_size'] ) ) {
			$options['max_bytes'] = $parsed_args['limit_response_size'];
		}

		// If we've got cookies, use and convert them to Requests_Cookie.
		if ( ! empty( $parsed_args['cookies'] ) ) {
			$options['cookies'] = self::normalize_cookies( $parsed_args['cookies'] );
		}

		// SSL certificate handling.
		if ( ! $parsed_args['sslverify'] ) {
			$options['verify']     = false;
			$options['verifyname'] = false;
		} else {
			$options['verify'] = $parsed_args['sslcertificates'];
		}

		// All non-GET/HEAD requests should put the arguments in the form body.
		if ( 'HEAD' !== $type && 'GET' !== $type ) {
			$options['data_format'] = 'body';
		}

		/**
		 * Filters whether SSL should be verified for non-local requests.
		 *
		 * @since 2.8.0
		 * @since 5.1.0 The `$url` parameter was added.
		 *
		 * @param bool   $ssl_verify Whether to verify the SSL connection. Default true.
		 * @param string $url        The request URL.
		 */
		$options['verify'] = apply_filters( 'https_ssl_verify', $options['verify'], $url );

		// Check for proxies.
		$proxy = new WP_HTTP_Proxy();
		if ( $proxy->is_enabled() && $proxy->send_through_proxy( $url ) ) {
			$options['proxy'] = new Requests_Proxy_HTTP( $proxy->host() . ':' . $proxy->port() );

			if ( $proxy->use_authentication() ) {
				$options['proxy']->use_authentication = true;
				$options['proxy']->user               = $proxy->username();
				$options['proxy']->pass               = $proxy->password();
			}
		}

		return array(
			'args'    => $parsed_args,
			'data'    => $data,
			'headers' => $headers,
			'options' => $options,
			'type'    => $type,
			'url'     => $url,
		);
	}

	/**
	 * Format a response into the expected shape.
	 *
	 * @param Requests_Response|WP_Error $response Response to format.
	 * @param array                      $args     Request arguments.
	 * @param string                     $url      Request URL.
	 * @return array|WP_Error
	 */
	protected function format_response( $response, $args, $url ) {
		// Convert the response into an array.
		if ( ! is_wp_error( $response ) ) {
			$http_response = new WP_HTTP_Requests_Response( $response, $args['filename'] );
			$response      = $http_response->to_array();

			// Add the original object to the array.
			$response['http_response'] = $http_response;
		}

		/**
		 * Fires after an HTTP API response is received and before the response is returned.
		 *
		 * @since 2.8.0
		 *
		 * @param array|WP_Error $response    HTTP response or WP_Error object.
		 * @param string         $context     Context under which the hook is fired.
		 * @param string         $class       HTTP transport used.
		 * @param array          $parsed_args HTTP request arguments.
		 * @param string         $url         The request URL.
		 */
		do_action( 'http_api_debug', $response, 'response', 'Requests', $args, $url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! $args['blocking'] ) {
			return array(
				'headers'       => array(),
				'body'          => '',
				'response'      => array(
					'code'    => false,
					'message' => false,
				),
				'cookies'       => array(),
				'http_response' => null,
			);
		}

		/**
		 * Filters a successful HTTP API response immediately before the response is returned.
		 *
		 * @since 2.9.0
		 *
		 * @param array  $response    HTTP response.
		 * @param array  $parsed_args HTTP request arguments.
		 * @param string $url         The request URL.
		 */
		return apply_filters( 'http_response', $response, $args, $url );
	}

	/**
	 * Uses the POST HTTP method.
	 *
	 * Used for sending data that is expected to be in the body.
	 *
	 * @since 2.7.0
	 *
	 * @param string       $url  The request URL.
	 * @param string|array $args Optional. Override the defaults.
	 * @return array|WP_Error Array containing 'headers', 'body', 'response', 'cookies', 'filename'.
	 *                        A WP_Error instance upon error.
	 */
	public function post( $url, $args = array() ) {
		return $this->normalize_request_args( $url, $args, 'POST' );
	}

	/**
	 * Uses the GET HTTP method.
	 *
	 * Used for sending data that is expected to be in the body.
	 *
	 * @since 2.7.0
	 *
	 * @param string|array $url  The request URL or array of remote requests that will be run in parallel.
	 * @param string|array $args Optional. Override the defaults.
	 * @return array|WP_Error Array containing 'headers', 'body', 'response', 'cookies', 'filename'.
	 *                        A WP_Error instance upon error.
	 */
	public function get( $url, $args = array() ) {
		return $this->normalize_request_args( $url, $args, 'GET' );
	}

	/**
	 * Uses the HEAD HTTP method.
	 *
	 * Used for sending data that is expected to be in the body.
	 *
	 * @since 2.7.0
	 *
	 * @param string       $url  The request URL.
	 * @param string|array $args Optional. Override the defaults.
	 * @return array|WP_Error Array containing 'headers', 'body', 'response', 'cookies', 'filename'.
	 *                        A WP_Error instance upon error.
	 */
	public function head( $url, $args = array() ) {
		return $this->normalize_request_args( $url, $args, 'HEAD' );
	}

	/**
	 * Normalize the request arguments for a GET, HEAD, or POST request.
	 *
	 * @param string|array $url    The request URL or array of remote requests that will be run in parallel.
	 * @param string|array $args   Request arguments, optional. Override the defaults.
	 * @param string       $method Request method to make.
	 * @return array Array containing 'headers', 'body', 'response', 'cookies', 'filename'.
	 *               A WP_Error instance upon error.
	 */
	protected function normalize_request_args( $url, $args, $method ) {
		$defaults = array( 'method' => $method );

		// Support an array of parallel requests.
		if ( is_array( $url ) ) {
			$parsed_requests = array();

			if ( ! empty( $args ) ) {
				_doing_it_wrong(
					__FUNCTION__,
					esc_html__( 'Arguments passed to the second $args parameter are ignored when $url is an array of parallel requests.' ),
					'6.0.0'
				);
			}

			foreach ( $url as $i => $request ) {
				// Support an array of string URLs.
				if ( ! is_array( $request ) ) {
					$request = array( $request, array() );
				}

				list( $request_url, $request_args ) = $request;

				$parsed_requests[ $i ] = array(
					$request_url,
					wp_parse_args( $request_args, $defaults ),
				);
			}

			return $this->request( $parsed_requests );
		}

		$parsed_args = wp_parse_args( $args, $defaults );

		return $this->request( $url, $parsed_args );
	}
}
