<?php
/**
 * Rental Settings Panel Template
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
$settings = get_post_meta( $product_id, '_rental_settings', true ) ?: array();
$currency = get_woocommerce_currency_symbol();
?>

<div id="rental_settings_data" class="panel woocommerce_options_panel hidden">
    <div class="options_group">
        <h3><?php esc_html_e( 'Rental Duration', 'woocommerce-car-rental' ); ?></h3>
        
        <?php
        // Minimum rental days
        woocommerce_wp_text_input( array(
            'id'          => '_rental_min_days',
            'label'       => __( 'Minimum Rental Days', 'woocommerce-car-rental' ),
            'type'        => 'number',
            'value'       => $settings['min_days'] ?? '1',
            'custom_attributes' => array(
                'min'  => '1',
                'step' => '1',
            ),
            'desc_tip'    => true,
            'description' => __( 'Minimum number of days for rental.', 'woocommerce-car-rental' ),
        ) );

        // Maximum rental days
        woocommerce_wp_text_input( array(
            'id'          => '_rental_max_days',
            'label'       => __( 'Maximum Rental Days', 'woocommerce-car-rental' ),
            'type'        => 'number',
            'value'       => $settings['max_days'] ?? '30',
            'custom_attributes' => array(
                'min'  => '1',
                'step' => '1',
            ),
            'desc_tip'    => true,
            'description' => __( 'Maximum number of days for rental. Leave empty for no limit.', 'woocommerce-car-rental' ),
        ) );
        ?>
    </div>

    <div class="options_group">
        <h3><?php esc_html_e( 'Rental Rules', 'woocommerce-car-rental' ); ?></h3>
        
        <?php
        // Extra day after hour
        woocommerce_wp_text_input( array(
            'id'          => '_rental_extra_day_hour',
            'label'       => __( 'Extra Day After Hour', 'woocommerce-car-rental' ),
            'type'        => 'number',
            'value'       => $settings['extra_day_hour'] ?? '14',
            'custom_attributes' => array(
                'min'  => '0',
                'max'  => '23',
                'step' => '1',
            ),
            'desc_tip'    => true,
            'description' => __( 'After this hour (24h format), an extra day is charged. Example: 14 = 2:00 PM', 'woocommerce-car-rental' ),
        ) );
        ?>
    </div>

    <div class="options_group">
        <h3><?php esc_html_e( 'Financial Settings', 'woocommerce-car-rental' ); ?></h3>
        
        <?php
        // Security deposit
        woocommerce_wp_text_input( array(
            'id'          => '_rental_security_deposit',
            'label'       => sprintf( __( 'Security Deposit (%s)', 'woocommerce-car-rental' ), $currency ),
            'type'        => 'text',
            'class'       => 'wc_input_price',
            'value'       => wc_format_localized_price( $settings['security_deposit'] ?? '' ),
            'data_type'   => 'price',
            'desc_tip'    => true,
            'description' => __( 'Security deposit amount required for rental. This is refunded after the rental period.', 'woocommerce-car-rental' ),
        ) );
        ?>
    </div>

    <div class="options_group">
        <h3><?php esc_html_e( 'Policies', 'woocommerce-car-rental' ); ?></h3>
        
        <?php
        // Cancellation policy
        woocommerce_wp_textarea_input( array(
            'id'          => '_rental_cancellation_policy',
            'label'       => __( 'Cancellation Policy', 'woocommerce-car-rental' ),
            'value'       => $settings['cancellation_policy'] ?? '',
            'desc_tip'    => true,
            'description' => __( 'Describe the cancellation policy for this vehicle rental.', 'woocommerce-car-rental' ),
            'rows'        => 5,
            'cols'        => 40,
            'placeholder' => __( 'e.g., Free cancellation up to 24 hours before pickup. 50% refund for cancellations within 24 hours.', 'woocommerce-car-rental' ),
        ) );
        ?>
    </div>

    <div class="options_group">
        <h3><?php esc_html_e( 'Additional Settings', 'woocommerce-car-rental' ); ?></h3>
        
        <?php
        // Additional settings
        woocommerce_wp_textarea_input( array(
            'id'          => '_rental_additional_settings',
            'label'       => __( 'Additional Rules & Settings', 'woocommerce-car-rental' ),
            'value'       => $settings['additional_settings'] ?? '',
            'desc_tip'    => true,
            'description' => __( 'Any additional rules, requirements, or settings specific to this rental vehicle.', 'woocommerce-car-rental' ),
            'rows'        => 5,
            'cols'        => 40,
            'placeholder' => __( 'e.g., Driver must be 25+ years old, valid driving license required, etc.', 'woocommerce-car-rental' ),
        ) );
        ?>
    </div>

    <div class="options_group">
        <h3><?php esc_html_e( 'Quick Settings Reference', 'woocommerce-car-rental' ); ?></h3>
        
        <div class="wcrbtw-settings-summary">
            <p class="description">
                <strong><?php esc_html_e( 'Current Configuration:', 'woocommerce-car-rental' ); ?></strong>
            </p>
            <ul style="margin-left: 20px; list-style: disc;">
                <li><?php printf( 
                    esc_html__( 'Rental period: %d to %d days', 'woocommerce-car-rental' ), 
                    $settings['min_days'] ?? 1, 
                    $settings['max_days'] ?? 30 
                ); ?></li>
                <li><?php printf( 
                    esc_html__( 'Extra day charged after: %d:00', 'woocommerce-car-rental' ), 
                    $settings['extra_day_hour'] ?? 14 
                ); ?></li>
                <?php if ( ! empty( $settings['security_deposit'] ) ) : ?>
                <li><?php printf( 
                    esc_html__( 'Security deposit: %s%s', 'woocommerce-car-rental' ), 
                    $currency,
                    wc_format_localized_price( $settings['security_deposit'] )
                ); ?></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>
