<?php
/**
 * Uninstall Script
 *
 * This file runs when the plugin is deleted
 *
 * @package WooCommerce_Car_Rental
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Check user capabilities
if ( ! current_user_can( 'activate_plugins' ) ) {
    return;
}

/**
 * Delete plugin data on uninstall
 */
function wcrbtw_uninstall_cleanup() {
    global $wpdb;

    // Get all rental vehicle products
    $rental_products = $wpdb->get_col( 
        $wpdb->prepare( 
            "SELECT p.ID FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'product'
            AND pm.meta_key = '_product_type'
            AND pm.meta_value = %s",
            'rental_vehicle'
        )
    );

    // Delete rental vehicle meta data
    if ( ! empty( $rental_products ) ) {
        $meta_keys = array(
            '_rental_details',
            '_rental_rates',
            '_rental_availability',
            '_rental_services',
            '_rental_insurance',
            '_rental_settings',
            '_rental_vehicle_type',
            '_rental_seats',
            '_rental_fuel_type',
            '_rental_transmission',
            '_rental_fleet_quantity',
            '_rental_base_daily_rate',
            '_rental_min_days',
            '_rental_max_days',
            '_rental_security_deposit',
        );

        foreach ( $rental_products as $product_id ) {
            foreach ( $meta_keys as $meta_key ) {
                delete_post_meta( $product_id, $meta_key );
            }
        }
    }

    // Delete order meta related to rentals
    $rental_order_meta_keys = array(
        '_contains_rental_vehicles',
        '_rental_start_date',
        '_rental_end_date',
        '_is_rental_vehicle',
    );

    foreach ( $rental_order_meta_keys as $meta_key ) {
        $wpdb->delete(
            $wpdb->postmeta,
            array( 'meta_key' => $meta_key ),
            array( '%s' )
        );
    }

    // Delete any transients
    $wpdb->query( 
        "DELETE FROM {$wpdb->options} 
        WHERE option_name LIKE '_transient_wcrbtw_%' 
        OR option_name LIKE '_transient_timeout_wcrbtw_%'"
    );

    // Clear any scheduled cron jobs
    $timestamp = wp_next_scheduled( 'wcrbtw_daily_cleanup' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'wcrbtw_daily_cleanup' );
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}

// Run cleanup
wcrbtw_uninstall_cleanup();
