<?php
/**
 * Glop Exception
 *
 * @author   Daniel Ruiz
 * @category Admin
 * @package  GLOP/wooglop
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Wooglop_Exception
 */
class Wooglop_Exception extends Exception {

	/**
	 * Error code.
	 *
	 * @var string sanitized error code.
	 */
	protected $error_code;

	/**
	 * Setup exception, requires 3 params.
	 *
	 * @since 1.0
	 *
	 * @param string $error_code error code.
	 * @param string $error_message user-friendly translated error message.
	 * @param int    $http_status_code HTTP status code to respond with.
	 */
	public function __construct( $error_code, $error_message, $http_status_code ) {
		$this->error_code = $error_code;
		parent::__construct( $error_message, $http_status_code );
	}

	/**
	 * Returns the error code
	 *
	 * @since  1.0
	 * @return string
	 */
	public function getErrorCode() {
		return $this->error_code;
	}
}
