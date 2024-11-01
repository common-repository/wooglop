<?php
/**
 * REST API: Wooglop_REST_Glop_Order_Controller class
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
class Wooglop_REST_Glop_Order_Controller extends WP_REST_Controller {

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
	protected $rest_base = 'pedidos';
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
					'callback'            => array( $this, 'get_orders' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
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

		if ( ( $usuario !== $this->usuario || $password !== $this->password ) || ! $this->get_cipher( $request )
		) {
			return false;
		}

		return true;
	}

	/**
	 * TODO establecer una forma de proteger este recurso
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool
	 */
	private function get_cipher( $request ) {
		$key  = $request->get_param( 'key' );
		$ref1 = $request->get_param( 'fecha1' );

		if ( ! isset( $ref1 ) ) {
			return false;
		}
		return md5( $this->key . $ref1 ) === $key;
	}

	/**
	 * Fecha1 y Fecha2 deben estar en timestamp
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @throws Wooglop_Exception Excepcion.
	 */
	public function get_orders( $request ) {
		$fecha1 = $request->get_param( 'fecha1' );
		$fecha2 = $request->get_param( 'fecha2' );

		try {
			$fecha1 = new DateTime( "@$fecha1" );
			$fecha2 = new DateTime( "@$fecha2" );

			$filter = array();
			$query  = $this->query_orders(
				array(
					'created_at_min' => $fecha1->format( 'Y-m-d H:i:s' ),
					'created_at_max' => $fecha2->format( 'Y-m-d H:i:s' ),
				)
			);

			$orders = array();

			foreach ( $query->posts as $order_id ) {

				if ( ! $this->is_readable( $order_id ) ) {
					continue;
				}

				$orders[] = current( $this->get_order( $order_id, null, $filter ) );
			}

			foreach ( $orders as &$order ) {
				$d                     = new DateTime( $order['created_at'] );
				$order['created_at']   = $d->format( 'Y' ) !== '1970' ? $d->getTimestamp() : '';
				$d                     = new DateTime( $order['updated_at'] );
				$order['updated_at']   = $d->format( 'Y' ) !== '1970' ? $d->getTimestamp() : '';
				$d                     = new DateTime( $order['completed_at'] );
				$order['completed_at'] = $d->format( 'Y' ) !== '1970' ? $d->getTimestamp() : '';
				// Por producto, añadir el rate aplicado.
				if ( array_key_exists( 'line_items', $order ) ) {
					foreach ( $order['line_items'] as &$line_item ) {

						$t = WC_Tax::find_rates(
							array(
								'country'   => '*',
								'tax_class' => $line_item['tax_class'],
							)
						);
						if ( $t ) {
							$t                = array_values( $t );
							$t                = $t[0];
							$line_item['tax'] = number_format( $t['rate'], 2 );
						}
						$s = null;
						try {
							$s = new WC_Product( $line_item['product_id'] );

						} catch ( Exception $f ) {
							// Queremos seguir adelante.
						}
						try {
							if ( empty( $s ) ) {
								$v              = new WC_Product_Variation( $line_item['product_id'] );
								$product_parent = $v->get_parent_data();
								if ( ! empty( $product_parent['sku'] ) ) {
									$line_item['product_sku'] = $product_parent['sku'];
								} else {
									$line_item['product_sku'] = $line_item['sku'];
								}
							} else {
								$line_item['product_sku'] = $line_item['sku'];
							}
						} catch ( Exception $f ) {
							// Queremos seguir adelante.
						}
						if ( $s && $s->is_type( 'simple' ) ) {
							$line_item['sku'] = '0';
						}
					}
				}
			}

			return array( 'orders' => $orders );

		} catch ( Exception $e ) {
			return new WP_Error(
				'glop_api_pedidos_error_fechas',
				/* translators: %1$s: accion */
				__( 'Error en las fechas', 'wooglop' ),
				array( 'status' => 400 )
			);
		}
	}


	/**
	 * Get the order for the given ID
	 *
	 * @since 1.0
	 * @param int   $id the order ID.
	 * @param array $fields fields.
	 * @param array $filter filters.
	 * @return array|WP_Error
	 */
	public function get_order( $id, $fields = null, $filter = array() ) {

		// Get the decimal precession.
		$dp         = ( isset( $filter['dp'] ) ? intval( $filter['dp'] ) : 2 );
		$order      = wc_get_order( $id );
		$order_data = array(
			'id'                        => $order->get_id(),
			'order_number'              => $order->get_order_number(),
			'created_at'                => $this->format_datetime( $order->get_date_created() ? $order->get_date_created()->getTimestamp() : 0, false, false ), // API gives UTC times.
			'updated_at'                => $this->format_datetime( $order->get_date_modified() ? $order->get_date_modified()->getTimestamp() : 0, false, false ), // API gives UTC times.
			'completed_at'              => $this->format_datetime( $order->get_date_completed() ? $order->get_date_completed()->getTimestamp() : 0, false, false ), // API gives UTC times.
			'status'                    => $order->get_status(),
			'currency'                  => $order->get_currency(),
			'total'                     => wc_format_decimal( $order->get_total(), $dp ),
			'subtotal'                  => wc_format_decimal( $order->get_subtotal(), $dp ),
			'total_line_items_quantity' => $order->get_item_count(),
			'total_tax'                 => wc_format_decimal( $order->get_total_tax(), $dp ),
			'total_shipping'            => wc_format_decimal( $order->get_shipping_total(), $dp ),
			'cart_tax'                  => wc_format_decimal( $order->get_cart_tax(), $dp ),
			'shipping_tax'              => wc_format_decimal( $order->get_shipping_tax(), $dp ),
			'total_discount'            => wc_format_decimal( $order->get_total_discount(), $dp ),
			'shipping_methods'          => $order->get_shipping_method(),
			'payment_details'           => array(
				'method_id'    => $order->get_payment_method(),
				'method_title' => $order->get_payment_method_title(),
				'paid'         => ! is_null( $order->get_date_paid() ),
			),
			'billing_address'           => array(
				'first_name' => $order->get_billing_first_name(),
				'last_name'  => $order->get_billing_last_name(),
				'company'    => $order->get_billing_company(),
				'address_1'  => $order->get_billing_address_1(),
				'address_2'  => $order->get_billing_address_2(),
				'city'       => $order->get_billing_city(),
				'state'      => $order->get_billing_state(),
				'postcode'   => $order->get_billing_postcode(),
				'country'    => $order->get_billing_country(),
				'email'      => $order->get_billing_email(),
				'phone'      => $order->get_billing_phone(),
			),
			'shipping_address'          => array(
				'first_name' => $order->get_shipping_first_name(),
				'last_name'  => $order->get_shipping_last_name(),
				'company'    => $order->get_shipping_company(),
				'address_1'  => $order->get_shipping_address_1(),
				'address_2'  => $order->get_shipping_address_2(),
				'city'       => $order->get_shipping_city(),
				'state'      => $order->get_shipping_state(),
				'postcode'   => $order->get_shipping_postcode(),
				'country'    => $order->get_shipping_country(),
			),
			'note'                      => $order->get_customer_note(),
			'customer_ip'               => $order->get_customer_ip_address(),
			'customer_user_agent'       => $order->get_customer_user_agent(),
			'customer_id'               => $order->get_user_id(),
			'view_order_url'            => $order->get_view_order_url(),
			'line_items'                => array(),
			'shipping_lines'            => array(),
			'tax_lines'                 => array(),
			'fee_lines'                 => array(),
			'coupon_lines'              => array(),
		);

		// add line items.
		foreach ( $order->get_items() as $item_id => $item ) {
			$product    = $item->get_product();
			$hideprefix = ( isset( $filter['all_item_meta'] ) && 'true' === $filter['all_item_meta'] ) ? null : '_';
			$item_meta  = $item->get_formatted_meta_data( $hideprefix );

			foreach ( $item_meta as $key => $values ) {
				$item_meta[ $key ]->label = $values->display_key;
				unset( $item_meta[ $key ]->display_key );
				unset( $item_meta[ $key ]->display_value );
			}

			$order_data['line_items'][] = array(
				'id'           => $item_id,
				'subtotal'     => wc_format_decimal( $order->get_line_subtotal( $item, false, false ), $dp ),
				'subtotal_tax' => wc_format_decimal( $item->get_subtotal_tax(), $dp ),
				'total'        => wc_format_decimal( $order->get_line_total( $item, false, false ), $dp ),
				'total_tax'    => wc_format_decimal( $item->get_total_tax(), $dp ),
				'price'        => wc_format_decimal( $order->get_item_total( $item, false, false ), $dp ),
				'quantity'     => $item->get_quantity(),
				'tax_class'    => $item->get_tax_class(),
				'name'         => $item->get_name(),
				'product_id'   => $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id(),
				'sku'          => is_object( $product ) ? $product->get_sku() : null,
				'meta'         => array_values( $item_meta ),
			);
		}

		// add shipping.
		foreach ( $order->get_shipping_methods() as $shipping_item_id => $shipping_item ) {
			$order_data['shipping_lines'][] = array(
				'id'           => $shipping_item_id,
				'method_id'    => $shipping_item->get_method_id(),
				'method_title' => $shipping_item->get_name(),
				'total'        => wc_format_decimal( $shipping_item->get_total(), $dp ),
			);
		}

		// add taxes.
		foreach ( $order->get_tax_totals() as $tax_code => $tax ) {
			$order_data['tax_lines'][] = array(
				'id'       => $tax->id,
				'rate_id'  => $tax->rate_id,
				'code'     => $tax_code,
				'title'    => $tax->label,
				'total'    => wc_format_decimal( $tax->amount, $dp ),
				'compound' => (bool) $tax->is_compound,
			);
		}

		// add fees.
		foreach ( $order->get_fees() as $fee_item_id => $fee_item ) {
			$order_data['fee_lines'][] = array(
				'id'        => $fee_item_id,
				'title'     => $fee_item->get_name(),
				'tax_class' => $fee_item->get_tax_class(),
				'total'     => wc_format_decimal( $order->get_line_total( $fee_item ), $dp ),
				'total_tax' => wc_format_decimal( $order->get_line_tax( $fee_item ), $dp ),
			);
		}

		// add coupons.
		foreach ( $order->get_items( 'coupon' ) as $coupon_item_id => $coupon_item ) {
			$order_data['coupon_lines'][] = array(
				'id'     => $coupon_item_id,
				'code'   => $coupon_item->get_code(),
				'amount' => wc_format_decimal( $coupon_item->get_discount(), $dp ),
			);
		}

		return array( 'order' => apply_filters( 'woocommerce_api_order_response', $order_data, $order, $fields, null ) );
	}


	/**
	 * Format a unix timestamp or MySQL datetime into an RFC3339 datetime
	 *
	 * @since 2.1
	 * @param int|string $timestamp unix timestamp or MySQL datetime.
	 * @param bool       $convert_to_utc convert.
	 * @param bool       $convert_to_gmt Use GMT timezone.
	 * @return string RFC3339 datetime
	 */
	public function format_datetime( $timestamp, $convert_to_utc = false, $convert_to_gmt = false ) {
		if ( $convert_to_gmt ) {
			if ( is_numeric( $timestamp ) ) {
				$timestamp = date( 'Y-m-d H:i:s', $timestamp );
			}

			$timestamp = get_gmt_from_date( $timestamp );
		}

		if ( $convert_to_utc ) {
			$timezone = new DateTimeZone( wc_timezone_string() );
		} else {
			$timezone = new DateTimeZone( 'UTC' );
		}

		try {

			if ( is_numeric( $timestamp ) ) {
				$date = new DateTime( "@{$timestamp}" );
			} else {
				$date = new DateTime( $timestamp, $timezone );
			}

			// convert to UTC by adjusting the time based on the offset of the site's timezone.
			if ( $convert_to_utc ) {
				$date->modify( -1 * $date->getOffset() . ' seconds' );
			}
		} catch ( Exception $e ) {

			$date = new DateTime( '@0' );
		}

		return $date->format( 'Y-m-d\TH:i:s\Z' );
	}

	/**
	 * Permisos por usuario que de momento no usamos.
	 *
	 * @param string $post post.
	 *
	 * @return bool
	 */
	protected function is_readable( $post ) {
		return true;
	}

	/**
	 * Helper method to get order post objects
	 *
	 * @since 2.1
	 * @param array $args request arguments for filtering query.
	 * @return WP_Query
	 */
	protected function query_orders( $args ) {

		// set base query arguments.
		$query_args = array(
			'fields'      => 'ids',
			'post_type'   => 'shop_order',
			'post_status' => array_keys( wc_get_order_statuses() ),
		);

		// add status argument.
		if ( ! empty( $args['status'] ) ) {

			$statuses                  = 'wc-' . str_replace( ',', ',wc-', $args['status'] );
			$statuses                  = explode( ',', $statuses );
			$query_args['post_status'] = $statuses;

			unset( $args['status'] );

		}

		$query_args = $this->merge_query_args( $query_args, $args );

		return new WP_Query( $query_args );
	}

	/**
	 * Add common request arguments to argument list before WP_Query is run
	 *
	 * @since 2.1
	 * @param array $base_args required arguments for the query (e.g. `post_type`, etc).
	 * @param array $request_args arguments provided in the request.
	 * @return array
	 */
	protected function merge_query_args( $base_args, $request_args ) {

		$args = array();

		// date.
		if ( ! empty( $request_args['created_at_min'] ) || ! empty( $request_args['created_at_max'] ) || ! empty( $request_args['updated_at_min'] ) || ! empty( $request_args['updated_at_max'] ) ) {

			$args['date_query'] = array();

			// resources created after specified date.
			if ( ! empty( $request_args['created_at_min'] ) ) {
				$args['date_query'][] = array(
					'column'    => 'post_date_gmt',
					'after'     => $request_args['created_at_min'],
					'inclusive' => true,
				);
			}

			// resources created before specified date.
			if ( ! empty( $request_args['created_at_max'] ) ) {
				$args['date_query'][] = array(
					'column'    => 'post_date_gmt',
					'before'    => $request_args['created_at_max'],
					'inclusive' => true,
				);
			}

			// resources updated after specified date.
			if ( ! empty( $request_args['updated_at_min'] ) ) {
				$args['date_query'][] = array(
					'column'    => 'post_modified_gmt',
					'after'     => $request_args['updated_at_min'],
					'inclusive' => true,
				);
			}

			// resources updated before specified date.
			if ( ! empty( $request_args['updated_at_max'] ) ) {
				$args['date_query'][] = array(
					'column'    => 'post_modified_gmt',
					'before'    => $request_args['updated_at_max'],
					'inclusive' => true,
				);
			}
		}

		// search.
		if ( ! empty( $request_args['q'] ) ) {
			$args['s'] = $request_args['q'];
		}

		// resources per response.
		if ( ! empty( $request_args['limit'] ) ) {
			$args['posts_per_page'] = $request_args['limit'];
		}

		// resource offset.
		if ( ! empty( $request_args['offset'] ) ) {
			$args['offset'] = $request_args['offset'];
		}

		// order (ASC or DESC, ASC by default).
		if ( ! empty( $request_args['order'] ) ) {
			$args['order'] = $request_args['order'];
		}

		// orderby.
		if ( ! empty( $request_args['orderby'] ) ) {
			$args['orderby'] = $request_args['orderby'];

			// allow sorting by meta value.
			if ( ! empty( $request_args['orderby_meta_key'] ) ) {
				$args['meta_key'] = $request_args['orderby_meta_key'];
			}
		}

		// allow post status change.
		if ( ! empty( $request_args['post_status'] ) ) {
			$args['post_status'] = $request_args['post_status'];
			unset( $request_args['post_status'] );
		}

		// filter by a list of post id.
		if ( ! empty( $request_args['in'] ) ) {
			$args['post__in'] = explode( ',', $request_args['in'] );
			unset( $request_args['in'] );
		}

		// filter by a list of post id.
		if ( ! empty( $request_args['in'] ) ) {
			$args['post__in'] = explode( ',', $request_args['in'] );
			unset( $request_args['in'] );
		}

		// resource page.
		$args['paged'] = ( isset( $request_args['page'] ) ) ? absint( $request_args['page'] ) : 1;

		$args = apply_filters( 'woocommerce_api_query_args', $args, $request_args );

		return array_merge( $base_args, $args );
	}
}
