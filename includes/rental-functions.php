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
 * Safely decode a JSON post meta value.
 *
 * Handles both JSON encoded strings and arrays already stored by legacy
 * versions of the plugin.
 *
 * @since 1.0.0
 * @param int    $product_id Product ID.
 * @param string $meta_key   Meta key to retrieve.
 * @return array
 */
function wcrbtw_get_json_meta_array( int $product_id, string $meta_key ): array {
    $value = get_post_meta( $product_id, $meta_key, true );

    if ( empty( $value ) && '0' !== $value ) {
        return array();
    }

    if ( is_array( $value ) ) {
        return $value;
    }

    if ( is_string( $value ) ) {
        $decoded = json_decode( $value, true );

        if ( is_array( $decoded ) ) {
            return $decoded;
        }
    }

    return array();
}

/**
 * Merge new meta structures with legacy array based meta values.
 *
 * Ensures backwards compatibility with the previous `_rental_*` meta keys
 * while prioritising the new `_wcrbtw_*` values.
 *
 * @since 1.0.0
 * @param array $data    Data assembled from the new meta keys.
 * @param mixed $legacy  Legacy meta value (expected to be array).
 * @return array
 */
function wcrbtw_merge_with_legacy_rental_meta( array $data, $legacy, array $meta_exists = array() ): array {
    if ( ! is_array( $legacy ) || empty( $legacy ) ) {
        return $data;
    }

    foreach ( $legacy as $key => $value ) {
        $has_new_meta = $meta_exists[ $key ] ?? false;

        if ( is_array( $value ) ) {
            $current = $data[ $key ] ?? array();

            if ( $has_new_meta ) {
                continue;
            }

            if ( ! is_array( $current ) || empty( $current ) ) {
                $data[ $key ] = $value;
                continue;
            }

            $child_exists = array();
            if ( isset( $meta_exists[ $key ] ) && is_array( $meta_exists[ $key ] ) ) {
                $child_exists = $meta_exists[ $key ];
            }

            $data[ $key ] = wcrbtw_merge_with_legacy_rental_meta( $current, $value, $child_exists );
            continue;
        }

        if ( $has_new_meta ) {
            continue;
        }

        if ( ! array_key_exists( $key, $data ) || null === $data[ $key ] ) {
            $data[ $key ] = $value;
        }
    }

    return $data;
}

/**
 * Evaluate truthy toggle values saved in post meta.
 *
 * @since 1.0.0
 * @param mixed $value Toggle value.
 * @return bool
 */
function wcrbtw_is_meta_flag_enabled( $value ): bool {
    if ( is_bool( $value ) ) {
        return $value;
    }

    if ( is_numeric( $value ) ) {
        return (int) $value > 0;
    }

    if ( is_string( $value ) ) {
        $value = strtolower( $value );

        return in_array( $value, array( 'yes', 'true', '1', 'on' ), true );
    }

    return false;
}

/**
 * Normalise seasonal rate arrays.
 *
 * @since 1.0.0
 * @param mixed $rate Seasonal rate entry.
 * @return array
 */
function wcrbtw_normalize_seasonal_rate( $rate ): array {
    if ( ! is_array( $rate ) ) {
        return array(
            'name'       => '',
            'start_date' => '',
            'end_date'   => '',
            'rate'       => 0.0,
            'priority'   => 0,
            'recurring'  => 'no',
        );
    }

    $normalized = array_merge(
        array(
            'name'       => '',
            'start_date' => '',
            'end_date'   => '',
            'rate'       => 0.0,
            'priority'   => 0,
            'recurring'  => 'no',
        ),
        $rate
    );

    $normalized['name']       = (string) $normalized['name'];
    $normalized['start_date'] = (string) $normalized['start_date'];
    $normalized['end_date']   = (string) $normalized['end_date'];

    if ( isset( $normalized['rate'] ) && '' !== $normalized['rate'] && null !== $normalized['rate'] ) {
        $normalized['rate'] = (float) $normalized['rate'];
    } else {
        $normalized['rate'] = 0.0;
    }

    if ( isset( $normalized['priority'] ) ) {
        $normalized['priority'] = (int) $normalized['priority'];
    }

    $normalized['recurring'] = wcrbtw_is_meta_flag_enabled( $normalized['recurring'] ?? 'no' ) ? 'yes' : 'no';

    return $normalized;
}

