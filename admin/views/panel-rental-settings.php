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

// Helper to normalize stored meta values into arrays.
if ( ! function_exists( 'wcrbtw_maybe_decode_meta_array' ) ) {
    /**
     * Attempt to decode a stored meta value into an array.
     *
     * @param mixed $value Stored meta value.
     * @return array
     */
    function wcrbtw_maybe_decode_meta_array( $value ): array {
        if ( empty( $value ) && '0' !== $value ) {
            return array();
        }

        if ( is_array( $value ) ) {
            return $value;
        }

        if ( is_string( $value ) ) {
            $trimmed_value = trim( $value );

            if ( '' === $trimmed_value ) {
                return array();
            }

            $decoded = json_decode( $trimmed_value, true );
            if ( is_array( $decoded ) ) {
                return $decoded;
            }

            if ( function_exists( 'maybe_unserialize' ) ) {
                $unserialized = maybe_unserialize( $value );
                if ( is_array( $unserialized ) ) {
                    return $unserialized;
                }
            }

            $lines = array_filter(
                array_map( 'trim', preg_split( '/[\r\n]+/', $value ) )
            );

            if ( ! empty( $lines ) ) {
                return array_values( $lines );
            }
        }

        return array();
    }
}

// Retrieve settings from new meta keys, with legacy fallbacks.
$settings_meta_exists = array(
    'min_days'            => metadata_exists( 'post', $product_id, '_wcrbtw_min_days' ),
    'max_days'            => metadata_exists( 'post', $product_id, '_wcrbtw_max_days' ),
    'extra_day_hour'      => metadata_exists( 'post', $product_id, '_wcrbtw_extra_day_hour' ),
    'security_deposit'    => metadata_exists( 'post', $product_id, '_wcrbtw_security_deposit' ),
    'cancellation_policy' => metadata_exists( 'post', $product_id, '_wcrbtw_cancellation_policy' ),
    'additional_settings' => metadata_exists( 'post', $product_id, '_wcrbtw_additional_settings' ),
);

$settings = array(
    'min_days'            => $settings_meta_exists['min_days'] ? get_post_meta( $product_id, '_wcrbtw_min_days', true ) : null,
    'max_days'            => $settings_meta_exists['max_days'] ? get_post_meta( $product_id, '_wcrbtw_max_days', true ) : null,
    'extra_day_hour'      => $settings_meta_exists['extra_day_hour'] ? get_post_meta( $product_id, '_wcrbtw_extra_day_hour', true ) : null,
    'security_deposit'    => $settings_meta_exists['security_deposit'] ? get_post_meta( $product_id, '_wcrbtw_security_deposit', true ) : null,
    'cancellation_policy' => $settings_meta_exists['cancellation_policy'] ? get_post_meta( $product_id, '_wcrbtw_cancellation_policy', true ) : null,
    'additional_settings' => $settings_meta_exists['additional_settings'] ? get_post_meta( $product_id, '_wcrbtw_additional_settings', true ) : null,
);

$legacy_settings = wcrbtw_maybe_decode_meta_array( get_post_meta( $product_id, '_rental_settings', true ) );

foreach ( $settings_meta_exists as $key => $meta_exists ) {
    if ( ! $meta_exists && isset( $legacy_settings[ $key ] ) ) {
        $legacy_value     = $legacy_settings[ $key ];
        $settings[ $key ] = is_scalar( $legacy_value ) ? (string) $legacy_value : null;
    }
}

// Additional fallbacks for individual legacy meta keys.
if ( ! $settings_meta_exists['min_days'] && null === $settings['min_days'] ) {
    $legacy_min_days = get_post_meta( $product_id, '_rental_min_days', true );
    if ( '' !== $legacy_min_days ) {
        $settings['min_days'] = $legacy_min_days;
    }
}

if ( ! $settings_meta_exists['max_days'] && null === $settings['max_days'] ) {
    $legacy_max_days = get_post_meta( $product_id, '_rental_max_days', true );
    if ( '' !== $legacy_max_days ) {
        $settings['max_days'] = $legacy_max_days;
    }
}

if ( ! $settings_meta_exists['extra_day_hour'] && null === $settings['extra_day_hour'] ) {
    $legacy_extra_day_hour = get_post_meta( $product_id, '_rental_extra_day_hour', true );
    if ( '' !== $legacy_extra_day_hour ) {
        $settings['extra_day_hour'] = $legacy_extra_day_hour;
    }
}

if ( ! $settings_meta_exists['security_deposit'] && null === $settings['security_deposit'] ) {
    $legacy_security_deposit = get_post_meta( $product_id, '_rental_security_deposit', true );
    if ( '' !== $legacy_security_deposit ) {
        $settings['security_deposit'] = $legacy_security_deposit;
    }
}

if ( ! $settings_meta_exists['cancellation_policy'] && null === $settings['cancellation_policy'] ) {
    $legacy_cancellation_policy = get_post_meta( $product_id, '_rental_cancellation_policy', true );
    if ( '' !== $legacy_cancellation_policy ) {
        $settings['cancellation_policy'] = $legacy_cancellation_policy;
    }
}

if ( ! $settings_meta_exists['additional_settings'] && null === $settings['additional_settings'] ) {
    $legacy_additional_settings = get_post_meta( $product_id, '_rental_additional_settings', true );
    if ( '' !== $legacy_additional_settings ) {
        $settings['additional_settings'] = $legacy_additional_settings;
    }
}

$currency = get_woocommerce_currency_symbol();
?>

<div id="rental_settings_data" class="panel woocommerce_options_panel show_if_rental_vehicle hidden">
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
            <?php
            $summary_min_days       = isset( $settings['min_days'] ) && '' !== $settings['min_days'] ? absint( $settings['min_days'] ) : 1;
            $summary_max_days       = isset( $settings['max_days'] ) && '' !== $settings['max_days'] ? absint( $settings['max_days'] ) : 30;
            $summary_extra_day_hour = isset( $settings['extra_day_hour'] ) && '' !== $settings['extra_day_hour'] ? absint( $settings['extra_day_hour'] ) : 14;
            ?>
            <ul style="margin-left: 20px; list-style: disc;">
                <li><?php printf(
                    esc_html__( 'Rental period: %d to %d days', 'woocommerce-car-rental' ),
                    $summary_min_days,
                    $summary_max_days
                ); ?></li>
                <li><?php printf(
                    esc_html__( 'Extra day charged after: %d:00', 'woocommerce-car-rental' ),
                    $summary_extra_day_hour
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
