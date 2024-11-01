<?php
/**
 * Glop Admin Footer
 *
 * @author   Daniel Ruiz
 * @category Admin
 * @package GLOP/wooglop
 * @since    1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
		<p class="submit">
			<input name="save" class="button-primary woocommerce-save-button" type="submit" value="Guardar cambios" />
			<?php wp_nonce_field( 'glop-settings' ); ?>
		</p>
	</form>
</div><!-- /glop-admin -->