/**
 * Normalise quantity period arrays.
 *
 * @since 1.0.0
 * @param mixed $period Quantity period entry.
 * @return array
 */
function wcrbtw_normalize_quantity_period( $period ): array {
    if ( ! is_array( $period ) ) {
        return array(
            'start_date' => '',
            'end_date'   => '',
            'quantity'   => 0,
        );
    }

    $normalized = array_merge(
        array(
            'start_date' => '',
            'end_date'   => '',
            'quantity'   => 0,
        ),
        $period
    );

    $normalized['start_date'] = isset( $normalized['start_date'] ) ? (string) $normalized['start_date'] : '';
    $normalized['end_date']   = isset( $normalized['end_date'] ) ? (string) $normalized['end_date'] : '';

    if ( isset( $normalized['quantity'] ) && '' !== $normalized['quantity'] && null !== $normalized['quantity'] ) {
        $normalized['quantity'] = max( 0, (int) $normalized['quantity'] );
    } else {
        $normalized['quantity'] = 0;
    }

    return $normalized;
}

/**
 * Normalise blocked dates arrays.
 *
 * @since 1.0.0
 * @param mixed $blocked_dates Blocked dates value.
 * @return array
 */
function wcrbtw_normalize_blocked_dates( $blocked_dates ): array {
    if ( ! is_array( $blocked_dates ) ) {
        return array();
    }

    $dates = array();

    foreach ( $blocked_dates as $key => $value ) {
        if ( is_string( $value ) && '' !== $value ) {
            $dates[] = $value;
            continue;
        }

        if ( is_string( $key ) && ! is_array( $value ) && '' !== $key ) {
            $dates[] = $key;
        }
    }

    $dates = array_unique( $dates );

    return array_values( $dates );
}

/**
 * Normalise weekly closure arrays.
 *
 * @since 1.0.0
 * @param mixed $weekly_closures Weekly closures value.
 * @return array
 */
function wcrbtw_normalize_weekly_closures( $weekly_closures ): array {
    if ( ! is_array( $weekly_closures ) ) {
        return array();
    }

    $closures = array();

    foreach ( $weekly_closures as $key => $value ) {
        if ( is_numeric( $value ) ) {
            $closures[] = (int) $value;
            continue;
        }

        if ( is_numeric( $key ) && wcrbtw_is_meta_flag_enabled( $value ) ) {
            $closures[] = (int) $key;
        }
    }

    $closures = array_values( array_unique( $closures ) );
    sort( $closures );

    return $closures;
}

/**
 * Get rental vehicle details
 *
 * @since 1.0.0
 * @param int $product_id Product ID
 * @return array
 */
function wcrbtw_get_vehicle_details( int $product_id ): array {
    $meta_exists = array(
        'vehicle_type'       => metadata_exists( 'post', $product_id, '_wcrbtw_vehicle_type' ),
        'seats'              => metadata_exists( 'post', $product_id, '_wcrbtw_seats' ),
        'fuel_type'          => metadata_exists( 'post', $product_id, '_wcrbtw_fuel_type' ),
        'transmission'       => metadata_exists( 'post', $product_id, '_wcrbtw_transmission' ),
        'fleet_quantity'     => metadata_exists( 'post', $product_id, '_wcrbtw_fleet_quantity' ),
        'additional_details' => metadata_exists( 'post', $product_id, '_wcrbtw_additional_details' ),
    );

    $details = array(
        'vehicle_type'       => $meta_exists['vehicle_type'] ? get_post_meta( $product_id, '_wcrbtw_vehicle_type', true ) : null,
        'seats'              => $meta_exists['seats'] ? get_post_meta( $product_id, '_wcrbtw_seats', true ) : null,
        'fuel_type'          => $meta_exists['fuel_type'] ? get_post_meta( $product_id, '_wcrbtw_fuel_type', true ) : null,
        'transmission'       => $meta_exists['transmission'] ? get_post_meta( $product_id, '_wcrbtw_transmission', true ) : null,
        'fleet_quantity'     => $meta_exists['fleet_quantity'] ? get_post_meta( $product_id, '_wcrbtw_fleet_quantity', true ) : null,
        'additional_details' => $meta_exists['additional_details'] ? get_post_meta( $product_id, '_wcrbtw_additional_details', true ) : null,
    );

    $details = wcrbtw_merge_with_legacy_rental_meta(
        $details,
        get_post_meta( $product_id, '_rental_details', true ),
        $meta_exists
    );

    $details = array_merge(
        array(
            'vehicle_type'       => '',
            'seats'              => '',
            'fuel_type'          => '',
            'transmission'       => '',
            'fleet_quantity'     => '',
            'additional_details' => '',
        ),
        $details
    );

    $text_fields = array( 'vehicle_type', 'fuel_type', 'transmission', 'additional_details' );
    foreach ( $text_fields as $field ) {
        if ( null === $details[ $field ] ) {
            $details[ $field ] = '';
        } else {
            $details[ $field ] = (string) $details[ $field ];
        }
    }

    if ( null !== $details['seats'] && '' !== $details['seats'] ) {
        $details['seats'] = (int) $details['seats'];
    } elseif ( null === $details['seats'] ) {
        $details['seats'] = '';
    }

    if ( null !== $details['fleet_quantity'] && '' !== $details['fleet_quantity'] ) {
        $details['fleet_quantity'] = (int) $details['fleet_quantity'];
    } elseif ( null === $details['fleet_quantity'] ) {
        $details['fleet_quantity'] = '';
    }

    return $details;
}

