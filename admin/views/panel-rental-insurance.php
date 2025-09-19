<?php
/**
 * Rental Insurance Panel Template
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
$insurance_options = get_post_meta( $product_id, '_rental_insurance', true ) ?: array();
$currency = get_woocommerce_currency_symbol();
?>

<div id="rental_insurance_data" class="panel woocommerce_options_panel show_if_rental_vehicle hidden">
    <div class="options_group">
        <h3><?php esc_html_e( 'Insurance Options', 'woocommerce-car-rental' ); ?></h3>
        
        <p class="form-field">
            <label><?php esc_html_e( 'Available Insurance Plans', 'woocommerce-car-rental' ); ?></label>
            <span class="description"><?php esc_html_e( 'Define insurance options that customers can choose for their rental.', 'woocommerce-car-rental' ); ?></span>
        </p>

        <div id="rental_insurance_container" class="wcrbtw-repeater-container">
            <table class="widefat wcrbtw-repeater-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Insurance Name', 'woocommerce-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Cost Type', 'woocommerce-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Cost', 'woocommerce-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Deductible', 'woocommerce-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'woocommerce-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Enabled', 'woocommerce-car-rental' ); ?></th>
                        <th width="50"><?php esc_html_e( 'Actions', 'woocommerce-car-rental' ); ?></th>
                    </tr>
                </thead>
                <tbody id="rental_insurance_tbody">
                    <?php
                    if ( empty( $insurance_options ) ) {
                        // Default insurance options
                        $insurance_options = array(
                            array(
                                'name' => __( 'Basic Coverage', 'woocommerce-car-rental' ),
                                'cost_type' => 'daily',
                                'cost' => '',
                                'deductible' => '',
                                'description' => '',
                                'enabled' => 'yes'
                            )
                        );
                    }
                    
                    foreach ( $insurance_options as $index => $insurance ) :
                    ?>
                    <tr class="wcrbtw-repeater-row">
                        <td>
                            <input type="text" 
                                   name="_rental_insurance[<?php echo esc_attr( $index ); ?>][name]" 
                                   value="<?php echo esc_attr( $insurance['name'] ?? '' ); ?>" 
                                   placeholder="<?php esc_attr_e( 'e.g., Full Coverage', 'woocommerce-car-rental' ); ?>" />
                        </td>
                        <td>
                            <select name="_rental_insurance[<?php echo esc_attr( $index ); ?>][cost_type]">
                                <option value="daily" <?php selected( ( $insurance['cost_type'] ?? 'daily' ), 'daily' ); ?>>
                                    <?php esc_html_e( 'Per Day', 'woocommerce-car-rental' ); ?>
                                </option>
                                <option value="flat" <?php selected( ( $insurance['cost_type'] ?? '' ), 'flat' ); ?>>
                                    <?php esc_html_e( 'Flat Rate', 'woocommerce-car-rental' ); ?>
                                </option>
                                <option value="percentage" <?php selected( ( $insurance['cost_type'] ?? '' ), 'percentage' ); ?>>
                                    <?php esc_html_e( 'Percentage', 'woocommerce-car-rental' ); ?>
                                </option>
                            </select>
                        </td>
                        <td>
                            <input type="text" 
                                   class="wc_input_price" 
                                   name="_rental_insurance[<?php echo esc_attr( $index ); ?>][cost]" 
                                   value="<?php echo esc_attr( wc_format_localized_price( $insurance['cost'] ?? '' ) ); ?>" 
                                   placeholder="<?php echo esc_attr( $currency ); ?>" />
                        </td>
                        <td>
                            <input type="text" 
                                   class="wc_input_price" 
                                   name="_rental_insurance[<?php echo esc_attr( $index ); ?>][deductible]" 
                                   value="<?php echo esc_attr( wc_format_localized_price( $insurance['deductible'] ?? '' ) ); ?>" 
                                   placeholder="<?php echo esc_attr( $currency ); ?>" 
                                   title="<?php esc_attr_e( 'Deductible amount', 'woocommerce-car-rental' ); ?>" />
                        </td>
                        <td>
                            <textarea 
                                name="_rental_insurance[<?php echo esc_attr( $index ); ?>][description]" 
                                rows="2" 
                                cols="30" 
                                placeholder="<?php esc_attr_e( 'Coverage details...', 'woocommerce-car-rental' ); ?>"><?php 
                                echo esc_textarea( $insurance['description'] ?? '' ); 
                            ?></textarea>
                        </td>
                        <td>
                            <input type="checkbox" 
                                   name="_rental_insurance[<?php echo esc_attr( $index ); ?>][enabled]" 
                                   value="1" 
                                   <?php checked( ( $insurance['enabled'] ?? 'no' ) === 'yes' ); ?> />
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
                <button type="button" id="add_insurance" class="button button-primary wcrbtw-add-row">
                    <?php esc_html_e( 'Add Insurance Option', 'woocommerce-car-rental' ); ?>
                </button>
            </p>
        </div>
    </div>

    <div class="options_group">
        <h3><?php esc_html_e( 'Insurance Notes', 'woocommerce-car-rental' ); ?></h3>
        
        <p class="description">
            <?php esc_html_e( 'Insurance options will be presented to customers during checkout. Each option can include different coverage levels and deductibles.', 'woocommerce-car-rental' ); ?>
        </p>
        
        <p class="form-field">
            <span class="woocommerce-help-tip" data-tip="<?php esc_attr_e( 'Cost Type: Per Day charges are multiplied by rental days. Flat Rate is a one-time charge. Percentage is based on total rental cost.', 'woocommerce-car-rental' ); ?>"></span>
            <span class="description">
                <?php esc_html_e( 'Configure insurance plans with different coverage levels to offer customers choice and protection.', 'woocommerce-car-rental' ); ?>
            </span>
        </p>
    </div>
</div>

<script type="text/template" id="tmpl-insurance-row">
    <tr class="wcrbtw-repeater-row">
        <td>
            <input type="text" 
                   name="_rental_insurance[{{data.index}}][name]" 
                   placeholder="<?php esc_attr_e( 'e.g., Full Coverage', 'woocommerce-car-rental' ); ?>" />
        </td>
        <td>
            <select name="_rental_insurance[{{data.index}}][cost_type]">
                <option value="daily"><?php esc_html_e( 'Per Day', 'woocommerce-car-rental' ); ?></option>
                <option value="flat"><?php esc_html_e( 'Flat Rate', 'woocommerce-car-rental' ); ?></option>
                <option value="percentage"><?php esc_html_e( 'Percentage', 'woocommerce-car-rental' ); ?></option>
            </select>
        </td>
        <td>
            <input type="text" 
                   class="wc_input_price" 
                   name="_rental_insurance[{{data.index}}][cost]" 
                   placeholder="<?php echo esc_attr( $currency ); ?>" />
        </td>
        <td>
            <input type="text" 
                   class="wc_input_price" 
                   name="_rental_insurance[{{data.index}}][deductible]" 
                   placeholder="<?php echo esc_attr( $currency ); ?>" 
                   title="<?php esc_attr_e( 'Deductible amount', 'woocommerce-car-rental' ); ?>" />
        </td>
        <td>
            <textarea 
                name="_rental_insurance[{{data.index}}][description]" 
                rows="2" 
                cols="30" 
                placeholder="<?php esc_attr_e( 'Coverage details...', 'woocommerce-car-rental' ); ?>"></textarea>
        </td>
        <td>
            <input type="checkbox" 
                   name="_rental_insurance[{{data.index}}][enabled]" 
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
