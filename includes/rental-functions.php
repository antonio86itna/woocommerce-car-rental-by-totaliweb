<?php
/**
 * Rental Vehicle Helper Functions
 *
 * Utility functions for rental vehicle management
 *
 * @package WooCommerce_Car_Rental
 * @subpackage Includes
 * @since 1.0.0
 */

declare( strict_types=1 );

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get rental vehicle data
 *
 * @since 1.0.0
 * @param int $product_id Product ID
 * @param string $data_type Specific data type to retrieve
 * @return array
 */
function wcrbtw_get_rental_data( int $product_id, string $data_type = '' ): array {
    return WCRBTW_Admin_Product_Data::get_rental_data( $product_id, $data_type );
}

/**
 * Check if product is a rental vehicle
 *
 * @since 1.0.0
 * @param int|WC_Product $product Product ID or object
 * @return bool
 */
function wcrbtw_is_rental_vehicle( $product ): bool {
    if ( is_numeric( $product ) ) {
        $product = wc_get_product( $product );
    }
    
    return $product && 'rental_vehicle' === $product->get_type();
}

/**
 * Get rental vehicle details
 *
 * @since 1.0.0
 * @param int $product_id Product ID
 * @return array
 */
function wcrbtw_get_vehicle_details( int $product_id ): array {
    return get_post_meta( $product_id, '_rental_details', true ) ?: array();
}

/**
 * Get rental vehicle rates
 *
 * @since 1.0.0
 * @param int $product_id Product ID
 * @return array
 */
function wcrbtw_get_vehicle_rates( int $product_id ): array {
    return get_post_meta( $product_id, '_rental_rates', true ) ?: array();
}

/**
 * Get rental vehicle availability
 *
 * @since 1.0.0
 * @param int $product_id Product ID
 * @return array
 */
function wcrbtw_get_vehicle_availability( int $product_id ): array {
    return get_post_meta( $product_id, '_rental_availability', true ) ?: array();
}

/**
 * Get rental vehicle services
 *
 * @since 1.0.0
 * @param int $product_id Product ID
 * @return array
 */
function wcrbtw_get_vehicle_services( int $product_id ): array {
    $services = get_post_meta( $product_id, '_rental_services', true ) ?: array();
    
    // Filter only enabled services
    return array_filter( $services, function( $service ) {
        return isset( $service['enabled'] ) && 'yes' === $service['enabled'];
    } );
}

/**
 * Get rental vehicle insurance options
 *
 * @since 1.0.0
 * @param int $product_id Product ID
 * @return array
 */
function wcrbtw_get_vehicle_insurance( int $product_id ): array {
    $insurance = get_post_meta( $product_id, '_rental_insurance', true ) ?: array();
    
    // Filter only enabled insurance options
    return array_filter( $insurance, function( $option ) {
        return isset( $option['enabled'] ) && 'yes' === $option['enabled'];
    } );
}

/**
 * Get rental vehicle settings
 *
 * @since 1.0.0
 * @param int $product_id Product ID
 * @return array
 */
function wcrbtw_get_vehicle_settings( int $product_id ): array {
    return get_post_meta( $product_id, '_rental_settings', true ) ?: array();
}

/**
 * Calculate rental price for date range
 *
 * @since 1.0.0
 * @param int $product_id Product ID
 * @param string $start_date Start date
 * @param string $end_date End date
 * @return float
 */
function wcrbtw_calculate_rental_price( int $product_id, string $start_date, string $end_date ): float {
    $rates = wcrbtw_get_vehicle_rates( $product_id );
    $base_rate = (float) ( $rates['base_daily_rate'] ?? 0 );
    
    if ( $base_rate <= 0 ) {
        return 0;
    }
    
    // Calculate number of days
    $start = new DateTime( $start_date );
    $end = new DateTime( $end_date );
    $interval = $start->diff( $end );
    $days = $interval->days + 1; // Include both start and end date
    
    // Apply seasonal rates if applicable
    $total_price = 0;
    $seasonal_rates = $rates['seasonal_rates'] ?? array();
    
    // Sort seasonal rates by priority
    usort( $seasonal_rates, function( $a, $b ) {
        return ( $b['priority'] ?? 0 ) - ( $a['priority'] ?? 0 );
    } );
    
    // Calculate price for each day
    $current_date = clone $start;
    while ( $current_date <= $end ) {
        $daily_rate = $base_rate;
        $date_string = $current_date->format( 'Y-m-d' );
        
        // Check for applicable seasonal rate
        foreach ( $seasonal_rates as $seasonal ) {
            if ( empty( $seasonal['start_date'] ) || empty( $seasonal['end_date'] ) ) {
                continue;
            }
            
            $season_start = new DateTime( $seasonal['start_date'] );
            $season_end = new DateTime( $seasonal['end_date'] );
            
            // Handle recurring dates (annual)
            if ( 'yes' === ( $seasonal['recurring'] ?? 'no' ) ) {
                // Adjust year for comparison
                $season_start->setDate( (int) $current_date->format( 'Y' ), (int) $season_start->format( 'm' ), (int) $season_start->format( 'd' ) );
                $season_end->setDate( (int) $current_date->format( 'Y' ), (int) $season_end->format( 'm' ), (int) $season_end->format( 'd' ) );
                
                // Handle year boundary crossing
                if ( $season_end < $season_start ) {
                    $season_end->modify( '+1 year' );
                }
            }
            
            if ( $current_date >= $season_start && $current_date <= $season_end ) {
                $daily_rate = (float) $seasonal['rate'];
                break; // Use first matching rate (highest priority)
            }
        }
        
        $total_price += $daily_rate;
        $current_date->modify( '+1 day' );
    }
    
    /**
     * Filter the calculated rental price
     *
     * @since 1.0.0
     * @param float $total_price Calculated price
     * @param int $product_id Product ID
     * @param string $start_date Start date
     * @param string $end_date End date
     * @param int $days Number of rental days
     */
    return (float) apply_filters( 'wcrbtw_calculated_rental_price', $total_price, $product_id, $start_date, $end_date, $days );
}