/**
 * Get rental vehicle rates
 *
 * @since 1.0.0
 * @param int $product_id Product ID
 * @return array
 */
function wcrbtw_get_vehicle_rates( int $product_id ): array {
    $meta_exists = array(
        'base_daily_rate' => metadata_exists( 'post', $product_id, '_wcrbtw_base_daily_rate' ),
        'seasonal_rates'  => metadata_exists( 'post', $product_id, '_wcrbtw_seasonal_rates' ),
    );

    $rates = array(
        'base_daily_rate' => $meta_exists['base_daily_rate'] ? get_post_meta( $product_id, '_wcrbtw_base_daily_rate', true ) : null,
        'seasonal_rates'  => $meta_exists['seasonal_rates']
            ? array_map( 'wcrbtw_normalize_seasonal_rate', wcrbtw_get_json_meta_array( $product_id, '_wcrbtw_seasonal_rates' ) )
            : null,
    );

    $rates = wcrbtw_merge_with_legacy_rental_meta(
        $rates,
        get_post_meta( $product_id, '_rental_rates', true ),
        $meta_exists
    );

    $rates = array_merge(
        array(
            'base_daily_rate' => 0.0,
            'seasonal_rates'  => array(),
        ),
        $rates
    );

    if ( null !== $rates['base_daily_rate'] && '' !== $rates['base_daily_rate'] ) {
        $rates['base_daily_rate'] = (float) $rates['base_daily_rate'];
    } else {
        $rates['base_daily_rate'] = 0.0;
    }

    if ( null === $rates['seasonal_rates'] ) {
        $rates['seasonal_rates'] = array();
    }

    $seasonal_rates = array();
    foreach ( (array) $rates['seasonal_rates'] as $seasonal_rate ) {
        $seasonal_rates[] = wcrbtw_normalize_seasonal_rate( $seasonal_rate );
    }

    $rates['seasonal_rates'] = $seasonal_rates;

    return $rates;
}

/**
 * Get rental vehicle availability
 *
 * @since 1.0.0
 * @param int $product_id Product ID
 * @return array
 */
