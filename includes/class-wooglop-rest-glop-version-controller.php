<?php
/**
 * REST API: Wooglop_REST_Glop_Version_Controller class
 *
 * @package Glop
 * @subpackage wooglop
 * @since 1.0.0
 */

/**
 * Core class to GLOP via the REST API.
 *
 * @since 1.0.0
 *
 * @see WP_REST_Controller
 */
class Wooglop_REST_Glop_Version_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'glop-api/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'version';
	/**
	 * If object is hierarchical.
	 *
	 * @var bool
	 */
	protected $hierarchical = false;

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since 1.0.0
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				'args'   => array(),
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_version' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Obtener la versión del plugin.
	 */
	public function get_version() {
		if ( ! defined( 'get_plugin_data' ) ) {
			/** WordPress Administration API */
			require_once ABSPATH . 'wp-admin/includes/admin.php';
		}
		$glop_updater_path = WOOGLOP_PLUGIN_PATH . '/wooglop.php';
		$glop_updater      = get_plugin_data( $glop_updater_path );
		return array( 'Version' => $glop_updater['Version'] );
	}
}
