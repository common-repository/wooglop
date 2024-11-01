<?php
/**
 * Glop Admin Footer
 *
 * @author   Daniel Ruiz
 * @category Admin
 * @package GLOP/wooglop
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Glop_Admin
 */
class Wooglop_Admin {

	/**
	 * Glop_Admin constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'wooglop_register_custom_menu_page' ) );
		add_action( 'wooglop_admin_field_hidden', array( 'Wooglop_Admin_Settings', 'get_hidden_field' ) );
	}

	/**
	 * Glop Register Custom Menu Page.
	 */
	public function wooglop_register_custom_menu_page() {
		add_menu_page(
			__( 'WooGlop', 'wooglop' ),
			__( 'WooGlop', 'wooglop' ),
			'manage_options',
			'wooglop',
			array( $this, 'wooglop_print_admin_page' ),
			plugins_url( 'wooglop/icon.png' ),
			10
		);
	}

	/**
	 * Glop Print Admin Page.
	 */
	public function wooglop_print_admin_page() {
		$this->glop_save_glop_ajustes();
		include WOOGLOP_PLUGIN_ADMIN_TPL_PATH . 'glop-admin-page.php';
	}

	/**
	 * Glop Save Glop Ajustes.
	 */
	public function glop_save_glop_ajustes() {
		if (
			isset( $_POST['_wpnonce'] ) &&
			wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), sanitize_key( 'glop-settings' ) ) &&
			array_key_exists( 'save', $_POST )
		) {
			$post = $_POST;
			if ( array_key_exists( 'glop_activar_servicio', $post ) ) {
				$post['glop_activar_servicio'] = ( '1' === $post['glop_activar_servicio'] ) ? 'yes' : 'no';
			}
			if ( array_key_exists( 'glop_activar_debug', $post ) ) {
				$post['glop_activar_debug'] = ( '1' === $post['glop_activar_debug'] ) ? 'yes' : 'no';
			}
			$this->glop_save_recursive_save_ajustes( $post );
		}
	}

	/**
	 * Glop Save Recursive Save Ajustes.
	 *
	 * @param null $params Params.
	 */
	public function glop_save_recursive_save_ajustes( $params = null ) {
		foreach ( $params as $key => $item ) {
			if ( substr( $key, 0, 4 ) === 'glop' ) {
				update_option( $key, $item );
			}
		}
	}

	/**
	 * Is Glop Actived.
	 *
	 * @return bool
	 */
	public static function glop_actived() {
		return get_option( 'glop_activar_servicio', 'yes' ) === 'yes';
	}

	/**
	 * Is Glop in debug mode.
	 *
	 * @return bool
	 */
	public static function glop_in_debug() {
		return get_option( 'glop_activar_debug', 'yes' ) === 'yes';
	}

	/**
	 * Glop usuario.
	 *
	 * @return mixed|null
	 */
	public static function glop_usuario() {
		return get_option( 'glop_usuario', null );
	}

	/**
	 * Glop Password.
	 *
	 * @return mixed|null
	 */
	public static function glop_password() {
		return get_option( 'glop_pass', '' );
	}

	/**
	 * Glop Key.
	 *
	 * @return mixed|null
	 */
	public static function glop_key() {
		return get_option( 'glop_key', '' );
	}

	/**
	 * Get payments methods.
	 *
	 * @return WC_Payment_Gateway[]
	 */
	public static function get_payments_methods() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array();
		}

		// Todos los métodos instalados aunque no estén activos
		// return WC()->payment_gateways->get_payment_gateway_ids();
		// Solo los métodos activos.
		return WC()->payment_gateways->get_available_payment_gateways();
	}

	/**
	 * Get shipment methods.
	 *
	 * @return array
	 */
	public static function get_shipment_methods() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array();
		}

		return WC()->shipping()->get_shipping_methods();
	}

	/**
	 * Get tax methods
	 *
	 * @return array
	 */
	public static function get_tax_methods() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array();
		}

		$tax_classes             = array();
		$tax_classes['standard'] = __( 'Standard rate', 'wooglop' );
		$classes                 = WC_Tax::get_tax_classes();

		foreach ( $classes as $class ) {
			$tax_classes[ sanitize_title( $class ) ] = $class;
		}

		return $tax_classes;
	}
}
