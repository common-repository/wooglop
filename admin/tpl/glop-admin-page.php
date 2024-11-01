<?php
/**
 * Glop Admin Page
 *
 * @author   Daniel Ruiz
 * @category Admin
 * @package GLOP/wooglop
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require WOOGLOP_PLUGIN_ADMIN_TPL_PATH . 'includes/glop-admin-header.php';
?>

<style>
	.col-xs-12 {
		display: inline-block;
		width: 100%;
		vertical-align: top;
	}

	@media all and (min-width: 782px) {
		.col-xs-12 {
			width: 50%;
		}
	}

	@media all and (min-width: 1000px) {
		.woocommerce table.form-table input[type=email], .woocommerce table.form-table input[type=number], .woocommerce table.form-table input[type=text]{
			min-width: 250px;
		}
	}
</style>

<?php
$wooglop_settings = array(
	array(
		'title' => '',
		'type'  => 'title',
		'id'    => 'glop_title',
	),
	array(
		'type'  => 'hidden',
		'id'    => 'glop_activar_servicio',
		'value' => '0',
	),
	array(
		'title' => __( 'Activar servicio', 'wooglop' ),
		'desc'  => 'Activar',
		'id'    => 'glop_activar_servicio',
		'type'  => 'checkbox',
	),
	array(
		'title' => __( 'Usuario', 'wooglop' ),
		'id'    => 'glop_usuario',
		'type'  => 'text',
	),
	array(
		'title' => __( 'Contraseña', 'wooglop' ),
		'id'    => 'glop_pass',
		'type'  => 'text',
	),
	array(
		'title' => __( 'Clave Privada', 'wooglop' ),
		'id'    => 'glop_key',
		'type'  => 'text',
	),
	array(
		'type'  => 'hidden',
		'id'    => 'glop_activar_debug',
		'value' => '0',
	),
	array(
		'title' => __( 'Activar Modo Depuración', 'wooglop' ),
		'desc'  => 'Activar',
		'id'    => 'glop_activar_debug',
		'type'  => 'checkbox',
	),
	array(
		'type'  => 'sectionend',
		'id'    => 'fin_section',
		'title' => '',
	),
);

Wooglop_Admin_Settings::output_fields( $wooglop_settings );
?>

<?php if ( class_exists( 'WooCommerce' ) && false ) : ?>
<br />
<h2><?php esc_html_e( 'Información del módulo', 'wooglop' ); ?></h2>
<table class="widefat striped">
	<tbody>
		<tr class="importer-item">
			<td class="import-system">
				<span class="importer-title"><?php esc_html_e( 'Actualizador', 'wooglop' ); ?></span>
			</td>
			<td class="desc">
				<span class="importer-desc"><a target="_blank" href="<?php echo esc_url( home_url( '/' ) ); ?>wp-json/glop-api/v1/productos"><?php echo esc_url( home_url( '/' ) ); ?>wp-json/glop-api/v1/productos</a></span>
			</td>
		</tr>
		<tr class="importer-item">
			<td class="import-system">
				<span class="importer-title"><?php esc_html_e( 'Obtener Productos', 'wooglop' ); ?></span>
			</td>
			<td class="desc">
				<span class="importer-desc"><a target="_blank" href="<?php echo esc_url( home_url( '/' ) ); ?>wp-json/glop-api/v1/productos/[referencia]"><?php echo esc_url( home_url( '/' ) ); ?>wp-json/glop-api/v1/productos/[referencia]</a></span>
			</td>
		</tr>
		<tr class="importer-item">
			<td class="import-system">
				<span class="importer-title"><?php esc_html_e( 'Versión', 'wooglop' ); ?></span>
			</td>
			<td class="desc">
				<span class="importer-desc"><a target="_blank" href="<?php echo esc_url( home_url( '/' ) ); ?>wp-json/glop-api/v1/version"><?php echo esc_url( home_url( '/' ) ); ?>wp-json/glop-api/v1/version</a></span>
			</td>
		</tr>
		<tr class="importer-item">
			<td class="import-system">
				<span class="importer-title"><?php esc_html_e( 'Pedidos', 'wooglop' ); ?></span>
			</td>
			<td class="desc">
				<span class="importer-desc"><a target="_blank" href="<?php echo esc_url( home_url( '/' ) ); ?>wp-json/glop-api/v1/pedidos"><?php echo esc_url( home_url( '/' ) ); ?>wp-json/glop-api/v1/pedidos</a></span>
			</td>
		</tr>
		<tr class="importer-item">
			<td class="import-system">
				<span class="importer-title"><?php esc_html_e( 'Impuestos', 'wooglop' ); ?></span>
			</td>
			<td class="desc">
				<span class="importer-desc"><a target="_blank" href="<?php echo esc_url( home_url( '/' ) ); ?>wp-json/glop-api/v1/impuestos"><?php echo esc_url( home_url( '/' ) ); ?>wp-json/glop-api/v1/impuestos</a></span>
			</td>
		</tr>
	</tbody>
</table>

	<div class="row">
		<div class="col-xs-12">
			<?php
			// Métodos de pago!
			$wooglop_settings = array(
				array(
					'title' => __( 'Métodos de pago <=> Glop', 'wooglop' ),
					'type'  => 'title',
					'id'    => 'glop_title',
				),
			);

			foreach ( Wooglop_Admin::get_payments_methods() as $wooglop_key_payment => $wooglop_payments_method ) {
				$wooglop_settings[] =
					array(
						'title' => $wooglop_payments_method->title,
						'id'    => 'glop_payment[' . Wooglop_Admin_Settings::sanitize_name_input( $wooglop_key_payment ) . ']',
						'type'  => 'text',
					);
			}

			$wooglop_settings[] =
				array(
					'type'  => 'sectionend',
					'id'    => 'fin_section',
					'title' => '',
				);


			Wooglop_Admin_Settings::output_fields( $wooglop_settings );
			?>
		</div>
		<?php if ( wc_shipping_enabled() ) : ?>
			<div class="col-xs-12">
			<?php
			// Métodos de envío!
			$wooglop_settings = array(
				array(
					'title' => __( 'Métodos de envío <=> Glop', 'wooglop' ),
					'type'  => 'title',
					'id'    => 'glop_title',
				),
			);

			foreach ( Wooglop_Admin::get_shipment_methods() as $wooglop_key_shipment => $wooglop_shipment_method ) {
				$wooglop_settings[] =
					array(
						'title' => $wooglop_shipment_method->method_title,
						'id'    => 'glop_shipment[' . Wooglop_Admin_Settings::sanitize_name_input( $wooglop_key_shipment ) . ']',
						'type'  => 'text',
					);
			}

			$wooglop_settings[] =
				array(
					'type'  => 'sectionend',
					'id'    => 'fin_section',
					'title' => '',
				);

			Wooglop_Admin_Settings::output_fields( $wooglop_settings );
			?>
			</div>
		<?php endif; ?>

		<?php if ( wc_tax_enabled() ) : ?>
			<div class="col-xs-12">
			<?php
			// Impuestos!
			$wooglop_settings = array(
				array(
					'title' => __( 'Impuestos <=> Glop', 'wooglop' ),
					'type'  => 'title',
					'id'    => 'glop_title',
				),
			);

			foreach ( Wooglop_Admin::get_tax_methods() as $wooglop_key_tax => $wooglop_tax_method ) {
				$wooglop_settings[] =
					array(
						'title' => $wooglop_tax_method,
						'id'    => 'glop_tax[' . Wooglop_Admin_Settings::sanitize_name_input( $wooglop_key_tax ) . ']',
						'type'  => 'text',
					);
			}

			$wooglop_settings[] =
				array(
					'type'  => 'sectionend',
					'id'    => 'fin_section',
					'title' => '',
				);

			Wooglop_Admin_Settings::output_fields( $wooglop_settings );
			?>
			</div><?php endif; ?>
	</div>
<?php endif; ?>

<?php require WOOGLOP_PLUGIN_ADMIN_TPL_PATH . 'includes/glop-admin-footer.php'; ?>