function wcrbtw_get_vehicle_availability( int $product_id ): array {
    $meta_exists = array(
        'blocked_dates'     => metadata_exists( 'post', $product_id, '_wcrbtw_blocked_dates' ),
        'quantity_periods'  => metadata_exists( 'post', $product_id, '_wcrbtw_quantity_periods' ),
        'weekly_closures'   => metadata_exists( 'post', $product_id, '_wcrbtw_weekly_closures' ),
        'maintenance_notes' => metadata_exists( 'post', $product_id, '_wcrbtw_maintenance_notes' ),
    );

    $availability = array(
        'blocked_dates'    => $meta_exists['blocked_dates']
            ? wcrbtw_normalize_blocked_dates( wcrbtw_get_json_meta_array( $product_id, '_wcrbtw_blocked_dates' ) )
            : null,
        'quantity_periods' => $meta_exists['quantity_periods']
            ? array_map( 'wcrbtw_normalize_quantity_period', wcrbtw_get_json_meta_array( $product_id, '_wcrbtw_quantity_periods' ) )
            : null,
        'weekly_closures'  => $meta_exists['weekly_closures']
            ? wcrbtw_normalize_weekly_closures( wcrbtw_get_json_meta_array( $product_id, '_wcrbtw_weekly_closures' ) )
            : null,
        'maintenance_notes' => $meta_exists['maintenance_notes']
            ? get_post_meta( $product_id, '_wcrbtw_maintenance_notes', true )
            : null,
    );

    $availability = wcrbtw_merge_with_legacy_rental_meta(
        $availability,
        get_post_meta( $product_id, '_rental_availability', true ),
        $meta_exists
    );

    $availability = array_merge(
        array(
            'blocked_dates'     => array(),
            'quantity_periods'  => array(),
            'weekly_closures'   => array(),
            'maintenance_notes' => '',
        ),
        $availability
    );

    if ( null === $availability['blocked_dates'] ) {
        $availability['blocked_dates'] = array();
    }

    $availability['blocked_dates'] = wcrbtw_normalize_blocked_dates( $availability['blocked_dates'] );

    $normalized_periods = array();
    if ( null !== $availability['quantity_periods'] ) {
        foreach ( (array) $availability['quantity_periods'] as $period ) {
            $normalized_periods[] = wcrbtw_normalize_quantity_period( $period );
        }
    }
    $availability['quantity_periods'] = $normalized_periods;

    if ( null === $availability['weekly_closures'] ) {
        $availability['weekly_closures'] = array();
    }

    $availability['weekly_closures'] = wcrbtw_normalize_weekly_closures( $availability['weekly_closures'] );

    if ( null === $availability['maintenance_notes'] ) {
        $availability['maintenance_notes'] = '';
    }

    return $availability;
}

/**
 * Get rental vehicle services
 *
 * @since 1.0.0
 * @param int $product_id Product ID
 * @return array
 */
function wcrbtw_get_vehicle_services( int $product_id ): array {
    $meta_exists = metadata_exists( 'post', $product_id, '_wcrbtw_services' );
    $services    = $meta_exists ? wcrbtw_get_json_meta_array( $product_id, '_wcrbtw_services' ) : array();

    if ( ! $meta_exists ) {
        $legacy_services = get_post_meta( $product_id, '_rental_services', true );
        if ( is_array( $legacy_services ) && ! empty( $legacy_services ) ) {
            $services = $legacy_services;
        }
    }

    $normalized_services = array();

    foreach ( (array) $services as $service ) {
        if ( ! is_array( $service ) ) {
            continue;
        }

        $service = array_merge(
            array(
                'name'        => '',
                'price_type'  => 'flat',
                'price'       => 0.0,
                'description' => '',
                'enabled'     => 'no',
            ),
            $service
        );

        if ( isset( $service['price'] ) && '' !== $service['price'] && null !== $service['price'] ) {
            $service['price'] = (float) $service['price'];
        } else {
            $service['price'] = 0.0;
        }

        $service['name']        = (string) $service['name'];
        $service['price_type'] = (string) $service['price_type'];
        $service['description'] = (string) $service['description'];
        $service['enabled'] = wcrbtw_is_meta_flag_enabled( $service['enabled'] ?? 'no' ) ? 'yes' : 'no';

        $normalized_services[] = $service;
    }

    $enabled_services = array_filter(
        $normalized_services,
        function( $service ) {
            return isset( $service['enabled'] ) && 'yes' === $service['enabled'];
        }
    );

    return array_values( $enabled_services );
}

/**
 * Get rental vehicle insurance options
 *
 * @since 1.0.0
 * @param int $product_id Product ID
 * @return array
 */
function wcrbtw_get_vehicle_insurance( int $product_id ): array {
    $meta_exists = metadata_exists( 'post', $product_id, '_wcrbtw_insurance' );
    $insurance   = $meta_exists ? wcrbtw_get_json_meta_array( $product_id, '_wcrbtw_insurance' ) : array();

    if ( ! $meta_exists ) {
        $legacy_insurance = get_post_meta( $product_id, '_rental_insurance', true );
        if ( is_array( $legacy_insurance ) && ! empty( $legacy_insurance ) ) {
            $insurance = $legacy_insurance;
        }
    }

    $normalized_insurance = array();

    foreach ( (array) $insurance as $option ) {
        if ( ! is_array( $option ) ) {
            continue;
        }

        $option = array_merge(
            array(
                'name'        => '',
                'cost_type'   => 'daily',
                'cost'        => 0.0,
                'deductible'  => 0.0,
                'description' => '',
                'enabled'     => 'no',
            ),
            $option
        );

        $option['cost']       = ( isset( $option['cost'] ) && '' !== $option['cost'] && null !== $option['cost'] ) ? (float) $option['cost'] : 0.0;
        $option['deductible'] = ( isset( $option['deductible'] ) && '' !== $option['deductible'] && null !== $option['deductible'] ) ? (float) $option['deductible'] : 0.0;
        $option['name']       = (string) $option['name'];
        $option['cost_type']  = (string) $option['cost_type'];
        $option['description'] = (string) $option['description'];
        $option['enabled']    = wcrbtw_is_meta_flag_enabled( $option['enabled'] ?? 'no' ) ? 'yes' : 'no';

        $normalized_insurance[] = $option;
    }

    $enabled_insurance = array_filter(
        $normalized_insurance,
        function( $option ) {
            return isset( $option['enabled'] ) && 'yes' === $option['enabled'];
        }
    );

    return array_values( $enabled_insurance );
}

