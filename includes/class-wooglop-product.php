<?php
/**
 * Glop Product
 *
 * @author   Daniel Ruiz
 * @category Admin
 * @package  GLOP/wooglop
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wp_crop_image' ) ) {
	include ABSPATH . 'wp-admin/includes/image.php';
}

if ( ! class_exists( 'Wooglop_Product', false ) ) :

	/**
	 * Wooglop_Product Class.
	 */
	class Wooglop_Product {

		const ACCION_CREATE = 'CREATE';
		const ACCION_UPDATE = 'UPDATE';
		const ACCION_DELETE = 'DELETE';

		const TYPE_SIMPLE   = 'simple';
		const TYPE_VARIABLE = 'variable';

		/**
		 * Accion.
		 *
		 * @var string $accion
		 */
		public $accion;
		/**
		 * Name.
		 *
		 * @var string $name
		 */
		public $name;
		/**
		 * Referencia.
		 *
		 * @var string $referencia
		 */
		public $referencia;
		/**
		 * Impuesto
		 *
		 * @var string $impuesto
		 */
		public $impuesto;
		/**
		 * Envio
		 *
		 * @var string $envio
		 */
		public $envio;
		/**
		 * Peso
		 *
		 * @var string $peso
		 */
		public $peso;
		/**
		 * Stock
		 *
		 * @var string $stock
		 */
		public $stock;
		/**
		 * Precio
		 *
		 * @var float $precio
		 */
		public $precio;
		/**
		 * Destacado
		 *
		 * @var string $destacado
		 */
		public $destacado;
		/**
		 * Borrador
		 *
		 * @var string $borrador
		 */
		public $borrador;
		/**
		 * Dimensiones
		 *
		 * @var stdClass $dimensiones
		 */
		public $dimensiones;
		/**
		 * Imaenes
		 *
		 * @var array $imagenes
		 */
		public $imagenes;
		/**
		 * Familias
		 *
		 * @var array $familias
		 */
		public $familias;
		/**
		 * Formatos
		 *
		 * @var array $formatos
		 */
		public $formatos;
		/**
		 * Atributos
		 *
		 * @var array $atributos
		 */
		public $atributos;
		/**
		 * Variaciones
		 *
		 * @var array $variaciones
		 */
		public $variaciones;
		/**
		 * Description
		 *
		 * @var string $description
		 */
		public $description;
		/**
		 * Enable_html_description
		 *
		 * @var string $enable_html_description
		 */
		public $enable_html_description;
		/**
		 * Enable_html_short_description
		 *
		 * @var string $enable_html_short_description
		 */
		public $enable_html_short_description;
		/**
		 * Descripción corta
		 *
		 * @var string $short_description
		 */
		public $short_description;
		/**
		 * Type
		 *
		 * @var string $type
		 */
		public $type;

		/**
		 * Response
		 *
		 * @var array $response
		 */
		public $response;

		/**
		 * Category
		 *
		 * @var int $categories
		 */
		public $categories;

		/**
		 * Manejar stock
		 *
		 * @var bool $manejar_stock
		 */
		public $manejar_stock;
		/**
		 * Precio Rebajado
		 *
		 * @var string $preciodos
		 */
		public $preciodos;
		/**
		 * Wooglop_Product constructor.
		 *
		 * @param array  $properties Propiedades.
		 * @param string $accion Accion.
		 *
		 * @throws Wooglop_Exception Excepcion.
		 */
		public function __construct( $properties, $accion = self::ACCION_CREATE ) {
			if ( ! self::is_action( $accion ) ) {
				throw new Wooglop_Exception(
					'glop_product_construct',
					__( 'Accion para el producto incorrecto', 'wooglop' ),
					400
				);
			}

			if ( array_key_exists( 'action', $properties ) ) {
				$accion = self::is_action( $properties['action'] ) ? $properties['action'] : $accion;
				unset( $properties['action'] );
			}

			$this->accion = strtoupper( $accion );

			$this->manejar_stock = false;

			foreach ( $properties as $key => $property ) {
				$this->{$key} = $property;
			}

			// Simple si solo tenemos un formato con referencia = 0.
			$this->type = $this->formatos
					&& is_array( $this->formatos )
					&& count( $this->formatos ) === 1
					&& '0' === $this->formatos[0]['referencia']
				? self::TYPE_SIMPLE
				: self::TYPE_VARIABLE;

			if ( ! isset( $this->name )
				&& $this->formatos
				&& is_array( $this->formatos )
				&& count( $this->formatos ) > 0
			) {
				if ( ! array_key_exists( 'name', $this->formatos[0] ) ) {
					throw new Wooglop_Exception(
						'glop_formato_sin_nombre',
						__( 'El formato no tiene nombre.', 'wooglop' ),
						400
					);
				} else {
					$this->name = $this->formatos[0]['name'];
				}
			}

			if ( self::TYPE_SIMPLE === $this->type ) {
				$primer_formato = $this->formatos[0];

				if ( array_key_exists( 'precio', $primer_formato ) ) {
					$this->precio = $this->format_price( $primer_formato['precio'] );
				}

				if ( array_key_exists( 'referencia', $primer_formato ) ) {
					$this->referencia = '0' !== $primer_formato['referencia'] ?
						$primer_formato['referencia'] :
						$this->referencia;
				}

				if ( array_key_exists( 'dimensiones', $primer_formato ) ) {
					$this->dimensiones = $primer_formato['dimensiones'];
				}

				if ( array_key_exists( 'peso', $primer_formato ) ) {
					$this->peso = $primer_formato['peso'];
				}

				if ( array_key_exists( 'preciodos', $primer_formato ) ) {
					$this->preciodos = $primer_formato['preciodos'];
				}

				// Manejar stock.
				$this->manejar_stock = true;
			}

			if ( isset( $this->formatos ) ) {
				$this->atributos = array();
				if ( self::TYPE_VARIABLE === $this->type ) {
					$this->variaciones = array();
				}
				// Todos los atributos.
				foreach ( $this->formatos as $formato ) {
					// Atributos.
					if ( array_key_exists( 'atributos', $formato ) ) {
						foreach ( $formato['atributos'] as $nombre_atributo => $atributo ) {
							if ( ! array_key_exists( $nombre_atributo, $this->atributos ) ) {
								$this->atributos[ $nombre_atributo ] = array();
							}
							if ( ! in_array( $atributo, $this->atributos[ $nombre_atributo ], true ) ) {
								$this->atributos[ $nombre_atributo ][] = $atributo;
							}
						}
					}
					// Variaciones.
					if ( self::TYPE_VARIABLE === $this->type ) {
						if ( array_key_exists( 'precio', $formato ) ) {
							$formato['precio'] = $this->format_price( $formato['precio'] );
						}
						if ( ! in_array( $formato, $this->variaciones, true ) ) {
							$this->variaciones[] = $formato;
						}
					}
				}
			}

			if ( isset( $this->nombrefamilia ) ) {
				$category_products = $this->get_product_categories();
				// Comprobar si nombrefamilia está ya en el sistema, si no crearla.
				if ( array_key_exists( 'product_categories', $category_products ) ) {
					$found = false;
					foreach ( $category_products['product_categories'] as $category_product ) {
						if ( strtolower( $this->nombrefamilia ) === strtolower( $category_product['name'] ) ) {
							$found = $category_product['id'];
							break;
						}
					}
					if ( ! $found ) {
						// Crear categoría.
						$category = $this->create_product_category(
							array(
								'product_category' => array(
									'name' => $this->nombrefamilia,
								),
							)
						);

						$this->categories = array( $category['product_category']['id'] );
					} else {
						$this->categories = array( $found );
					}
				}
			}
		}

		/**
		 * Action is one of permitted
		 *
		 * @param string $action Accion.
		 */
		public static function is_action( $action ) {
			return in_array( strtoupper( $action ), self::get_actions(), true );
		}

		/**
		 * Get all enabled actions
		 *
		 * @return array
		 */
		public static function get_actions() {
			return array(
				self::ACCION_CREATE,
				self::ACCION_UPDATE,
				self::ACCION_DELETE,
			);
		}

		/**
		 * CHeck Product
		 *
		 * @return bool
		 */
		private function check_product() {
			return ( isset( $this->name ) && strlen( $this->name ) > 0 )
				&& ( isset( $this->type ) && strlen( $this->type ) > 0 );
		}

		/**
		 * Insertar un producto en WC
		 *
		 * @return WC_Product_Simple|WC_Product_Grouped|WC_Product_External|WC_Product_Variable|WP_Error|array
		 *
		 * @throws Wooglop_Exception Exception.
		 */
		public function render() {
			$product = null;

			if ( ! self::is_action( $this->accion ) ) {
				throw new Wooglop_Exception(
					'glop_api_error_action',
					/* translators: %1$s: accion */
					sprintf( __( 'Error en la acción %s', 'wooglop' ), $this->accion ),
					400
				);
			}
			switch ( $this->accion ) {
				case self::ACCION_DELETE:
					$product = $this->delete();
					break;
				case self::ACCION_UPDATE:
					$product = $this->update();
					break;
				case self::ACCION_CREATE:
				default:
					$product = $this->create();
			}

			if ( isset( $product ) ) {
				$this->response = $this->get_product( $product );
			}
			return $this->response;
		}

		/**
		 * Get the product for the given ID
		 *
		 * @since 1.0
		 * @param WC_Product_Simple $product Product.
		 * @return array|WP_Error
		 */
		public function get_product( $product ) {
			// TODO hacer la respuesta.
			return array(
				'reference' => $product->get_sku( '' ),
				'post_id'   => $product->get_id(),
			);
		}

		/**
		 * Save variations.
		 *
		 * @since  1.0
		 *
		 * @param  WC_Product_Variable $product Producto.
		 * @param  array               $data Datos.
		 *
		 * @return bool
		 * @throws Wooglop_Exception Excepcion.
		 */
		protected function save_variations( $product, $data ) {
			$variations  = $data['variations'];
			$attributes  = $product->get_attributes();
			$variaciones = array();
			$variations2 = wc_get_products(
				array(
					'status'      => array( 'private', 'publish' ),
					'type'        => 'variation',
					'parent'      => $product->get_id(),
					'numberposts' => -1,
				)
			);
			foreach ( $variations2 as $item ) {
				$variaciones[] = $product->get_available_variation( $item );
			}

			$variaciones_o = array();
			if ( count( $variaciones ) > 0 ) {
				foreach ( $variaciones as $variacione ) {
					$variaciones_o[ $variacione['sku'] ] = $variacione['variation_id'];
				}
			}

			foreach ( $variations as $menu_order => $data ) {
				$variation_id = array_key_exists( $data['sku'], $variaciones_o ) ? absint( $variaciones_o[ $data['sku'] ] ) : 0;
				$variation    = new WC_Product_Variation( $variation_id );

				// Create initial name and status.
				if ( ! $variation->get_slug() ) {
					/* translators: 1: variation id 2: product name */
					$variation->set_name( sprintf( __( 'Variation #%1$s of %2$s', 'wooglop' ), $variation->get_id(), $product->get_name() ) );
					$variation->set_status( isset( $data['visible'] ) && false === $data['visible'] ? 'private' : 'publish' );
				}

				// Parent ID.
				$variation->set_parent_id( $product->get_id() );

				// Menu order.
				$variation->set_menu_order( $menu_order );

				// Status.
				if ( isset( $data['visible'] ) ) {
					$variation->set_status( false === $data['visible'] ? 'private' : 'publish' );
				}

				// SKU.
				if ( isset( $data['sku'] ) ) {
					try {
						$variation->set_sku( wc_clean( $data['sku'] ) );
					} catch ( WC_Data_Exception $e ) {

						throw new Wooglop_Exception(
							'glop_set_sku_error',
							/* translators: %s: error */
							sprintf( __( 'set_sku_variations: %s', 'wooglop' ), (string) $e ),
							400
						);
					}
				}

				// Thumbnail.
				if ( isset( $data['image'] ) && is_array( $data['image'] ) ) {
					$image = current( $data['image'] );
					if ( is_array( $image ) ) {
						$image['position'] = 0;
					}

					$variation = $this->save_product_images( $variation, array( $image ) );
				}

				// Virtual variation.
				if ( isset( $data['virtual'] ) ) {
					$variation->set_virtual( $data['virtual'] );
				}

				// Shipping data.
				$variation = $this->save_product_shipping_data( $variation, $data );

				// Stock handling.
				$manage_stock = (bool) $variation->get_manage_stock();
				if ( isset( $data['managing_stock'] ) ) {
					$manage_stock = $data['managing_stock'];
				}
				$variation->set_manage_stock( $manage_stock );

				$stock_status = $variation->get_stock_status();
				if ( isset( $data['in_stock'] ) ) {
					$stock_status = true === $data['in_stock'] ? 'instock' : 'outofstock';
				}
				$variation->set_stock_status( $stock_status );

				$backorders = $variation->get_backorders();
				if ( isset( $data['backorders'] ) ) {
					$backorders = $data['backorders'];
				}
				$variation->set_backorders( $backorders );

				if ( $manage_stock ) {
					if ( isset( $data['stock_quantity'] ) ) {
						$variation->set_stock_quantity( $data['stock_quantity'] );
					} elseif ( isset( $data['inventory_delta'] ) ) {
						$stock_quantity  = wc_stock_amount( $variation->get_stock_quantity() );
						$stock_quantity += wc_stock_amount( $data['inventory_delta'] );
						$variation->set_stock_quantity( $stock_quantity );
					}
				} else {
					$variation->set_backorders( 'no' );
					$variation->set_stock_quantity( '' );
				}

				// Regular Price.
				if ( isset( $data['regular_price'] ) ) {
					$variation->set_regular_price( $data['regular_price'] );
				}

				// Sale Price.
				if ( isset( $data['sale_price'] ) ) {
					$variation->set_sale_price( $data['sale_price'] );
				}

				if ( isset( $data['sale_price_dates_from'] ) ) {
					$variation->set_date_on_sale_from( $data['sale_price_dates_from'] );
				}

				if ( isset( $data['sale_price_dates_to'] ) ) {
					$variation->set_date_on_sale_to( $data['sale_price_dates_to'] );
				}

				// Tax class.
				if ( isset( $data['tax_class'] ) ) {
					$variation->set_tax_class( $data['tax_class'] );
				}

				// Description.
				if ( isset( $data['description'] ) ) {
					$variation->set_description( wp_kses_post( $data['description'] ) );
				}

				// Update taxonomies.
				if ( isset( $data['attributes'] ) ) {
					$_attributes = array();

					foreach ( $data['attributes'] as $attribute_key => $attribute ) {
						if ( ! isset( $attribute['name'] ) ) {
							continue;
						}

						$taxonomy   = 0;
						$_attribute = array();

						if ( isset( $attribute['slug'] ) ) {
							$taxonomy = $this->get_attribute_taxonomy_by_slug( $attribute['slug'] );
						}

						if ( ! $taxonomy ) {
							$taxonomy = sanitize_title( $attribute['name'] );
						}

						if ( isset( $attributes[ $taxonomy ] ) ) {
							$_attribute = $attributes[ $taxonomy ];
						}

						if ( isset( $attributes[ 'pa_' . $taxonomy ] ) ) {
							$_attribute = $attributes[ 'pa_' . $taxonomy ];
						}

						if ( isset( $_attribute['is_variation'] ) && $_attribute['is_variation'] ) {
							$_attribute_key = sanitize_title( $_attribute['name'] );

							if ( isset( $_attribute['is_taxonomy'] ) && $_attribute['is_taxonomy'] ) {
								// Don't use wc_clean as it destroys sanitized characters.
								$_attribute_value = isset( $attribute['option'] ) ? sanitize_title( stripslashes( $attribute['option'] ) ) : '';
							} else {
								$_attribute_value = isset( $attribute['option'] ) ? wc_clean( stripslashes( $attribute['option'] ) ) : '';
							}

							$_attributes[ $_attribute_key ] = $_attribute_value;
						}
					}

					$variation->set_attributes( $_attributes );
				}

				$variation->save();

				do_action( 'wooglop_save_product_variation', $variation_id, $menu_order, $variation );
			}

			return true;
		}

		/**
		 * Eliminar variaciones.
		 *
		 * @param WC_Product_Variable $product Producto.
		 */
		private function delete_variations( $product ) {
			/**
			 * Variaciones.
			 *
			 * @var WC_Product_Variation[] $variations Variacion.
			 */
			$variations = wc_get_products(
				array(
					'status' => array( 'private', 'publish' ),
					'type'   => 'variation',
					'parent' => $product->get_id(),
					'return' => 'objects',
				)
			);

			if ( $variations ) {
				foreach ( $variations as $variation_object ) {
					$variation_object->delete( true );
				}
			}
		}

		/**
		 * Get attribute taxonomy by slug.
		 *
		 * @since 1.0
		 * @param string $slug Slug.
		 * @return string|null
		 */
		private function get_attribute_taxonomy_by_slug( $slug ) {
			wp_cache_flush();
			delete_transient( 'wc_attribute_taxonomies' );
			$taxonomy             = null;
			$attribute_taxonomies = wc_get_attribute_taxonomies();

			foreach ( $attribute_taxonomies as $key => $tax ) {
				if ( $slug === $tax->attribute_name ) {
					$taxonomy = 'pa_' . $tax->attribute_name;

					break;
				}
			}

			return $taxonomy;
		}

		/**
		 * Save product meta.
		 *
		 * @since  1.0
		 * @param  WC_Product $product Producto.
		 * @param  array      $data Data.
		 * @return WC_Product
		 * @throws Wooglop_Exception Excepcion.
		 */
		protected function save_product_meta( $product, $data ) {
			try {
				// Tax status.
				if ( isset( $data['tax_status'] ) ) {
					$product->set_tax_status( wc_clean( $data['tax_status'] ) );
				}

				// Tax Class.
				// TODO obtener class según el id de tax que me envía el json.
				if ( isset( $data['tax_class'] ) ) {
					$product->set_tax_class( wc_clean( $data['tax_class'] ) );
				}

				// Shipping data.
				$product = $this->save_product_shipping_data( $product, $data );

				// SKU.
				if ( isset( $data['sku'] ) ) {
					$sku     = $product->get_sku();
					$new_sku = wc_clean( $data['sku'] );

					if ( '' === $new_sku ) {
						$product->set_sku( '' );
					} elseif ( $new_sku !== $sku ) {
						if ( ! empty( $new_sku ) ) {
							$unique_sku = wc_product_has_unique_sku( $product->get_id(), $new_sku );
							if ( ! $unique_sku ) {
								throw new Wooglop_Exception(
									'wooglop_product_sku_already_exists',
									/* translators: %1$s: product */
									sprintf( __( 'The SKU %s already exists on another product.', 'wooglop' ), $new_sku ),
									400
								);
							} else {
								$product->set_sku( $new_sku );
							}
						} else {
							$product->set_sku( '' );
						}
					}
				}

				// Attributes.
				if ( isset( $data['attributes'] ) ) {
					$attributes = array();

					foreach ( $data['attributes'] as $attribute ) {
						$is_taxonomy = 0;
						$taxonomy    = 0;

						if ( ! isset( $attribute['name'] ) ) {
							continue;
						}

						$attribute['slug'] = sanitize_title( $attribute['name'] );

						$taxonomy       = $this->get_attribute_taxonomy_by_slug( $attribute['slug'] );
						$attribute_slug = sanitize_title( $attribute['slug'] );

						if ( ! $taxonomy ) {
							global $wpdb;
							$taxonomy = $wpdb->insert(
								$wpdb->prefix . 'woocommerce_attribute_taxonomies',
								array(
									'attribute_label'   => $attribute['name'],
									'attribute_name'    => $attribute['slug'],
									'attribute_type'    => 'select',
									'attribute_orderby' => 'menu_order',
									'attribute_public'  => 0,
								),
								array( '%s', '%s', '%s', '%s', '%d' )
							);
							$id = $wpdb->insert_id;
							do_action( 'woocommerce_api_create_product_attribute', $id, $data );
							delete_transient( 'wc_attribute_taxonomies' );
							WC_Cache_Helper::incr_cache_prefix( 'woocommerce-attributes' );
							$taxonomy = $this->get_attribute_taxonomy_by_slug( $attribute['slug'] );
							register_taxonomy(
								$taxonomy,
								'product',
								array(
									'label' => $attribute['name'],
								)
							);
						}
						$is_taxonomy = true;

						if ( $is_taxonomy ) {

							$attribute_id = wc_attribute_taxonomy_id_by_name( $attribute['name'] );

							if ( isset( $attribute['options'] ) ) {
								$options = $attribute['options'];

								if ( ! is_array( $attribute['options'] ) ) {
									// Text based attributes - Posted values are term names.
									$options = explode( WC_DELIMITER, $options );
								}

								$values = array_map( 'wc_sanitize_term_text_based', $options );
								$values = array_filter( $values, 'strlen' );
							} else {
								$values = array();
							}

							// Update post terms.
							if ( taxonomy_exists( $taxonomy ) ) {
								wp_set_object_terms( $product->get_id(), $values, $taxonomy );
							}

							if ( ! empty( $values ) ) {
								// Add attribute to array, but don't set values.
								$attribute_object = new WC_Product_Attribute();
								$attribute_object->set_id( $attribute_id );
								$attribute_object->set_name( $taxonomy );
								$attribute_object->set_options( $values );
								$attribute_object->set_position( isset( $attribute['position'] ) ? absint( $attribute['position'] ) : 0 );
								$attribute_object->set_visible( ( isset( $attribute['visible'] ) && $attribute['visible'] ) ? 1 : 0 );
								$attribute_object->set_variation( ( isset( $attribute['variation'] ) && $attribute['variation'] ) ? 1 : 0 );
								$attributes[] = $attribute_object;
							}
						} elseif ( isset( $attribute['options'] ) ) {
							// Array based.
							if ( is_array( $attribute['options'] ) ) {
								$values = $attribute['options'];

								// Text based, separate by pipe.
							} else {
								$values = array_map( 'wc_clean', explode( WC_DELIMITER, $attribute['options'] ) );
							}

							// Custom attribute - Add attribute to array and set the values.
							$attribute_object = new WC_Product_Attribute();
							$attribute_object->set_name( $attribute['name'] );
							$attribute_object->set_options( $values );
							$attribute_object->set_position( isset( $attribute['position'] ) ? absint( $attribute['position'] ) : 0 );
							$attribute_object->set_visible( ( isset( $attribute['visible'] ) && $attribute['visible'] ) ? 1 : 0 );
							$attribute_object->set_variation( ( isset( $attribute['variation'] ) && $attribute['variation'] ) ? 1 : 0 );
							$attributes[] = $attribute_object;
						}
					}

					uasort( $attributes, 'wc_product_attribute_uasort_comparison' );

					$product->set_attributes( $attributes );
				}

				// Regular Price.
				if ( isset( $data['regular_price'] ) ) {
					$regular_price = ( '' === $data['regular_price'] ) ? '' : $data['regular_price'];
					$product->set_regular_price( $regular_price );
				}

				// Sale Price.
				if ( isset( $data['sale_price'] ) ) {
					$sale_price = ( '' === $data['sale_price'] ) ? '' : $data['sale_price'];
					$product->set_sale_price( $sale_price );
				}

				if ( isset( $data['sale_price_dates_from'] ) ) {
					$date_from = $data['sale_price_dates_from'];
				} else {
					$date_from = $product->get_date_on_sale_from() ? date( 'Y-m-d', $product->get_date_on_sale_from()->getTimestamp() ) : '';
				}

				if ( isset( $data['sale_price_dates_to'] ) ) {
					$date_to = $data['sale_price_dates_to'];
				} else {
					$date_to = $product->get_date_on_sale_to() ? date( 'Y-m-d', $product->get_date_on_sale_to()->getTimestamp() ) : '';
				}

				if ( $date_to && ! $date_from ) {
					$date_from = strtotime( 'NOW', current_time( 'timestamp', true ) );
				}

				$product->set_date_on_sale_to( $date_to );
				$product->set_date_on_sale_from( $date_from );

				if ( $product->is_on_sale( 'edit' ) ) {
					$product->set_price( $product->get_sale_price( 'edit' ) );
				} else {
					$product->set_price( $product->get_regular_price( 'edit' ) );
				}

				// TODO: esto lo podemos usar para los formatos.
				// Product parent ID for groups.
				if ( isset( $data['parent_id'] ) ) {
					$product->set_parent_id( absint( $data['parent_id'] ) );
				}

				// Stock status.
				if ( isset( $data['in_stock'] ) ) {
					$stock_status = ( true === $data['in_stock'] ) ? 'instock' : 'outofstock';
				} else {
					$stock_status = $product->get_stock_status();

					if ( '' === $stock_status ) {
						$stock_status = 'instock';
					}
				}

				// Stock Data.
				if ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) {
					// Manage stock.
					if ( isset( $data['managing_stock'] ) ) {
						$managing_stock = ( true === $data['managing_stock'] ) ? 'yes' : 'no';
						$product->set_manage_stock( $managing_stock );
					} else {
						$managing_stock = $product->get_manage_stock() ? 'yes' : 'no';
					}

					// Backorders.
					if ( isset( $data['backorders'] ) ) {
						if ( 'notify' === $data['backorders'] ) {
							$backorders = 'notify';
						} else {
							$backorders = ( true === $data['backorders'] ) ? 'yes' : 'no';
						}

						$product->set_backorders( $backorders );
					} else {
						$backorders = $product->get_backorders();
					}

					if ( 'yes' === $managing_stock ) {
						$product->set_backorders( $backorders );

						// Stock status is always determined by children so sync later.
						if ( ! $product->is_type( 'variable' ) ) {
							$product->set_stock_status( $stock_status );
						}

						// Stock quantity.
						if ( isset( $data['stock_quantity'] ) ) {
							$product->set_stock_quantity( wc_stock_amount( $data['stock_quantity'] ) );
						} elseif ( isset( $data['inventory_delta'] ) ) {
							$stock_quantity  = wc_stock_amount( $product->get_stock_quantity() );
							$stock_quantity += wc_stock_amount( $data['inventory_delta'] );
							$product->set_stock_quantity( wc_stock_amount( $stock_quantity ) );
						}
					} else {
						// Don't manage stock.
						$product->set_manage_stock( 'no' );
						$product->set_backorders( $backorders );
						$product->set_stock_quantity( '' );
						$product->set_stock_status( $stock_status );
					}
				} elseif ( ! $product->is_type( 'variable' ) ) {
					$product->set_stock_status( $stock_status );
				}

				// Product categories.
				if ( isset( $data['categories'] ) && is_array( $data['categories'] ) ) {
					$product->set_category_ids( $data['categories'] );
				}

				// Product tags.
				if ( isset( $data['tags'] ) && is_array( $data['tags'] ) ) {
					$product->set_tag_ids( $data['tags'] );
				}

				// Do action for product type.
				do_action( 'wooglop_api_process_product_meta_' . $product->get_type(), $product->get_id(), $data );

				return $product;
			} catch ( Exception $e ) {
				throw new Wooglop_Exception(
					'wooglop_api_error_save_metadata',
					/* translators: %1$s: product */
					sprintf( __( 'Error %s', 'wooglop' ), $e->__toString() ),
					400
				);
			}
		}

		/**
		 * Save product shipping data
		 *
		 * @since 1.0
		 * @param WC_Product $product Product.
		 * @param array      $data Data.
		 * @return WC_Product
		 */
		private function save_product_shipping_data( $product, $data ) {
			if ( isset( $data['weight'] ) ) {
				$product->set_weight( '' === $data['weight'] ? '' : wc_format_decimal( $data['weight'] ) );
			}

			// Product dimensions.
			if ( isset( $data['dimensions'] ) ) {
				// Height.
				if ( isset( $data['dimensions']['height'] ) ) {
					$product->set_height( '' === $data['dimensions']['height'] ? '' : wc_format_decimal( $data['dimensions']['height'] ) );
				}

				// Width.
				if ( isset( $data['dimensions']['width'] ) ) {
					$product->set_width( '' === $data['dimensions']['width'] ? '' : wc_format_decimal( $data['dimensions']['width'] ) );
				}

				// Length.
				if ( isset( $data['dimensions']['length'] ) ) {
					$product->set_length( '' === $data['dimensions']['length'] ? '' : wc_format_decimal( $data['dimensions']['length'] ) );
				}
			}

			// Virtual.
			if ( isset( $data['virtual'] ) ) {
				$virtual = ( true === $data['virtual'] ) ? 'yes' : 'no';

				if ( 'yes' === $virtual ) {
					$product->set_weight( '' );
					$product->set_height( '' );
					$product->set_length( '' );
					$product->set_width( '' );
				}
			}

			// Shipping class.
			// TODO shipping class según lo que me envíe el json.
			if ( isset( $data['shipping_class'] ) ) {
				$data_store        = $product->get_data_store();
				$shipping_class_id = $data_store->get_shipping_class_id_by_slug( wc_clean( $data['shipping_class'] ) );
				$product->set_shipping_class_id( $shipping_class_id );
			}

			return $product;
		}

		/**
		 * Guardar imágenes
		 *
		 * @param WC_Product_Simple|WC_Product_Grouped|WC_Product_External|WC_Product_Variable $product Producto.
		 * @param array                                                                        $images Imágenes.
		 *
		 * @return WC_Product
		 * @throws Wooglop_Exception Excepcion.
		 */
		private function save_product_images( $product, $images = null ) {
			$images = null === $images ? $this->imagenes : $images;
			if ( is_array( $images ) ) {
				$gallery = array();
				foreach ( $images as $key => $image ) {
					$attachment_id = 0;
					if ( ! array_key_exists( 'src', $image ) ) {
						continue;
					}
					$upload        = $this->upload_product_image( $image );
					$attachment_id = $this->set_product_image_as_attachment( $upload, $product->get_id() );

					if ( 0 === $key ) {
						$product->set_image_id( $attachment_id );
					} else {
						$gallery[] = $attachment_id;
					}

					// Set the image alt if present.
					if ( ! empty( $image['alt'] ) && $attachment_id ) {
						update_post_meta( $attachment_id, '_wp_attachment_image_alt', wc_clean( $image['alt'] ) );
					}

					// Set the image title if present.
					if ( ! empty( $image['nombre'] ) && $attachment_id ) {
						wp_update_post(
							array(
								'ID'         => $attachment_id,
								'post_title' => $image['nombre'],
							)
						);
					}
				}

				if ( ! empty( $gallery ) ) {
					$product->set_gallery_image_ids( $gallery );
				}
			} else {
				$product->set_image_id( '' );
				$product->set_gallery_image_ids( array() );
			}

			return $product;
		}

		/**
		 * Upload Product Image
		 *
		 * @throws Wooglop_Exception Excepcion.
		 *
		 * @param stdClass $image Image.
		 * @return array|WP_Error
		 */
		public function upload_product_image( $image ) {
			if ( ! array_key_exists( 'nombre', $image ) || empty( $image['nombre'] ) ) {
				// Nombre aleatorio.
				$image['nombre'] = str_replace(
					'.',
					'_',
					uniqid( 'product_image_' . wp_rand(), true )
				);
			}
			$file_name    = wc_clean( str_replace( '.jpg', '', $image['nombre'] ) . '.jpg' );
			$image_decode = base64_decode( $image['src'] );

			// Ensure we have a file name and type.
			wp_check_filetype( $file_name, wc_rest_allowed_image_mime_types() );

			// Upload the file.
			$upload = wp_upload_bits( $file_name, null, $image_decode );
			if ( $upload['error'] ) {
				throw new Wooglop_Exception( 'glop_upload_image', $upload['error'], 400 );
			}

			// Get filesize.
			$filesize = filesize( $upload['file'] );

			if ( 0 === $filesize ) {
				// phpcs:disable WordPress.VIP.FileSystemWritesDisallow
				unlink( $upload['file'] );
				unset( $upload );
				throw new Wooglop_Exception( 'glop_upload_file_error', __( 'Zero size file downloaded.', 'wooglop' ), 400 );
			}

			unset( $response );

			do_action( 'wooglop_uploaded_image', $upload );

			return $upload;
		}

		/**
		 * Set product image as attachment
		 *
		 * @param string $upload Upload.
		 * @param string $id Id.
		 *
		 * @return int|WP_Error
		 */
		protected function set_product_image_as_attachment( $upload, $id ) {
			$info       = wp_check_filetype( $upload['file'] );
			$title      = '';
			$content    = '';
			$image_meta = wp_read_image_metadata( $upload['file'] );
			if ( $image_meta ) {
				if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
					$title = wc_clean( $image_meta['title'] );
				}
				if ( trim( $image_meta['caption'] ) ) {
					$content = wc_clean( $image_meta['caption'] );
				}
			}

			$attachment = array(
				'post_mime_type' => $info['type'],
				'guid'           => $upload['url'],
				'post_parent'    => $id,
				'post_title'     => $title,
				'post_content'   => $content,
			);

			$attachment_id = wp_insert_attachment( $attachment, $upload['file'], $id );
			if ( ! is_wp_error( $attachment_id ) ) {
				wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
			}

			return $attachment_id;
		}

		/**
		 * Transforma en float un string de precio
		 *
		 * @param string $precio Precio.
		 *
		 * @return float
		 */
		private function format_price( $precio ) {
			return floatval(
				str_replace( ',', '.', $precio )
			);
		}

		/**
		 * Datos de JSON de glop a datos de Woocommerce
		 *
		 * @return array
		 */
		private function glop_to_wc() {
			$data = array();

			if ( $this->name ) {
				$data['title'] = $this->name;
				$data['name']  = $this->name;
			}
			if ( $this->description ) {
				$data['description']       = $this->description;
				$data['short_description'] = $this->short_description;
			}
			$data['enable_html_description']       = true;
			$data['enable_html_short_description'] = true;
			$data['type']                          = $this->type;
			$data['images']                        = $this->imagenes;
			$data['sku']                           = $this->referencia;
			$data['regular_price']                 = $this->precio / 100;
			$data['sale_price']                    = $this->precio / 100;
			$data['stock_quantity']                = $this->stock / 100;
			$data['in_stock']                      = 0 < $data['stock_quantity'];
			$data['weight']                        = $this->peso / 100;
			$data['managing_stock']                = self::TYPE_SIMPLE === $this->type;
			$data['tax_status']                    = 'none';
			$data['featured']                      = isset( $this->destacado ) && '1' === $this->destacado;
			$data['status']                        = ( isset( $this->borrador ) && '1' === $this->borrador ) ? 'draft' : 'publish';

			if ( count( $this->categories ) > 0 ) {
				$data['categories'] = $this->categories;
			}

			if ( $this->atributos && count( $this->atributos ) > 0 ) {
				$data['attributes'] = array();
				foreach ( $this->atributos as $nombre_atributo => $atributo ) {
					$slug                        = sanitize_title( $nombre_atributo );
					$atributos                   = array(
						'name'      => $nombre_atributo,
						'options'   => implode( WC_DELIMITER, $atributo ),
						'visible'   => true,
						'variation' => true,
					);
					$data['attributes'][ $slug ] = $atributos;
				}
			}

			if ( $this->variaciones && count( $this->variaciones ) > 0 ) {
				$data['variations'] = array();
				foreach ( $this->variaciones as $key_atributo => $variacion ) {
					$slug           = sanitize_title( $variacion['name'] );
					$variacion_data = array(
						'name'    => $variacion['name'],
						'options' => array(),
						'visible' => true,
						'slug'    => $slug,
					);
					if ( array_key_exists( 'referencia', $variacion ) ) {
						$variacion_data['sku'] = $variacion['referencia'];
					}
					if ( array_key_exists( 'stock', $variacion ) ) {
						$variacion_data['stock_quantity'] = floatval( $variacion['stock'] ) / 100;
						$variacion_data['stock']          = floatval( $variacion['stock'] ) / 100;
						$variacion_data['managing_stock'] = true;
						$variacion_data['in_stock']       = ( array_key_exists( 'stock_quantity', $variacion ) ) ? 0 < $variacion['stock_quantity'] : true;
					}
					if ( array_key_exists( 'precio', $variacion ) ) {
						$variacion_data['regular_price'] = $variacion['precio'] / 100;
					}
					if ( array_key_exists( 'preciodos', $variacion ) ) {
						$variacion_data['sale_price'] = $variacion['preciodos'] / 100;
					} else {
						$variacion_data['sale_price'] = $variacion_data['regular_price'];
					}
					if ( array_key_exists( 'peso', $variacion ) ) {
						$variacion_data['weight'] = $variacion['peso'] / 100;
					}
					if ( array_key_exists( 'dimensiones', $variacion ) ) {
						$variacion_data['dimensions'] = array();
						if ( array_key_exists( 'ancho', (array) $variacion['dimensiones'] ) ) {
							$variacion_data['dimensions']['width'] = $variacion['dimensiones']['ancho'] / 100;
						}
						if ( array_key_exists( 'alto', (array) $variacion['dimensiones'] ) ) {
							$variacion_data['dimensions']['height'] = $variacion['dimensiones']['alto'] / 100;
						}
						if ( array_key_exists( 'profundo', (array) $variacion['dimensiones'] ) ) {
							$variacion_data['dimensions']['length'] = $variacion['dimensiones']['profundo'] / 100;
						}
					}
					if ( array_key_exists( 'atributos', $variacion ) ) {
						if ( ! array_key_exists( 'attributes', $variacion_data ) ) {
							$variacion_data['attributes'] = array();
						}
						foreach ( $variacion['atributos'] as $key => $atributo2 ) {
							$slug                           = sanitize_title( $key );
							$variacion_data['attributes'][] = array(
								'name'    => $slug,
								'option'  => $atributo2,
								'visible' => true,
							);
						}
					}
					$data['variations'][] = $variacion_data;
				}
			}

			if ( array_key_exists( 'ancho', (array) $this->dimensiones ) ||
				array_key_exists( 'alto', (array) $this->dimensiones ) ||
				array_key_exists( 'profundo', (array) $this->dimensiones )
			) {
				$data['dimensions'] = array();
				if ( array_key_exists( 'ancho', (array) $this->dimensiones ) ) {
					$data['dimensions']['width'] = $this->dimensiones['ancho'] / 100;
				}
				if ( array_key_exists( 'alto', (array) $this->dimensiones ) ) {
					$data['dimensions']['height'] = $this->dimensiones['alto'] / 100;
				}
				if ( array_key_exists( 'profundo', (array) $this->dimensiones ) ) {
					$data['dimensions']['length'] = $this->dimensiones['profundo'] / 100;
				}
			}

			if ( $this->impuesto ) {
				// Buscar el rate con % = $this->impuesto.
				$classes = WC_Tax::get_tax_classes();
				$found   = null;
				foreach ( $classes as $class ) {
					$rate = array_values( WC_Tax::get_base_tax_rates( $class ) );
					if ( count( $rate ) > 0 && (float) $rate[0]['rate'] === (float) $this->impuesto / 100 ) {
						$found = $class;
						break;
					}
				}

				if ( $found ) {
					$data['tax_class']  = $found;
					$data['tax_status'] = 'taxable';
				}
			}

			if ( ! empty( $this->preciodos ) ) {
				$data['sale_price'] = $this->preciodos / 100;
			} else {
				$data['sale_price'] = $data['regular_price'];
			}

			return $data;
		}

		/**
		 * Create a new product category.
		 *
		 * @since  2.5.0
		 * @param  array $data     Posted data.
		 * @return array|Wooglop_Exception  Product category if succeed, otherwise WP_Error will be returned.
		 * @throws Wooglop_Exception Excepcion.
		 */
		public function create_product_category( $data ) {
			if ( ! isset( $data['product_category'] ) ) {
				throw new Wooglop_Exception(
					'glop_api_missing_product_category_data',
					/* translators: 1: variation id 2: product name */
					sprintf( __( 'No %1$s data specified to create %1$s', 'wooglop' ), 'product_category' ),
					400
				);
			}

			$defaults = array(
				'name'        => '',
				'slug'        => '',
				'description' => '',
				'parent'      => 0,
				'display'     => 'default',
				'image'       => '',
			);

			$data = wp_parse_args( $data['product_category'], $defaults );
			$data = apply_filters( 'wooglop_api_create_product_category_data', $data, $this );

			// Check parent.
			$data['parent'] = absint( $data['parent'] );
			if ( $data['parent'] ) {
				$parent = get_term_by( 'id', $data['parent'], 'product_cat' );
				if ( ! $parent ) {
					throw new Wooglop_Exception( 'wooglop_api_invalid_product_category_parent', __( 'Product category parent is invalid', 'wooglop' ), 400 );
				}
			}

			// If value of image is numeric, assume value as image_id.
			$image    = $data['image'];
			$image_id = 0;
			if ( is_numeric( $image ) ) {
				$image_id = absint( $image );
			} elseif ( ! empty( $image ) ) {
				$upload   = $this->upload_product_category_image( esc_url_raw( $image ) );
				$image_id = $this->set_product_category_image_as_attachment( $upload );
			}

			$insert = wp_insert_term( $data['name'], 'product_cat', $data );
			if ( is_wp_error( $insert ) ) {
				throw new Wooglop_Exception( 'wooglop_api_cannot_create_product_category', $insert->get_error_message(), 400 );
			}

			$id = $insert['term_id'];

			update_woocommerce_term_meta( $id, 'display_type', 'default' === $data['display'] ? '' : sanitize_text_field( $data['display'] ) );

			// Check if image_id is a valid image attachment before updating the term meta.
			if ( $image_id && wp_attachment_is_image( $image_id ) ) {
				update_woocommerce_term_meta( $id, 'thumbnail_id', $image_id );
			}

			do_action( 'wooglop_api_create_product_category', $id, $data );

			return $this->get_product_category( $id );

		}

		/**
		 * Get a listing of product categories
		 *
		 * @since 1.0
		 *
		 * @param string|null $fields fields to limit response to.
		 * @throws Wooglop_Exception Excepcion.
		 *
		 * @return array|Wooglop_Exception
		 */
		public function get_product_categories( $fields = null ) {
			$product_categories = array();

			$terms = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
					'fields'     => 'ids',
				)
			);

			foreach ( $terms as $term_id ) {
				$product_categories[] = current( $this->get_product_category( $term_id, $fields ) );
			}

			return array( 'product_categories' => apply_filters( 'wooglop_api_product_categories_response', $product_categories, $terms, $fields, $this ) );

		}

		/**
		 * Get the product category for the given ID
		 *
		 * @since 1.0
		 *
		 * @param string      $id product category term ID.
		 * @param string|null $fields fields to limit response to.
		 *
		 * @throws Wooglop_Exception Excepcion.
		 *
		 * @return array|Wooglop_Exception
		 */
		public function get_product_category( $id, $fields = null ) {
			try {
				$id = absint( $id );

				// Validate ID.
				if ( empty( $id ) ) {
					throw new Wooglop_Exception( 'glop_api_invalid_product_category_id', __( 'Invalid product category ID', 'wooglop' ), 400 );
				}

				$term = get_term( $id, 'product_cat' );

				if ( is_wp_error( $term ) || is_null( $term ) ) {
					throw new Wooglop_Exception( 'glop_api_invalid_product_category_id', __( 'A product category with the provided ID could not be found', 'wooglop' ), 400 );
				}

				$term_id = intval( $term->term_id );

				// Get category display type.
				$display_type = get_woocommerce_term_meta( $term_id, 'display_type' );

				// Get category image.
				$image    = '';
				$image_id = get_woocommerce_term_meta( $term_id, 'thumbnail_id' );
				if ( $image_id ) {
					$image = wp_get_attachment_url( $image_id );
				}

				$product_category = array(
					'id'          => $term_id,
					'name'        => $term->name,
					'slug'        => $term->slug,
					'parent'      => $term->parent,
					'description' => $term->description,
					'display'     => $display_type ? $display_type : 'default',
					'image'       => $image ? esc_url( $image ) : '',
					'count'       => intval( $term->count ),
				);

				return array( 'product_category' => apply_filters( 'wooglop_api_product_category_response', $product_category, $id, $fields, $term, $this ) );
			} catch ( Exception $e ) {
				return new Wooglop_Exception( 'wooglop_get_product_category', $e->getMessage(), 400 );
			}
		}


		/**
		 * Crear producto
		 *
		 * @return WC_Product|WC_Product_External|WC_Product_Grouped|WC_Product_Simple|WC_Product_Variable
		 *
		 * @throws Wooglop_Exception Excepción.
		 */
		private function create() {

			if ( ! $this->check_product() ) {
				throw new Wooglop_Exception(
					'glop_api_missing_product_data',
					/* translators: %1$s: product */
					sprintf( __( 'No %1$s data specified to create %1$s', 'wooglop' ), 'product' ),
					400
				);
			}

			$data = $this->glop_to_wc();

			// Set visible visibility when not sent.
			if ( ! isset( $data['catalog_visibility'] ) ) {
				$data['catalog_visibility'] = 'visible';
			}

			// Validate the product type.
			if ( ! in_array( wc_clean( $data['type'] ), array_keys( wc_get_product_types() ), true ) ) {
				throw new Wooglop_Exception(
					'wooglop_api_invalid_product_type',
					sprintf(
						/* translators: %s: product */
						__( 'Invalid product type - the product type must be any of these: %s', 'wooglop' ),
						implode( ', ', array_keys( wc_get_product_types() ) )
					),
					400
				);
			}

			// Enable description html tags.
			$post_content = isset( $data['description'] ) ? wc_clean( $data['description'] ) : '';
			if ( $post_content && isset( $data['enable_html_description'] ) && true === $data['enable_html_description'] ) {
				$post_content = $data['description'];
			}

			// Enable short description html tags.
			$post_excerpt = isset( $data['short_description'] ) ? wc_clean( $data['short_description'] ) : '';
			if ( $post_excerpt && isset( $data['enable_html_short_description'] ) && true === $data['enable_html_short_description'] ) {
				$post_excerpt = $data['short_description'];
			}

			$classname = WC_Product_Factory::get_classname_from_product_type( $data['type'] );
			if ( ! class_exists( $classname ) ) {
				$classname = 'WC_Product_Simple';
			}

			/**
			 * Producto a crear
			 *
			 * @var WC_Product_Simple|WC_Product_Grouped|WC_Product_External|WC_Product_Variable $product Producto.
			 */
			$product = new $classname();

			if ( isset( $data['title'] ) ) {
				$product->set_name( wc_clean( $data['title'] ) );
			}
			$product->set_status( isset( $data['status'] ) ? wc_clean( $data['status'] ) : 'publish' );
			if ( isset( $data['short_description'] ) ) {
				$product->set_short_description( isset( $data['short_description'] ) ? $post_excerpt : '' );
			}
			if ( isset( $data['description'] ) ) {
				$product->set_description( isset( $data['description'] ) ? $post_content : '' );
			}
			$product->set_menu_order( isset( $data['menu_order'] ) ? intval( $data['menu_order'] ) : 0 );

			if ( ! empty( $data['name'] ) ) {
				$product->set_slug( sanitize_title( $data['name'] ) );
			}

			// Featured Product.
			$product->set_featured( isset( $data['featured'] ) ? wc_clean( $data['featured'] ) : '0' );

			// Attempts to create the new product.
			$product->save();
			$id = $product->get_id();

			// Checks for an error in the product creation.
			if ( 0 >= $id ) {
				throw new Wooglop_Exception( 'glop_cannot_create_product', $id->get_error_message(), 400 );
			}

			// Check for featured/gallery images, upload it and set it.
			if ( isset( $data['images'] ) ) {
				$product = $this->save_product_images( $product );
			}

			// Save product meta fields.
			$product = $this->save_product_meta( $product, $data );
			$product->save();

			// Save variations.
			if ( isset( $data['type'] ) && 'variable' === $data['type'] && isset( $data['variations'] ) && is_array( $data['variations'] ) ) {
				$this->save_variations( $product, $data );
			}

			do_action( 'wooglop_create_product', $id, $data );

			// Clear cache/transients.
			wc_delete_product_transients( $id );

			return $product;
		}

		/**
		 * Actualizar producto
		 *
		 * @return WC_Product|WC_Product_External|WC_Product_Grouped|WC_Product_Simple|WC_Product_Variable
		 *
		 * @throws Wooglop_Exception Excepción.
		 */
		private function update() {
			if ( ! isset( $this->referencia ) ) {
				throw new Wooglop_Exception(
					'glop_api_missing_product_data',
					sprintf(
						/* translators: %s: product */
						__( 'No %1$s data specified to edit %1$s', 'wooglop' ),
						implode( ', ', array_keys( wc_get_product_types() ) )
					),
					400
				);
			}

			// Obtenemos ID producto por SKU.
			$product_id = wc_get_product_id_by_sku( $this->referencia );
			if ( ! $product_id ) {
				throw new Wooglop_Exception(
					'wooglop_api_update_no_reference',
					sprintf(
						/* translators: %s: referencia */
						__( 'No hay un producto con referencia', 'wooglop' ),
						$this->referencia
					),
					400
				);
			}
			$product = wc_get_product( $product_id );

			$data = $this->glop_to_wc();

			// Product title.
			if ( isset( $data['title'] ) ) {
				$product->set_name( wc_clean( $data['title'] ) );
			}

			// Product name (slug).
			if ( isset( $data['name'] ) ) {
				$product->set_slug( wc_clean( $data['name'] ) );
			}

			// Product status.
			if ( isset( $data['status'] ) ) {
				$product->set_status( wc_clean( $data['status'] ) );
			}

			// Product short description.
			if ( isset( $data['short_description'] ) ) {
				// Enable short description html tags.
				$post_excerpt = ( isset( $data['enable_html_short_description'] ) && true === $data['enable_html_short_description'] ) ? $data['short_description'] : wc_clean( $data['short_description'] );
				$product->set_short_description( $post_excerpt );
			}

			// Product description.
			if ( isset( $data['description'] ) ) {
				// Enable description html tags.
				$post_content = ( isset( $data['enable_html_description'] ) && true === $data['enable_html_description'] ) ? $data['description'] : wc_clean( $data['description'] );
				$product->set_description( $post_content );
			}

			// Validate the product type.
			if ( isset( $data['type'] ) && ! in_array( wc_clean( $data['type'] ), array_keys( wc_get_product_types() ), true ) ) {
				throw new Wooglop_Exception(
					'wooglop_api_invalid_product_type',
					sprintf(
						/* translators: %s: product */
						__( 'Invalid product type - the product type must be any of these: %s', 'wooglop' ),
						implode( ', ', array_keys( wc_get_product_types() ) )
					),
					400
				);
			}

			// Check for featured/gallery images, upload it and set it.
			if ( isset( $data['images'] ) ) {
				$product = $this->save_product_images( $product, $data['images'] );
			}

			// Save product meta fields.
			$product = $this->save_product_meta( $product, $data );

			// Save variations.
			if ( $product->is_type( 'variable' ) ) {
				if ( isset( $data['variations'] ) && is_array( $data['variations'] ) ) {
					// Eliminar variaciones.
//					$this->delete_variations( $product );
					$this->save_variations( $product, $data );
				} else {
					// Just sync variations.
					$product = WC_Product_Variable::sync( $product, false );
				}
			}

			$product->set_featured( isset( $data['featured'] ) ? wc_clean( $data['featured'] ) : '0' );

			$product->save();

			do_action( 'woowoocommerce_api_edit_product', $product_id, $data );

			// Clear cache/transients.
			wc_delete_product_transients( $product_id );

			return $product;
		}

		/**
		 * Eliminar producto
		 *
		 * @return array
		 *
		 * @throws Wooglop_Exception Excepción.
		 */
		private function delete() {
			// Obtenemos ID producto por SKU.
			$id = wc_get_product_id_by_sku( $this->referencia );
			if ( ! $id ) {
				throw new Wooglop_Exception(
					'wooglop_api_delete_no_reference',
					sprintf(
						/* translators: %s: referencia */
						__( 'No hay un producto con referencia', 'wooglop' ),
						$this->referencia
					),
					400
				);
			}

			if ( is_wp_error( $id ) ) {
				return $id;
			}

			$product = wc_get_product( $id );

			do_action( 'woowoocommerce_api_delete_product', $id, $this );

			// If we're forcing, then delete permanently.
			$force = true;
			if ( $force ) {
				if ( $product->is_type( 'variable' ) ) {
					foreach ( $product->get_children() as $child_id ) {
						$child = wc_get_product( $child_id );
						$child->delete( true );
					}
				} elseif ( $product->is_type( 'grouped' ) ) {
					foreach ( $product->get_children() as $child_id ) {
						$child = wc_get_product( $child_id );
						$child->set_parent_id( 0 );
						$child->save();
					}
				}

				$product->delete( true );
				$result = $product->get_id() > 0 ? false : true;
			} else {
				$product->delete();
				$result = 'trash' === $product->get_status();
			}

			if ( ! $result ) {
				throw new Wooglop_Exception(
					'glop_api_cannot_delete_product',
					sprintf(
						/* translators: %s: product */
						sprintf( __( 'This %s cannot be deleted', 'wooglop' ), 'product' )
					),
					400
				);
			}

			// Delete parent product transients.
			$parent_id = wp_get_post_parent_id( $id );
			if ( $parent_id ) {
				wc_delete_product_transients( $parent_id );
			}

			if ( $force ) {
				return $product;
			} else {
				$this->server->send_status( '202' );

				return array(
					'message' => sprintf(
						/* translators: %s: product */
						__( 'Deleted %s', 'wooglop' ),
						'product'
					),
				);
			}
		}
	}

endif;
