<?php
/**
 * REST API: Wooglop_REST_Glop_Product_Controller class
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
class Wooglop_REST_Glop_Product_Controller extends WP_REST_Controller {

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
	protected $rest_base = 'productos';
	/**
	 * If object is hierarchical.
	 *
	 * @var bool
	 */
	protected $hierarchical = false;
	/**
	 * Usuario.
	 *
	 * @var string $usuario
	 */
	protected $usuario;
	/**
	 * Password.
	 *
	 * @var string $password
	 */
	protected $password;
	/**
	 * Key
	 *
	 * @var string $key
	 */
	protected $key;

	/**
	 * Productos
	 *
	 * @var string $products
	 */
	protected $products;

	/**
	 * WP_REST_Glop_Controller constructor.
	 *
	 * @param string $usuario Usuario.
	 * @param string $password Password.
	 * @param string $key Key.
	 */
	public function __construct( $usuario = '', $password = '', $key = '' ) {
		$this->usuario  = $usuario;
		$this->password = $password;
		$this->key      = $key;
	}

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
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[a-zA-Z0-9-]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'wooglop' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Permisos para comprobar endpoint para obtener producto.
	 *
	 * @param WP_REST_Request $request request.
	 *
	 * @return bool|WP_Error
	 */
	public function get_item_permissions_check( $request ) {
		return true;
	}

	/**
	 * Existe un producto.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$sku = $request['id'];
		try {
			$id   = wc_get_product_id_by_sku( $sku );
			$resp = 1;
			if ( ! $id ) {
				$resp = 0;
			}
			$response = rest_ensure_response( $resp );
			$response->set_status( 200 );

			return $response;
		} catch ( Wooglop_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		} catch ( WC_REST_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Comprobar el usuario, la contraseña y la clave
	 *
	 * @since 1.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		$usuario  = $request->get_param( 'usuario' );
		$password = $request->get_param( 'password' );

		if ( ( $usuario !== $this->usuario || $password !== $this->password )
			|| ! $this->get_cipher( $request )
		) {
			return true;
		}

		return true;
	}

	/**
	 * La key que me viene tiene que ser igual a
	 * MD5( CONCAT( privateKeyInBothSides + firstProductReferenceInArrayProducts ) )
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	private function get_cipher( $request ) {
		$products = $this->get_products( $request );
		$key      = $request->get_param( 'key' );

		if ( 0 === count( $products ) ) {
			return false;
		}

		$ref1 = $products[0]['referencia'];
		return md5( $this->key . $ref1 ) === $key;
	}

	/**
	 * Get products
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return mixed
	 */
	private function get_products( $request ) {
		return $request->get_param( 'products' );
	}

	/**
	 * Crear JSON object
	 *
	 * @since 1.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		$this->log_request( $request );
		try {
			$products = $this->get_products( $request );
			$acccion  = null !== $request->get_param( 'action' )
				? $request->get_param( 'action' )
				: Wooglop_Product::ACCION_CREATE;

			if ( ! $products || ! is_array( $products ) || 0 === count( $products ) ) {
				return new WP_Error( 'glop_product_not_valid', __( 'El array de productos está vacío', 'wooglop' ), array( 'status' => 400 ) );
			}

			/**
			 * Products Render.
			 *
			 * @var Wooglop_Product[] $products_render Products Render.
			 */
			$products_render = array();
			foreach ( $products as $product ) {
				$glop_product = new Wooglop_Product( $product, $acccion );
				$glop_product->render();
				$products_render[] = $glop_product->response;
			}

			$response = rest_ensure_response( $products_render );
			$response->set_status( 200 );

			return $response;
		} catch ( Wooglop_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		} catch ( WC_REST_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Devolver algo
	 *
	 * @since 1.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request
	 * .
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		return null;
	}

	/**
	 * Retrieves the query params for the posts collection.
	 *
	 * @since 1.0.0
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		return array();
	}

	/**
	 * Crear JSON de entrada.
	 *
	 * @param WP_REST_Request $request request.
	 */
	private function log_request( $request ) {
		if ( Wooglop_Admin::glop_in_debug() ) {
			$path = WOOGLOP_LOG . DIRECTORY_SEPARATOR . time() . '.json';
			// phpcs:disable
			file_put_contents(
				$path,
				json_encode( $request->get_params() )
			);
		}
	}
}