/**
 * Check if vehicle is available for date range
 *
 * @since 1.0.0
 * @param int $product_id Product ID
 * @param string $start_date Start date
 * @param string $end_date End date
 * @return bool
 */
function wcrbtw_is_vehicle_available( int $product_id, string $start_date, string $end_date ): bool {
    $availability = wcrbtw_get_vehicle_availability( $product_id );
    $blocked_dates = $availability['blocked_dates'] ?? array();
    $weekly_closures = $availability['weekly_closures'] ?? array();
    
    // Check date range
    $start = new DateTime( $start_date );
    $end = new DateTime( $end_date );
    $current = clone $start;
    
    while ( $current <= $end ) {
        $date_string = $current->format( 'Y-m-d' );
        $day_of_week = $current->format( 'w' ); // 0 = Sunday, 6 = Saturday
        
        // Check if date is blocked
        if ( in_array( $date_string, $blocked_dates, true ) ) {
            return false;
        }
        
        // Check weekly closures
        if ( in_array( $day_of_week, $weekly_closures, true ) ) {
            return false;
        }
        
        $current->modify( '+1 day' );
    }
    
    // Check quantity availability
    $quantity_periods = $availability['quantity_periods'] ?? array();
    if ( ! empty( $quantity_periods ) ) {
        foreach ( $quantity_periods as $period ) {
            if ( empty( $period['start_date'] ) || empty( $period['end_date'] ) ) {
                continue;
            }
            
            $period_start = new DateTime( $period['start_date'] );
            $period_end = new DateTime( $period['end_date'] );
            
            // Check if our date range overlaps with this period
            if ( $start <= $period_end && $end >= $period_start ) {
                $available_qty = (int) ( $period['quantity'] ?? 0 );
                
                // Get existing bookings for this period
                $booked_qty = wcrbtw_get_booked_quantity( $product_id, $start_date, $end_date );
                
                if ( $booked_qty >= $available_qty ) {
                    return false;
                }
            }
        }
    }
    
    /**
     * Filter vehicle availability
     *
     * @since 1.0.0
     * @param bool $available Whether the vehicle is available
     * @param int $product_id Product ID
     * @param string $start_date Start date
     * @param string $end_date End date
     */
    return apply_filters( 'wcrbtw_vehicle_availability', true, $product_id, $start_date, $end_date );
}

/**
 * Get booked quantity for a vehicle in date range
 *
 * @since 1.0.0
 * @param int $product_id Product ID
 * @param string $start_date Start date
 * @param string $end_date End date
 * @return int
 */
function wcrbtw_get_booked_quantity( int $product_id, string $start_date, string $end_date ): int {
    // Query orders containing this rental vehicle
    $args = array(
        'limit'  => -1,
        'status' => array( 'wc-processing', 'wc-completed', 'wc-on-hold' ),
        'return' => 'ids',
    );
    
    $orders = wc_get_orders( $args );
    $booked_quantity = 0;
    
    foreach ( $orders as $order_id ) {
        $order = wc_get_order( $order_id );
        
        foreach ( $order->get_items() as $item ) {
            if ( $item->get_product_id() !== $product_id ) {
                continue;
            }
            
            $item_start = $item->get_meta( '_rental_start_date' );
            $item_end = $item->get_meta( '_rental_end_date' );
            
            if ( ! $item_start || ! $item_end ) {
                continue;
            }
            
            // Check if dates overlap
            if ( wcrbtw_dates_overlap( $start_date, $end_date, $item_start, $item_end ) ) {
                $booked_quantity += $item->get_quantity();
            }
        }
    }
    
    return $booked_quantity;
}

