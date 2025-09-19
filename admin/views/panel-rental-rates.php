<?php
/**
 * Rental Rates Panel Template
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
$rates = get_post_meta( $product_id, '_rental_rates', true ) ?: array();
$currency = get_woocommerce_currency_symbol();
?>

<div id="rental_rates_data" class="panel woocommerce_options_panel hidden">
    <div class="options_group">
        <h3><?php esc_html_e( 'Base Pricing', 'woocommerce-car-rental' ); ?></h3>
        
        <?php
        // Base daily rate
        woocommerce_wp_text_input( array(
            'id'          => '_rental_base_daily_rate',
            'label'       => sprintf( __( 'Base Daily Rate (%s)', 'woocommerce-car-rental' ), $currency ),
            'type'        => 'text',
            'class'       => 'wc_input_price',
            'value'       => wc_format_localized_price( $rates['base_daily_rate'] ?? '' ),
            'data_type'   => 'price',
            'desc_tip'    => true,
            'description' => __( 'Base rental price per day.', 'woocommerce-car-rental' ),
        ) );
        ?>
    </div>

    <div class="options_group">
        <h3><?php esc_html_e( 'Seasonal/Dynamic Rates', 'woocommerce-car-rental' ); ?></h3>
        
        <p class="form-field">
            <label><?php esc_html_e( 'Seasonal Rate Rules', 'woocommerce-car-rental' ); ?></label>
            <span class="description"><?php esc_html_e( 'Define special rates for specific periods (holidays, seasons, etc.).', 'woocommerce-car-rental' ); ?></span>
        </p>

        <div id="rental_seasonal_rates_container" class="wcrbtw-repeater-container">
            <table class="widefat wcrbtw-repeater-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Name', 'woocommerce-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Start Date', 'woocommerce-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'End Date', 'woocommerce-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Daily Rate', 'woocommerce-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Priority', 'woocommerce-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Recurring', 'woocommerce-car-rental' ); ?></th>
                        <th width="50"><?php esc_html_e( 'Actions', 'woocommerce-car-rental' ); ?></th>
                    </tr>
                </thead>
                <tbody id="rental_seasonal_rates_tbody">
                    <?php
                    $seasonal_rates = $rates['seasonal_rates'] ?? array();
                    if ( empty( $seasonal_rates ) ) {
                        $seasonal_rates = array( array() ); // Start with one empty row
                    }
                    
                    foreach ( $seasonal_rates as $index => $rate ) :
                    ?>
                    <tr class="wcrbtw-repeater-row">
                        <td>
                            <input type="text" 
                                   name="_rental_seasonal_rates[<?php echo esc_attr( $index ); ?>][name]" 
                                   value="<?php echo esc_attr( $rate['name'] ?? '' ); ?>" 
                                   placeholder="<?php esc_attr_e( 'e.g., Summer Season', 'woocommerce-car-rental' ); ?>" />
                        </td>
                        <td>
                            <input type="date" 
                                   name="_rental_seasonal_rates[<?php echo esc_attr( $index ); ?>][start_date]" 
                                   value="<?php echo esc_attr( $rate['start_date'] ?? '' ); ?>" />
                        </td>
                        <td>
                            <input type="date" 
                                   name="_rental_seasonal_rates[<?php echo esc_attr( $index ); ?>][end_date]" 
                                   value="<?php echo esc_attr( $rate['end_date'] ?? '' ); ?>" />
                        </td>
                        <td>
                            <input type="text" 
                                   class="wc_input_price" 
                                   name="_rental_seasonal_rates[<?php echo esc_attr( $index ); ?>][rate]" 
                                   value="<?php echo esc_attr( wc_format_localized_price( $rate['rate'] ?? '' ) ); ?>" 
                                   placeholder="<?php echo esc_attr( $currency ); ?>" />
                        </td>
                        <td>
                            <input type="number" 
                                   name="_rental_seasonal_rates[<?php echo esc_attr( $index ); ?>][priority]" 
                                   value="<?php echo esc_attr( $rate['priority'] ?? '0' ); ?>" 
                                   min="0" 
                                   step="1" 
                                   style="width: 60px;" />
                        </td>
                        <td>
                            <input type="checkbox" 
                                   name="_rental_seasonal_rates[<?php echo esc_attr( $index ); ?>][recurring]" 
                                   value="1" 
                                   <?php checked( ( $rate['recurring'] ?? 'no' ) === 'yes' ); ?> 
                                   title="<?php esc_attr_e( 'Repeat annually', 'woocommerce-car-rental' ); ?>" />
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
                <button type="button" id="add_seasonal_rate" class="button button-primary wcrbtw-add-row">
                    <?php esc_html_e( 'Add Seasonal Rate', 'woocommerce-car-rental' ); ?>
                </button>
                <span class="description" style="margin-left: 10px;">
                    <?php esc_html_e( 'Higher priority rates override lower ones for overlapping dates.', 'woocommerce-car-rental' ); ?>
                </span>
            </p>
        </div>
    </div>
</div>

<script type="text/template" id="tmpl-seasonal-rate-row">
    <tr class="wcrbtw-repeater-row">
        <td>
            <input type="text" 
                   name="_rental_seasonal_rates[{{data.index}}][name]" 
                   placeholder="<?php esc_attr_e( 'e.g., Summer Season', 'woocommerce-car-rental' ); ?>" />
        </td>
        <td>
            <input type="date" 
                   name="_rental_seasonal_rates[{{data.index}}][start_date]" />
        </td>
        <td>
            <input type="date" 
                   name="_rental_seasonal_rates[{{data.index}}][end_date]" />
        </td>
        <td>
            <input type="text" 
                   class="wc_input_price" 
                   name="_rental_seasonal_rates[{{data.index}}][rate]" 
                   placeholder="<?php echo esc_attr( $currency ); ?>" />
        </td>
        <td>
            <input type="number" 
                   name="_rental_seasonal_rates[{{data.index}}][priority]" 
                   value="0" 
                   min="0" 
                   step="1" 
                   style="width: 60px;" />
        </td>
        <td>
            <input type="checkbox" 
                   name="_rental_seasonal_rates[{{data.index}}][recurring]" 
                   value="1" 
                   title="<?php esc_attr_e( 'Repeat annually', 'woocommerce-car-rental' ); ?>" />
        </td>
        <td>
            <button type="button" class="button wcrbtw-remove-row">
                <?php esc_html_e( 'Remove', 'woocommerce-car-rental' ); ?>
            </button>
        </td>
    </tr>
</script>