/**
 * Get rental vehicle settings
 *
 * @since 1.0.0
 * @param int $product_id Product ID
 * @return array
 */
function wcrbtw_get_vehicle_settings( int $product_id ): array {
    $meta_exists = array(
        'min_days'            => metadata_exists( 'post', $product_id, '_wcrbtw_min_days' ),
        'max_days'            => metadata_exists( 'post', $product_id, '_wcrbtw_max_days' ),
        'extra_day_hour'      => metadata_exists( 'post', $product_id, '_wcrbtw_extra_day_hour' ),
        'security_deposit'    => metadata_exists( 'post', $product_id, '_wcrbtw_security_deposit' ),
        'cancellation_policy' => metadata_exists( 'post', $product_id, '_wcrbtw_cancellation_policy' ),
        'additional_settings' => metadata_exists( 'post', $product_id, '_wcrbtw_additional_settings' ),
    );

    $settings = array(
        'min_days'            => $meta_exists['min_days'] ? get_post_meta( $product_id, '_wcrbtw_min_days', true ) : null,
        'max_days'            => $meta_exists['max_days'] ? get_post_meta( $product_id, '_wcrbtw_max_days', true ) : null,
        'extra_day_hour'      => $meta_exists['extra_day_hour'] ? get_post_meta( $product_id, '_wcrbtw_extra_day_hour', true ) : null,
        'security_deposit'    => $meta_exists['security_deposit'] ? get_post_meta( $product_id, '_wcrbtw_security_deposit', true ) : null,
        'cancellation_policy' => $meta_exists['cancellation_policy'] ? get_post_meta( $product_id, '_wcrbtw_cancellation_policy', true ) : null,
        'additional_settings' => $meta_exists['additional_settings'] ? get_post_meta( $product_id, '_wcrbtw_additional_settings', true ) : null,
    );

    $settings = wcrbtw_merge_with_legacy_rental_meta(
        $settings,
        get_post_meta( $product_id, '_rental_settings', true ),
        $meta_exists
    );

    $settings = array_merge(
        array(
            'min_days'            => null,
            'max_days'            => null,
            'extra_day_hour'      => null,
            'security_deposit'    => null,
            'cancellation_policy' => '',
            'additional_settings' => '',
        ),
        $settings
    );

    if ( null !== $settings['min_days'] && '' !== $settings['min_days'] ) {
        $settings['min_days'] = (int) $settings['min_days'];
    } else {
        $settings['min_days'] = null;
    }

    if ( null !== $settings['max_days'] && '' !== $settings['max_days'] ) {
        $settings['max_days'] = (int) $settings['max_days'];
    } else {
        $settings['max_days'] = null;
    }

    if ( null !== $settings['extra_day_hour'] && '' !== $settings['extra_day_hour'] ) {
        $settings['extra_day_hour'] = (int) $settings['extra_day_hour'];
    } else {
        $settings['extra_day_hour'] = null;
    }

    if ( null !== $settings['security_deposit'] && '' !== $settings['security_deposit'] ) {
        $settings['security_deposit'] = (float) $settings['security_deposit'];
    } else {
        $settings['security_deposit'] = 0.0;
    }

    if ( null === $settings['cancellation_policy'] ) {
        $settings['cancellation_policy'] = '';
    }

    if ( null === $settings['additional_settings'] ) {
        $settings['additional_settings'] = '';
    }

    return $settings;
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
        $day_of_week = (int) $current->format( 'w' ); // 0 = Sunday, 6 = Saturday

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