/**
 * Check if two date ranges overlap
 *
 * @since 1.0.0
 * @param string $start1 First range start date
 * @param string $end1 First range end date
 * @param string $start2 Second range start date
 * @param string $end2 Second range end date
 * @return bool
 */
function wcrbtw_dates_overlap( string $start1, string $end1, string $start2, string $end2 ): bool {
    $start1_dt = new DateTime( $start1 );
    $end1_dt = new DateTime( $end1 );
    $start2_dt = new DateTime( $start2 );
    $end2_dt = new DateTime( $end2 );
    
    return $start1_dt <= $end2_dt && $end1_dt >= $start2_dt;
}

/**
 * Format rental period for display
 *
 * @since 1.0.0
 * @param string $start_date Start date
 * @param string $end_date End date
 * @return string
 */
function wcrbtw_format_rental_period( string $start_date, string $end_date ): string {
    $start = new DateTime( $start_date );
    $end = new DateTime( $end_date );
    $interval = $start->diff( $end );
    $days = $interval->days + 1;
    
    $format = get_option( 'date_format' );
    
    return sprintf(
        __( '%s to %s (%d days)', 'woocommerce-car-rental' ),
        $start->format( $format ),
        $end->format( $format ),
        $days
    );
}

/**
 * Get vehicle type label
 *
 * @since 1.0.0
 * @param string $type Vehicle type key
 * @return string
 */
function wcrbtw_get_vehicle_type_label( string $type ): string {
    $types = array(
        'car'     => __( 'Car', 'woocommerce-car-rental' ),
        'scooter' => __( 'Scooter', 'woocommerce-car-rental' ),
        'van'     => __( 'Van', 'woocommerce-car-rental' ),
        'suv'     => __( 'SUV', 'woocommerce-car-rental' ),
        'truck'   => __( 'Truck', 'woocommerce-car-rental' ),
    );
    
    return $types[ $type ] ?? $type;
}

/**
 * Get fuel type label
 *
 * @since 1.0.0
 * @param string $type Fuel type key
 * @return string
 */
function wcrbtw_get_fuel_type_label( string $type ): string {
    $types = array(
        'gasoline' => __( 'Gasoline', 'woocommerce-car-rental' ),
        'diesel'   => __( 'Diesel', 'woocommerce-car-rental' ),
        'electric' => __( 'Electric', 'woocommerce-car-rental' ),
        'hybrid'   => __( 'Hybrid', 'woocommerce-car-rental' ),
        'lpg'      => __( 'LPG', 'woocommerce-car-rental' ),
    );
    
    return $types[ $type ] ?? $type;
}

/**
 * Get transmission type label
 *
 * @since 1.0.0
 * @param string $type Transmission type key
 * @return string
 */
function wcrbtw_get_transmission_label( string $type ): string {
    $types = array(
        'manual'    => __( 'Manual', 'woocommerce-car-rental' ),
        'automatic' => __( 'Automatic', 'woocommerce-car-rental' ),
        'semi-auto' => __( 'Semi-Automatic', 'woocommerce-car-rental' ),
    );
    
    return $types[ $type ] ?? $type;
}

/**
 * Get minimum rental days for a vehicle
 *
 * @since 1.0.0
 * @param int $product_id Product ID
 * @return int
 */
function wcrbtw_get_min_rental_days( int $product_id ): int {
    $settings = wcrbtw_get_vehicle_settings( $product_id );
    return max( 1, (int) ( $settings['min_days'] ?? 1 ) );
}

/**
 * Get maximum rental days for a vehicle
 *
 * @since 1.0.0
 * @param int $product_id Product ID
 * @return int
 */
function wcrbtw_get_max_rental_days( int $product_id ): int {
    $settings = wcrbtw_get_vehicle_settings( $product_id );
    return max( 1, (int) ( $settings['max_days'] ?? 30 ) );
}

/**
 * Get security deposit for a vehicle
 *
 * @since 1.0.0
 * @param int $product_id Product ID
 * @return float
 */
function wcrbtw_get_security_deposit( int $product_id ): float {
    $settings = wcrbtw_get_vehicle_settings( $product_id );
    return (float) ( $settings['security_deposit'] ?? 0 );
}

/**
 * Get all rental vehicles
 *
 * @since 1.0.0
 * @param array $args Query arguments
 * @return array
 */
function wcrbtw_get_rental_vehicles( array $args = array() ): array {
    $defaults = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => array(
            array(
                'key'     => '_product_type',
                'value'   => 'rental_vehicle',
                'compare' => '=',
            ),
        ),
    );
    
    $args = wp_parse_args( $args, $defaults );
    $query = new WP_Query( $args );
    
    return $query->posts;
}
