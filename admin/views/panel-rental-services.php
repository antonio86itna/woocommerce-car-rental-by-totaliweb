<?php
/**
 * Rental Services Panel Template
 *
 * @package WooCommerce_Car_Rental
 * @subpackage Admin/Views
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get saved data
$services = get_post_meta( $product_id, '_rental_services', true ) ?: array();
$currency = get_woocommerce_currency_symbol();
?>

<div id="rental_services_data" class="panel woocommerce_options_panel hidden">
    <div class="options_group">
        <h3><?php esc_html_e( 'Extra Services', 'woocommerce-car-rental' ); ?></h3>
        
        <p class="form-field">
            <label><?php esc_html_e( 'Available Services', 'woocommerce-car-rental' ); ?></label>
            <span class="description"><?php esc_html_e( 'Define additional services that customers can add to their rental.', 'woocommerce-car-rental' ); ?></span>
        </p>

        <div id="rental_services_container" class="wcrbtw-repeater-container">
            <table class="widefat wcrbtw-repeater-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Service Name', 'woocommerce-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Price Type', 'woocommerce-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Price', 'woocommerce-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'woocommerce-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Enabled', 'woocommerce-car-rental' ); ?></th>
                        <th width="50"><?php esc_html_e( 'Actions', 'woocommerce-car-rental' ); ?></th>
                    </tr>
                </thead>
                <tbody id="rental_services_tbody">
                    <?php
                    if ( empty( $services ) ) {
                        $services = array( array() );
                    }
                    
                    foreach ( $services as $index => $service ) :
                    ?>
                    <tr class="wcrbtw-repeater-row">
                        <td>
                            <input type="text" 
                                   name="_rental_services[<?php echo esc_attr( $index ); ?>][name]" 
                                   value="<?php echo esc_attr( $service['name'] ?? '' ); ?>" 
                                   placeholder="<?php esc_attr_e( 'e.g., GPS Navigation', 'woocommerce-car-rental' ); ?>" />
                        </td>
                        <td>
                            <select name="_rental_services[<?php echo esc_attr( $index ); ?>][price_type]">
                                <option value="flat" <?php selected( ( $service['price_type'] ?? 'flat' ), 'flat' ); ?>>
                                    <?php esc_html_e( 'Flat Rate', 'woocommerce-car-rental' ); ?>
                                </option>
                                <option value="daily" <?php selected( ( $service['price_type'] ?? '' ), 'daily' ); ?>>
                                    <?php esc_html_e( 'Per Day', 'woocommerce-car-rental' ); ?>
                                </option>
                            </select>
                        </td>
                        <td>
                            <input type="text" 
                                   class="wc_input_price" 
                                   name="_rental_services[<?php echo esc_attr( $index ); ?>][price]" 
                                   value="<?php echo esc_attr( wc_format_localized_price( $service['price'] ?? '' ) ); ?>" 
                                   placeholder="<?php echo esc_attr( $currency ); ?>" />
                        </td>
                        <td>
                            <textarea 
                                name="_rental_services[<?php echo esc_attr( $index ); ?>][description]" 
                                rows="2" 
                                cols="30" 
                                placeholder="<?php esc_attr_e( 'Service description...', 'woocommerce-car-rental' ); ?>"><?php 
                                echo esc_textarea( $service['description'] ?? '' ); 
                            ?></textarea>
                        </td>
                        <td>
                            <input type="checkbox" 
                                   name="_rental_services[<?php echo esc_attr( $index ); ?>][enabled]" 
                                   value="1" 
                                   <?php checked( ( $service['enabled'] ?? 'no' ) === 'yes' ); ?> />
                        </td>
                        <td>
                            <button type="button" class="button wcrbtw-remove-row">
                                <?php esc_html_e( 'Remove', 'woocommerce-car-rental' ); ?>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <p class="toolbar">
                <button type="button" id="add_service" class="button button-primary wcrbtw-add-row">
                    <?php esc_html_e( 'Add Service', 'woocommerce-car-rental' ); ?>
                </button>
            </p>
        </div>
    </div>

    <div class="options_group">
        <h3><?php esc_html_e( 'Service Conditions', 'woocommerce-car-rental' ); ?></h3>
        
        <p class="description">
            <?php esc_html_e( 'Additional conditions or requirements for services can be configured here in future updates.', 'woocommerce-car-rental' ); ?>
        </p>
    </div>
</div>

<script type="text/template" id="tmpl-service-row">
    <tr class="wcrbtw-repeater-row">
        <td>
            <input type="text" 
                   name="_rental_services[{{data.index}}][name]" 
                   placeholder="<?php esc_attr_e( 'e.g., GPS Navigation', 'woocommerce-car-rental' ); ?>" />
        </td>
        <td>
            <select name="_rental_services[{{data.index}}][price_type]">
                <option value="flat"><?php esc_html_e( 'Flat Rate', 'woocommerce-car-rental' ); ?></option>
                <option value="daily"><?php esc_html_e( 'Per Day', 'woocommerce-car-rental' ); ?></option>
            </select>
        </td>
        <td>
            <input type="text" 
                   class="wc_input_price" 
                   name="_rental_services[{{data.index}}][price]" 
                   placeholder="<?php echo esc_attr( $currency ); ?>" />
        </td>
        <td>
            <textarea 
                name="_rental_services[{{data.index}}][description]" 
                rows="2" 
                cols="30" 
                placeholder="<?php esc_attr_e( 'Service description...', 'woocommerce-car-rental' ); ?>"></textarea>
        </td>
        <td>
            <input type="checkbox" 
                   name="_rental_services[{{data.index}}][enabled]" 
                   value="1" 
                   checked />
        </td>
        <td>
            <button type="button" class="button wcrbtw-remove-row">
                <?php esc_html_e( 'Remove', 'woocommerce-car-rental' ); ?>
            </button>
        </td>
    </tr>
</script>
