<?php
/**
 * Glop Updater
 *
 * Handles GLOP-API endpoint requests.
 *
 * @author   Daniel Ruiz
 * @category API
 * @package  GLOP/wooglop
 * @since 2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Glop_Updater
 */
class Wooglop_Updater {

	/**
	 * Usuario
	 *
	 * @var string
	 */
	protected $usuario;

	/**
	 * Password
	 *
	 * @var string
	 */
	protected $password;

	/**
	 * Key
	 *
	 * @var string
	 */
	protected $key;

	/**
	 * Glop_Updater constructor.
	 *
	 * @param string $usuario Usuario.
	 * @param string $password Password.
	 * @param string $key Key.
	 */
	public function __construct( $usuario = '', $password = '', $key = '' ) {
		$this->usuario  = $usuario;
		$this->password = $password;
		$this->key      = $key;

		$this->init_ws_glop();
	}

	/**
	 * Initi Ws Glop.
	 */
	private function init_ws_glop() {
		/*
		* FILTROS
		*/
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );

		/**
		 * ACTIONS
		 */
		// Register API endpoints.
		add_action( 'init', array( $this, 'add_endpoint' ), 0 );

		// Handle glop-api endpoint requests.
		add_action( 'parse_request', array( $this, 'handle_api_requests' ), 0 );

		// WP REST API.
		$this->rest_api_init();
	}

	/**
	 * Add new query vars.
	 *
	 * @since  1.0
	 * @param  array $vars Vars.
	 * @return string[]
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'glop-api-version';
		$vars[] = 'glop-api-route';
		$vars[] = 'glop-api';
		return $vars;
	}

	/**
	 * Add new endpoints.
	 *
	 * @since 1.0
	 */
	public static function add_endpoint() {
		// REST API, deprecated since 2.6.0.
		add_rewrite_rule( '^glop-api/v([1-3]{1})/?$', 'index.php?glop-api-version=$matches[1]&glop-api-route=/', 'top' );
		add_rewrite_rule( '^glop-api/v([1-3]{1})(.*)?', 'index.php?glop-api-version=$matches[1]&glop-api-route=$matches[2]', 'top' );
		add_rewrite_endpoint( 'glop-api', EP_ALL );
	}

	/**
	 * Init WP REST API.
	 *
	 * @since 2.6.0
	 */
	private function rest_api_init() {
		// REST API was included starting WordPress 4.4.
		if ( ! class_exists( 'WP_REST_Server' ) ) {
			return;
		}

		// Init REST API routes.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 2.6.0
	 */
	public function register_rest_routes() {

		$controllers = array(
			'Wooglop_REST_Glop_Product_Controller',
			'Wooglop_REST_Glop_Version_Controller',
			'Wooglop_REST_Glop_Order_Controller',
			'Wooglop_REST_Glop_Tax_Controller',
		);

		foreach ( $controllers as $controller ) {
			$this->$controller = new $controller( $this->usuario, $this->password, $this->key );
			$this->$controller->register_routes();
		}
	}

	/**
	 * API request - Trigger any API requests.
	 *
	 * @since   1.0
	 * @version 1.0
	 */
	public function handle_api_requests() {
		global $wp;
		if (
			isset( $_GET['action'], $_GET['security'] ) &&
			wp_verify_nonce( sanitize_key( $_GET['security'] ), sanitize_key( $_GET['action'] ) ) &&
			array_key_exists( 'glop-api', $_GET ) && ! empty( $_GET['glop-api'] )
		) {
			$glop_api = wc_clean( wp_unslash( $_GET['glop-api'] ) );
			if ( ! empty( $glop_api ) ) {
				$wp->query_vars['glop-api'] = wp_unslash( $glop_api );
			}

			// glop-api endpoint requests.
			if ( ! empty( $wp->query_vars['glop-api'] ) ) {

				// Buffer, we won't want any output here.
				ob_start();

				// No cache headers.
				$this->glop_nocache_headers();

				// Clean the API request.
				$api_request = strtolower( $this->glop_clean( $wp->query_vars['glop-api'] ) );

				// Trigger generic action before request hook.
				do_action( 'wooglop_api_request', $api_request );

				// Is there actually something hooked into this API request? If not trigger 400 - Bad request.
				status_header( has_action( 'glop_api_' . $api_request ) ? 200 : 400 );

				// Trigger an action which plugins can hook into to fulfill the request.
				do_action( 'wooglop_api_' . $api_request );

				// Done, clear buffer and exit.
				ob_end_clean();
				die( '-1' );
			}
		}
	}

	/**
	 * Glop Clean.
	 *
	 * @param string $var Var.
	 *
	 * @return array|string
	 */
	public function glop_clean( $var ) {
		if ( is_array( $var ) ) {
			return array_map( 'wc_clean', $var );
		} else {
			return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
		}
	}

	/**
	 * Glop No Cache Headers
	 */
	public function glop_nocache_headers() {
		$this->glop_maybe_define_constant( 'DONOTCACHEPAGE', true );
		$this->glop_maybe_define_constant( 'DONOTCACHEOBJECT', true );
		$this->glop_maybe_define_constant( 'DONOTCACHEDB', true );
		nocache_headers();
	}

	/**
	 * Glop Maybe define Constant
	 *
	 * @param string $name Name.
	 * @param string $value Value.
	 */
	public function glop_maybe_define_constant( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}
}
