<?php
/**
 * Plugin Name: WooGlop
 * Plugin URI: https://www.glop.es/ecommerce/
 * Description: WooGlop
 * Author: Glop Software
 * Author URI: https://www.glop.es/
 * Text Domain: wooglop
 * Domain Path: /languages
 * Version: 1.0.8
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 4.0.0
 * Tested up to: 5.2.3
 *
 * @package GLOP/wooglop
 */

/**
 * DEFINES
 */
define( 'WOOGLOP_NAME', plugin_basename( __FILE__ ) );
define( 'WOOGLOP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WOOGLOP_PLUGIN_INCLUDES', WOOGLOP_PLUGIN_PATH . 'includes' . DIRECTORY_SEPARATOR );
define( 'WOOGLOP_ADMIN_INCLUDES', WOOGLOP_PLUGIN_PATH . 'admin' . DIRECTORY_SEPARATOR );
define( 'WOOGLOP_PLUGIN_ADMIN_TPL_PATH', WOOGLOP_ADMIN_INCLUDES . 'tpl' . DIRECTORY_SEPARATOR );
define( 'WOOGLOP_LANGUA_INCLUDES', WOOGLOP_PLUGIN_PATH . 'languages' . DIRECTORY_SEPARATOR );
define( 'WOOGLOP_LOG', WOOGLOP_PLUGIN_PATH . 'log' . DIRECTORY_SEPARATOR );

/**
 * INCLUDES
 */
require WOOGLOP_ADMIN_INCLUDES . 'class-wooglop-admin.php';
require WOOGLOP_PLUGIN_INCLUDES . 'class-wooglop-exception.php';
require WOOGLOP_PLUGIN_INCLUDES . 'class-wooglop-product.php';
require WOOGLOP_PLUGIN_INCLUDES . 'class-wooglop-admin-settings.php';
require WOOGLOP_PLUGIN_INCLUDES . 'class-wooglop-updater.php';
require WOOGLOP_PLUGIN_INCLUDES . 'class-wooglop-rest-glop-product-controller.php';
require WOOGLOP_PLUGIN_INCLUDES . 'class-wooglop-rest-glop-version-controller.php';
require WOOGLOP_PLUGIN_INCLUDES . 'class-wooglop-rest-glop-order-controller.php';
require WOOGLOP_PLUGIN_INCLUDES . 'class-wooglop-rest-glop-tax-controller.php';

/**
 * Glop Register.
 */
function wooglop_register() {
	/**
	 * Exec
	 *
	 * @var Wooglop_Admin $glop_admin Glop Admin.
	 */
	$glop_admin = new Wooglop_Admin();

	if ( $glop_admin->glop_actived() ) {
		new Wooglop_Updater( Wooglop_Admin::glop_usuario(), Wooglop_Admin::glop_password(), Wooglop_Admin::glop_key() );
	}
}

add_action( 'init', 'wooglop_register' );

/**
 * Glop load Plugin Textdomain.
 */
function wooglop_load_plugin_textdomain() {
	load_plugin_textdomain( 'wooglop', false, WOOGLOP_LANGUA_INCLUDES );
}
// Traducciones.
add_action( 'plugins_loaded', 'wooglop_load_plugin_textdomain' );
