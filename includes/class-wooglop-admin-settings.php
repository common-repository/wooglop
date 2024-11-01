<?php
/**
 * Glop Admin Settings
 *
 * @author   Daniel Ruiz
 * @category Admin
 * @package  GLOP/wooglop
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wooglop_Admin_Settings', false ) ) :

	/**
	 * Glop_Admin_Settings Class.
	 */
	class Wooglop_Admin_Settings {

		/**
		 * Get a setting from the settings API.
		 *
		 * @param string $option_name Option name.
		 * @param mixed  $default Default.
		 *
		 * @return mixed
		 */
		public static function get_option( $option_name, $default = '' ) {
			// Array value.
			if ( strstr( $option_name, '[' ) ) {

				parse_str( $option_name, $option_array );

				// Option name is first key.
				$option_name = current( array_keys( $option_array ) );

				// Get value.
				$option_values = get_option( $option_name, '' );

				$key = key( $option_array[ $option_name ] );

				if ( isset( $option_values[ $key ] ) ) {
					$option_value = $option_values[ $key ];
				} else {
					$option_value = null;
				}
			} else {
				$option_value = get_option( $option_name, null );
			}

			if ( is_array( $option_value ) ) {
				$option_value = array_map( 'stripslashes', $option_value );
			} elseif ( ! is_null( $option_value ) ) {
				$option_value = stripslashes( $option_value );
			}

			return ( null === $option_value ) ? $default : $option_value;
		}

		/**
		 * Output admin fields.
		 *
		 * @param array[] $options Opens array to output.
		 */
		public static function output_fields( $options ) {
			foreach ( $options as $value ) {
				if ( ! isset( $value['type'] ) ) {
					continue;
				}
				if ( ! isset( $value['id'] ) ) {
					$value['id'] = '';
				}
				if ( ! isset( $value['title'] ) ) {
					$value['title'] = isset( $value['name'] ) ? $value['name'] : '';
				}
				if ( ! isset( $value['class'] ) ) {
					$value['class'] = '';
				}
				if ( ! isset( $value['css'] ) ) {
					$value['css'] = '';
				}
				if ( ! isset( $value['default'] ) ) {
					$value['default'] = '';
				}
				if ( ! isset( $value['desc'] ) ) {
					$value['desc'] = '';
				}
				if ( ! isset( $value['desc_tip'] ) ) {
					$value['desc_tip'] = false;
				}
				if ( ! isset( $value['placeholder'] ) ) {
					$value['placeholder'] = '';
				}

				// Custom attribute handling.
				$custom_attributes = array();

				if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
					foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
						$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
					}
				}

				// Description handling.
				$field_description = self::get_field_description( $value );
				$tooltip_html      = $field_description['tooltip_html'];
				$description       = $field_description['description'];

				// Switch based on type.
				switch ( $value['type'] ) {
					// Section Titles.
					case 'title':
						if ( ! empty( $value['title'] ) ) {
							echo '<h2>' . esc_html( $value['title'] ) . '</h2>';
						}
						if ( ! empty( $value['desc'] ) ) {
							echo esc_html( wpautop( wptexturize( wp_kses_post( $value['desc'] ) ) ) );
						}
						echo '<table class="form-table">' . "\n\n";
						if ( ! empty( $value['id'] ) ) {
							do_action( 'wooglop_settings_' . sanitize_title( $value['id'] ) );
						}
						break;
					// Section Ends.
					case 'sectionend':
						if ( ! empty( $value['id'] ) ) {
							do_action( 'wooglop_settings_' . sanitize_title( $value['id'] ) . '_end' );
						}
						echo '</table>';
						if ( ! empty( $value['id'] ) ) {
							do_action( 'wooglop_settings_' . sanitize_title( $value['id'] ) . '_after' );
						}
						break;
					// Standard text inputs and subtypes like 'number'.
					case 'text':
					case 'email':
					case 'number':
					case 'password':
						$option_value = self::get_option( $value['id'], $value['default'] );
						?>
						<tr valign="top">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
								<?php echo esc_html( $tooltip_html ); ?>
							</th>
							<td class="forminp forminp-<?php echo esc_html( $value['type'] ); ?>">
								<input
									name="<?php echo esc_attr( $value['id'] ); ?>"
									id="<?php echo esc_attr( $value['id'] ); ?>"
									type="<?php echo esc_attr( $value['type'] ); ?>"
									style="<?php echo esc_attr( $value['css'] ); ?>"
									value="<?php echo esc_attr( $option_value ); ?>"
									class="<?php echo esc_attr( $value['class'] ); ?>"
									placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
									<?php echo esc_html( implode( ' ', $custom_attributes ) ); ?>
									/> <?php echo esc_html( $description ); ?>
							</td>
						</tr>
						<?php
						break;
					// Color picker.
					case 'color':
						$option_value = self::get_option( $value['id'], $value['default'] );
						?>
						<tr valign="top">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
								<?php echo esc_html( $tooltip_html ); ?>
							</th>
							<td class="forminp forminp-<?php echo esc_html( $value['type'] ); ?>">&lrm;
								<span class="colorpickpreview" style="background: <?php echo esc_attr( $option_value ); ?>"></span>
								<input
									name="<?php echo esc_attr( $value['id'] ); ?>"
									id="<?php echo esc_attr( $value['id'] ); ?>"
									type="text"
									dir="ltr"
									style="<?php echo esc_attr( $value['css'] ); ?>"
									value="<?php echo esc_attr( $option_value ); ?>"
									class="<?php echo esc_attr( $value['class'] ); ?>colorpick"
									placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
									<?php echo esc_html( implode( ' ', $custom_attributes ) ); ?>
									/>&lrm; <?php echo esc_html( $description ); ?>
									<div id="colorPickerDiv_<?php echo esc_attr( $value['id'] ); ?>" class="colorpickdiv" style="z-index: 100;background:#eee;border:1px solid #ccc;position:absolute;display:none;"></div>
							</td>
						</tr>
						<?php
						break;
					// Textarea.
					case 'textarea':
						$option_value = self::get_option( $value['id'], $value['default'] );
						?>
						<tr valign="top">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
								<?php echo esc_html( $tooltip_html ); ?>
							</th>
							<td class="forminp forminp-<?php echo esc_html( sanitize_title( $value['type'] ) ); ?>">
								<?php echo esc_html( $description ); ?>
								<textarea
									name="<?php echo esc_attr( $value['id'] ); ?>"
									id="<?php echo esc_attr( $value['id'] ); ?>"
									style="<?php echo esc_attr( $value['css'] ); ?>"
									class="<?php echo esc_attr( $value['class'] ); ?>"
									placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
									<?php echo esc_html( implode( ' ', $custom_attributes ) ); ?>
									><?php echo esc_textarea( $option_value ); ?></textarea>
							</td>
						</tr>
						<?php
						break;
					// Select boxes.
					case 'select':
					case 'multiselect':
						$option_value = self::get_option( $value['id'], $value['default'] );
						?>
						<tr valign="top">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
								<?php echo esc_html( $tooltip_html ); ?>
							</th>
							<td class="forminp forminp-<?php echo esc_html( sanitize_title( $value['type'] ) ); ?>">
								<select
									name="<?php echo esc_attr( $value['id'] ); ?><?php echo ( 'multiselect' === $value['type'] ) ? '[]' : ''; ?>"
									id="<?php echo esc_attr( $value['id'] ); ?>"
									style="<?php echo esc_attr( $value['css'] ); ?>"
									class="<?php echo esc_attr( $value['class'] ); ?>"
									<?php echo esc_html( implode( ' ', $custom_attributes ) ); ?>
									<?php echo ( 'multiselect' === $value['type'] ) ? 'multiple="multiple"' : ''; ?>
								>
									<?php
									foreach ( $value['options'] as $key => $val ) {
										?>
										<option value="<?php echo esc_attr( $key ); ?>"
										<?php
										if ( is_array( $option_value ) ) {
											selected( in_array( $key, $option_value, true ), true );
										} else {
											selected( $option_value, $key );
										}
										?>
										><?php echo esc_html( $val ); ?></option>
										<?php
									}
									?>
								</select> <?php echo esc_html( $description ); ?>
							</td>
						</tr>
						<?php
						break;
					// Radio inputs.
					case 'radio':
						$option_value = self::get_option( $value['id'], $value['default'] );
						?>
						<tr valign="top">
							<th scope="row" class="titledesc">
							<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
							<?php echo esc_html( $tooltip_html ); ?>
						</th>
						<td class="forminp forminp-<?php echo esc_html( sanitize_title( $value['type'] ) ); ?>">
						<fieldset>
							<?php echo esc_html( $description ); ?>
							<ul>
							<?php
							foreach ( $value['options'] as $key => $val ) {
								?>
								<li>
									<label><input
										name="<?php echo esc_attr( $value['id'] ); ?>"
										value="<?php echo esc_html( $key ); ?>"
										type="radio"
										style="<?php echo esc_attr( $value['css'] ); ?>"
										class="<?php echo esc_attr( $value['class'] ); ?>"
										<?php echo esc_html( implode( ' ', $custom_attributes ) ); ?>
										<?php checked( $key, $option_value ); ?>
										/> <?php echo esc_html( $val ); ?></label>
								</li>
								<?php
							}
							?>
							</ul>
						</fieldset>
						</td>
					</tr>
						<?php
						break;
					// Checkbox input.
					case 'checkbox':
						$option_value     = self::get_option( $value['id'], $value['default'] );
						$visibility_class = array();

						if ( ! isset( $value['hide_if_checked'] ) ) {
							$value['hide_if_checked'] = false;
						}
						if ( ! isset( $value['show_if_checked'] ) ) {
							$value['show_if_checked'] = false;
						}
						if ( 'yes' === $value['hide_if_checked'] || 'yes' === $value['show_if_checked'] ) {
							$visibility_class[] = 'hidden_option';
						}
						if ( 'option' === $value['hide_if_checked'] ) {
							$visibility_class[] = 'hide_options_if_checked';
						}
						if ( 'option' === $value['show_if_checked'] ) {
							$visibility_class[] = 'show_options_if_checked';
						}

						if ( ! isset( $value['checkboxgroup'] ) || 'start' === $value['checkboxgroup'] ) {
							?>
						<tr valign="top" class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
							<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ); ?></th>
							<td class="forminp forminp-checkbox">
								<fieldset>
							<?php
						} else {
							?>
						<fieldset class="<?php echo esc_attr( esc_attr( implode( ' ', $visibility_class ) ) ); ?>">
							<?php
						}
						if ( ! empty( $value['title'] ) ) {
							?>
						<legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ); ?></span></legend>
							<?php
						}
						?>
						<label for="<?php echo esc_html( $value['id'] ); ?>">
						<input
							name="<?php echo esc_attr( $value['id'] ); ?>"
							id="<?php echo esc_attr( $value['id'] ); ?>"
							type="checkbox"
							class="<?php echo esc_attr( isset( $value['class'] ) ? $value['class'] : '' ); ?>"
							value="1"
							<?php checked( $option_value, 'yes' ); ?>
							<?php echo esc_html( implode( ' ', $custom_attributes ) ); ?>
						/> <?php echo esc_html( $description ); ?>
						</label> <?php echo esc_html( $tooltip_html ); ?>
							<?php
							if ( ! isset( $value['checkboxgroup'] ) || 'end' === $value['checkboxgroup'] ) {
								?>
							</fieldset>
								</td>
							</tr>
							<?php } else { ?>
						</fieldset>
								<?php
							}
						break;
					// Image width settings.
					case 'image_width':
						$image_size       = str_replace( '_image_size', '', $value['id'] );
						$size             = wc_get_image_size( $image_size );
						$width            = isset( $size['width'] ) ? $size['width'] : $value['default']['width'];
						$height           = isset( $size['height'] ) ? $size['height'] : $value['default']['height'];
						$crop             = isset( $size['crop'] ) ? $size['crop'] : $value['default']['crop'];
						$disabled_attr    = '';
						$disabled_message = '';

						if ( has_filter( 'glop_get_image_size_' . $image_size ) ) {
							$disabled_attr    = 'disabled="disabled"';
							$disabled_message = '<p><small>' . __( 'The settings of this image size have been disabled because its values are being overwritten by a filter.', 'wooglop' ) . '</small></p>';
						}
						?>
						<tr valign="top">
							<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ); ?> <?php echo esc_html( $tooltip_html . $disabled_message ); ?></th>
							<td class="forminp image_width_settings">
								<input name="<?php echo esc_attr( $value['id'] ); ?>[width]" <?php echo esc_html( $disabled_attr ); ?> id="<?php echo esc_attr( $value['id'] ); ?>-width" type="text" size="3" value="<?php echo esc_html( $width ); ?>" /> &times; <input name="<?php echo esc_attr( $value['id'] ); ?>[height]" <?php echo esc_html( $disabled_attr ); ?> id="<?php echo esc_attr( $value['id'] ); ?>-height" type="text" size="3" value="<?php echo esc_html( $height ); ?>" />px

								<label><input name="<?php echo esc_attr( $value['id'] ); ?>[crop]" <?php echo esc_html( $disabled_attr ); ?> id="<?php echo esc_html( esc_attr( $value['id'] ) ); ?>-crop" type="checkbox" value="1" <?php checked( 1, $crop ); ?> /> <?php esc_html_e( 'Hard crop?', 'wooglop' ); ?></label>
							</td>
						</tr>
						<?php
						break;
					// Single page selects.
					case 'single_select_page':
						$args = array(
							'name'             => $value['id'],
							'id'               => $value['id'],
							'sort_column'      => 'menu_order',
							'sort_order'       => 'ASC',
							'show_option_none' => ' ',
							'class'            => $value['class'],
							'echo'             => false,
							'selected'         => absint( self::get_option( $value['id'] ) ),
						);

						if ( isset( $value['args'] ) ) {
							$args = wp_parse_args( $value['args'], $args );
						}

						?>
						<tr valign="top" class="single_select_page">
							<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ); ?> <?php echo esc_html( $tooltip_html ); ?></th>
							<td class="forminp">
								<?php echo esc_html( str_replace( ' id=', " data-placeholder='" . esc_attr__( 'Select a page&hellip;', 'wooglop' ) . "' style='" . esc_html( $value['css'] ) . "' class='" . esc_html( $value['class'] ) . "' id=", wp_dropdown_pages( $args ) ) ); ?> <?php echo esc_html( $description ); ?>
							</td>
						</tr>
						<?php
						break;
					// Single country selects.
					case 'single_select_country':
						$country_setting = (string) self::get_option( $value['id'] );

						if ( strstr( $country_setting, ':' ) ) {
							$country_setting = explode( ':', $country_setting );
							$country         = current( $country_setting );
							$state           = end( $country_setting );
						} else {
							$country = $country_setting;
							$state   = '*';
						}
						?>
						<tr valign="top">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
								<?php echo esc_html( $tooltip_html ); ?>
							</th>
							<td class="forminp">
								<select name="<?php echo esc_attr( $value['id'] ); ?>" style="<?php echo esc_attr( $value['css'] ); ?>" data-placeholder="<?php esc_attr_e( 'Choose a country&hellip;', 'wooglop' ); ?>" aria-label="<?php esc_attr_e( 'Country', 'wooglop' ); ?>" class="wc-enhanced-select">
								<?php WC()->countries->country_dropdown_options( $country, $state ); ?>
								</select> <?php echo esc_html( $description ); ?>
							</td>
						</tr>
						<?php
						break;
					// Country multiselects.
					case 'multi_select_countries':
						$selections = (array) self::get_option( $value['id'] );

						if ( ! empty( $value['options'] ) ) {
							$countries = $value['options'];
						} else {
							$countries = WC()->countries->countries;
						}

						asort( $countries );
						?>
						<tr valign="top">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
								<?php echo esc_html( $tooltip_html ); ?>
							</th>
							<td class="forminp">
								<select multiple="multiple" name="<?php echo esc_attr( $value['id'] ); ?>[]" style="width:350px" data-placeholder="<?php esc_attr_e( 'Choose countries&hellip;', 'wooglop' ); ?>" aria-label="<?php esc_attr_e( 'Country', 'wooglop' ); ?>" class="wc-enhanced-select">
								<?php
								if ( ! empty( $countries ) ) {
									foreach ( $countries as $key => $val ) {
										echo '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $selections, true ), true, false ) . '>' . esc_html( $val ) . '</option>';
									}
								}
								?>
								</select> <?php echo ( $description ) ? esc_html( $description ) : ''; ?> <br /><a class="select_all button" href="#"><?php esc_html_e( 'Select all', 'wooglop' ); ?></a> <a class="select_none button" href="#"><?php esc_html_e( 'Select none', 'wooglop' ); ?></a>
							</td>
						</tr>
						<?php
						break;
					// Default: run an action.
					default:
						do_action( 'wooglop_admin_field_' . $value['type'], $value );
						break;
				}
			}
		}

		/**
		 * Helper function to get the formatted description and tip HTML for a
		 * given form field. Plugins can call this when implementing their own custom
		 * settings types.
		 *
		 * @param  array $value The form field value array.
		 * @return array The description and tip as a 2 element array
		 */
		public static function get_field_description( $value ) {
			$description  = '';
			$tooltip_html = '';

			if ( true === $value['desc_tip'] ) {
				$tooltip_html = $value['desc'];
			} elseif ( ! empty( $value['desc_tip'] ) ) {
				$description  = $value['desc'];
				$tooltip_html = $value['desc_tip'];
			} elseif ( ! empty( $value['desc'] ) ) {
				$description = $value['desc'];
			}

			if ( $description && in_array( $value['type'], array( 'textarea', 'radio' ), true ) ) {
				$description = '<p style="margin-top:0">' . wp_kses_post( $description ) . '</p>';
			} elseif ( $description && in_array( $value['type'], array( 'checkbox' ), true ) ) {
				$description = wp_kses_post( $description );
			} elseif ( $description ) {
				$description = '<span class="description">' . wp_kses_post( $description ) . '</span>';
			}

			if ( $tooltip_html && in_array( $value['type'], array( 'checkbox' ), true ) ) {
				$tooltip_html = '<p class="description">' . $tooltip_html . '</p>';
			} elseif ( $tooltip_html ) {
				$tooltip_html = wc_help_tip( $tooltip_html );
			}

			return array(
				'description'  => $description,
				'tooltip_html' => $tooltip_html,
			);
		}

		/**
		 * Get Hidden Field.
		 *
		 * @param string $field Field.
		 */
		public static function get_hidden_field( $field ) {
			echo "<input type='" . esc_html( $field['type'] ) . "' name='" . esc_html( $field['id'] ) . "' value='" . esc_html( $field['value'] ) . "'' />";
		}

		/**
		 * Sanitize name input.
		 *
		 * @param string $value Value.
		 *
		 * @return mixed|null
		 */
		public static function sanitize_name_input( $value ) {
			$value_sanitized = strtolower( str_replace( ' ', '-', $value ) );

			return apply_filters( 'wooglop_sanitize_name_input', $value, $value_sanitized );
		}
	}

endif;
