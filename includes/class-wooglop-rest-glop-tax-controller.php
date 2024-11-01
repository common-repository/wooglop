<?php
/**
 * REST API: Wooglop_REST_Glop_Tax_Controller class
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
class Wooglop_REST_Glop_Tax_Controller extends WP_REST_Controller {

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
	protected $rest_base = 'impuestos';
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
	protected $taxes;

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
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_taxes' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
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
			return false;
		}

		return true;
	}

	/**
	 * La key que me viene tiene que ser igual a
	 * MD5( CONCAT( privateKeyInBothSides + firstTaxNameInArrayTaxes ) )
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	private function get_cipher( $request ) {
		$products = $this->get_taxes( $request );
		$key      = $request->get_param( 'key' );

		if ( 0 === count( $products ) ) {
			return false;
		}

		$ref1 = $products[0]['nombre'];
		return md5( $this->key . $ref1 ) === $key;
	}

	/**
	 * Get taxes.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return mixed
	 */
	private function get_taxes( $request ) {
		return $request->get_param( 'impuestos' );
	}

	/**
	 * Crear impuestos
	 *
	 * @since 1.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_taxes( $request ) {
		$this->log_request( $request );
		try {
			$taxes = $this->get_taxes( $request );
			// Eliminar todos los impuestos existentes.
			$this->delete_all_taxes();
			// Crear todos los impuestos que me dan.
			$taxes_render = array();
			foreach ( $taxes as $tax ) {
				$taxes_render[] = $this->create_single_tax( $tax );
			}

			$response = rest_ensure_response( $taxes_render );
			$response->set_status( 200 );
			return $response;
		} catch ( Wooglop_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		} catch ( WC_REST_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Eliminar todas las tasas.
	 */
	private function delete_all_taxes() {
		$classes = WC_Tax::get_tax_classes();
		foreach ( $classes as $class ) {
			$this->delete_single_tax(
				array(
					'slug' => sanitize_title( $class ),
				)
			);
		}
	}

	/**
	 * Crea una tasa.
	 *
	 * @param array $tax Tax.
	 *
	 * @return array|WP_Error
	 */
	private function create_single_tax( $tax ) {
		$exists    = false;
		$classes   = WC_Tax::get_tax_classes();
		$nombre    = $tax['nombre'];
		$tax_class = array(
			'slug' => sanitize_title( $nombre ),
			'name' => $nombre,
		);

		// Check if class exists.
		foreach ( $classes as $key => $class ) {
			if ( sanitize_title( $class ) === $tax_class['slug'] ) {
				$exists = true;
				break;
			}
		}

		// Return error if tax class already exists.
		if ( $exists ) {
			return new WP_Error(
				'woocommerce_rest_tax_class_exists',
				__( 'Cannot create existing resource.', 'wooglop' ),
				array( 'status' => 400 )
			);
		}

		// Add the new class.
		$classes[] = $nombre;

//		update_option( 'woocommerce_tax_classes', implode( "\n", $classes ) );
		try {
			WC_Tax::create_tax_class( $nombre );
		}catch (\Exception $e){}
		// Crear rate.
		$rate = $this->create_rate(
			array(
				'rate'  => $tax['porcentaje'],
				'name'  => $tax['nombre'],
				'class' => $tax_class['slug'],
			)
		);

		return $rate;
	}

	/**
	 * Crear un rate.
	 *
	 * @param array $rate Rate.
	 *
	 * @return array|object
	 */
	private function create_rate( $rate ) {
		$id     = absint( isset( $rate['id'] ) ? $rate['id'] : 0 );
		$data   = array();
		$fields = array(
			'tax_rate_country',
			'tax_rate_state',
			'tax_rate',
			'tax_rate_name',
			'tax_rate_priority',
			'tax_rate_compound',
			'tax_rate_shipping',
			'tax_rate_order',
			'tax_rate_class',
		);

		foreach ( $fields as $field ) {
			// Keys via API differ from the stored names returned by _get_tax_rate.
			$key = 'tax_rate' === $field ? 'rate' : str_replace( 'tax_rate_', '', $field );

			// Remove data that was not posted.
			if ( ! isset( $rate[ $key ] ) ) {
				continue;
			}

			// Add to data array.
			switch ( $key ) {
				case 'tax_rate_priority':
				case 'tax_rate_compound':
				case 'tax_rate_shipping':
				case 'tax_rate_order':
					$data[ $field ] = absint( $rate[ $key ] );
					break;
				case 'tax_rate_class':
					$data[ $field ] = 'standard' !== $rate['tax_rate_class'] ? $rate['tax_rate_class'] : '';
					break;
				default:
					$data[ $field ] = wc_clean( $rate[ $key ] );
					break;
			}
		}

		if ( $id ) {
			WC_Tax::_update_tax_rate( $id, $data );
		} else {
			$id = WC_Tax::_insert_tax_rate( $data );
		}

		// Add locales.
		if ( ! empty( $request['postcode'] ) ) {
			WC_Tax::_update_tax_rate_postcodes( $id, wc_clean( $request['postcode'] ) );
		}
		if ( ! empty( $request['city'] ) ) {
			WC_Tax::_update_tax_rate_cities( $id, wc_clean( $request['city'] ) );
		}

		return WC_Tax::_get_tax_rate( $id, OBJECT );
	}

	/**
	 * Elimina un tax.
	 *
	 * @param array $tax Tax.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	private function delete_single_tax( $tax ) {
		global $wpdb;

		$tax_class = array(
			'slug' => sanitize_title( $tax['slug'] ),
			'name' => '',
		);

		$classes = WC_Tax::get_tax_classes();

		foreach ( $classes as $key => $class ) {
			if ( sanitize_title( $class ) === $tax_class['slug'] ) {
				$tax_class['name'] = $class;
				unset( $classes[ $key ] );
				$deleted = true;
				break;
			}
		}
		WC_Tax::delete_tax_class_by('slug', $tax['slug'] );
//		update_option( 'woocommerce_tax_classes', implode( "\n", $classes ) );

		// Delete tax rate locations locations from the selected class.
		// phpcs:disable
		$wpdb->query( $wpdb->prepare( "
			DELETE locations.*
			FROM {$wpdb->prefix}woocommerce_tax_rate_locations AS locations
			INNER JOIN
				{$wpdb->prefix}woocommerce_tax_rates AS rates
				ON rates.tax_rate_id = locations.tax_rate_id
			WHERE rates.tax_rate_class = '%s'
		", $tax_class['slug'] ) );

		// Delete tax rates in the selected class.
		$wpdb->delete( $wpdb->prefix . 'woocommerce_tax_rates', array( 'tax_rate_class' => $tax_class['slug'] ), array( '%s' ) );

		return true;
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
