<?php
/**
 * Rental Availability Panel Template
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

// Prepare availability data from new meta keys.
$blocked_dates_meta_exists    = metadata_exists( 'post', $product_id, '_wcrbtw_blocked_dates' );
$quantity_periods_meta_exists = metadata_exists( 'post', $product_id, '_wcrbtw_quantity_periods' );
$weekly_closures_meta_exists  = metadata_exists( 'post', $product_id, '_wcrbtw_weekly_closures' );
$maintenance_meta_exists      = metadata_exists( 'post', $product_id, '_wcrbtw_maintenance_notes' );

$blocked_dates = wcrbtw_maybe_decode_meta_array(
    $blocked_dates_meta_exists ? get_post_meta( $product_id, '_wcrbtw_blocked_dates', true ) : null
);

$quantity_periods = wcrbtw_maybe_decode_meta_array(
    $quantity_periods_meta_exists ? get_post_meta( $product_id, '_wcrbtw_quantity_periods', true ) : null
);

$weekly_closures = wcrbtw_maybe_decode_meta_array(
    $weekly_closures_meta_exists ? get_post_meta( $product_id, '_wcrbtw_weekly_closures', true ) : null
);

$maintenance_notes = $maintenance_meta_exists
    ? get_post_meta( $product_id, '_wcrbtw_maintenance_notes', true )
    : '';

// Legacy fallbacks using previous `_rental_*` meta keys when new data is not present.
$legacy_availability = wcrbtw_maybe_decode_meta_array( get_post_meta( $product_id, '_rental_availability', true ) );

if ( ! $blocked_dates_meta_exists ) {
    if ( empty( $blocked_dates ) ) {
        $blocked_dates = wcrbtw_maybe_decode_meta_array( get_post_meta( $product_id, '_rental_blocked_dates', true ) );
    }

    if ( empty( $blocked_dates ) && isset( $legacy_availability['blocked_dates'] ) ) {
        $blocked_dates = wcrbtw_maybe_decode_meta_array( $legacy_availability['blocked_dates'] );
    }
}

if ( ! $quantity_periods_meta_exists ) {
    if ( empty( $quantity_periods ) ) {
        $quantity_periods = wcrbtw_maybe_decode_meta_array( get_post_meta( $product_id, '_rental_quantity_periods', true ) );
    }

    if ( empty( $quantity_periods ) && isset( $legacy_availability['quantity_periods'] ) ) {
        $quantity_periods = wcrbtw_maybe_decode_meta_array( $legacy_availability['quantity_periods'] );
    }
}

if ( ! $weekly_closures_meta_exists ) {
    if ( empty( $weekly_closures ) ) {
        $weekly_closures = wcrbtw_maybe_decode_meta_array( get_post_meta( $product_id, '_rental_weekly_closures', true ) );
    }

    if ( empty( $weekly_closures ) && isset( $legacy_availability['weekly_closures'] ) ) {
        $weekly_closures = wcrbtw_maybe_decode_meta_array( $legacy_availability['weekly_closures'] );
    }
}

if ( ! $maintenance_meta_exists ) {
    if ( '' === $maintenance_notes ) {
        $maintenance_notes = get_post_meta( $product_id, '_rental_maintenance_notes', true );
    }

    if ( '' === $maintenance_notes && isset( $legacy_availability['maintenance_notes'] ) ) {
        $legacy_maintenance = $legacy_availability['maintenance_notes'];
        $maintenance_notes  = is_scalar( $legacy_maintenance ) ? (string) $legacy_maintenance : '';
    }
}

$availability = array(
    'blocked_dates'     => $blocked_dates,
    'quantity_periods'  => $quantity_periods,
    'weekly_closures'   => $weekly_closures,
    'maintenance_notes' => $maintenance_notes,
);
?>

<div id="rental_availability_data" class="panel woocommerce_options_panel show_if_rental_vehicle hidden">
    <div class="options_group">
        <h3><?php esc_html_e( 'Manual Date Blocks', 'woocommerce-car-rental' ); ?></h3>
        
        <p class="form-field">
            <label><?php esc_html_e( 'Blocked Dates', 'woocommerce-car-rental' ); ?></label>
            <span class="description"><?php esc_html_e( 'Select specific dates when this vehicle is not available for rental.', 'woocommerce-car-rental' ); ?></span>
        </p>

        <div id="rental_blocked_dates_container" class="wcrbtw-calendar-container">
            <textarea 
                id="_rental_blocked_dates_input" 
                name="_rental_blocked_dates[]" 
                placeholder="<?php esc_attr_e( 'Enter dates in YYYY-MM-DD format, one per line', 'woocommerce-car-rental' ); ?>"
                rows="5"
                cols="40"><?php 
                if ( ! empty( $availability['blocked_dates'] ) && is_array( $availability['blocked_dates'] ) ) {
                    echo esc_textarea( implode( "\n", $availability['blocked_dates'] ) );
                }
            ?></textarea>
            <p class="description">
                <?php esc_html_e( 'Enter dates when the vehicle is unavailable (maintenance, already booked, etc.)', 'woocommerce-car-rental' ); ?>
            </p>
        </div>
    </div>

    <div class="options_group">
        <h3><?php esc_html_e( 'Quantity Management by Period', 'woocommerce-car-rental' ); ?></h3>
        
        <p class="form-field">
            <label><?php esc_html_e( 'Available Quantities', 'woocommerce-car-rental' ); ?></label>
            <span class="description"><?php esc_html_e( 'Set different quantities available for specific periods.', 'woocommerce-car-rental' ); ?></span>
        </p>

        <div id="rental_quantity_periods_container" class="wcrbtw-repeater-container">
            <table class="widefat wcrbtw-repeater-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Start Date', 'woocommerce-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'End Date', 'woocommerce-car-rental' ); ?></th>
                        <th><?php esc_html_e( 'Available Quantity', 'woocommerce-car-rental' ); ?></th>
                        <th width="50"><?php esc_html_e( 'Actions', 'woocommerce-car-rental' ); ?></th>
                    </tr>
                </thead>
                <tbody id="rental_quantity_periods_tbody">
                    <?php
                    $quantity_periods = $availability['quantity_periods'] ?? array();
                    if ( empty( $quantity_periods ) ) {
                        $quantity_periods = array( array() );
                    }
                    
                    foreach ( $quantity_periods as $index => $period ) :
                    ?>
                    <tr class="wcrbtw-repeater-row">
                        <td>
                            <input type="date" 
                                   name="_rental_quantity_periods[<?php echo esc_attr( $index ); ?>][start_date]" 
                                   value="<?php echo esc_attr( $period['start_date'] ?? '' ); ?>" />
                        </td>
                        <td>
                            <input type="date" 
                                   name="_rental_quantity_periods[<?php echo esc_attr( $index ); ?>][end_date]" 
                                   value="<?php echo esc_attr( $period['end_date'] ?? '' ); ?>" />
                        </td>
                        <td>
                            <input type="number" 
                                   name="_rental_quantity_periods[<?php echo esc_attr( $index ); ?>][quantity]" 
                                   value="<?php echo esc_attr( $period['quantity'] ?? '1' ); ?>" 
                                   min="0" 
                                   step="1" />
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
                <button type="button" id="add_quantity_period" class="button button-primary wcrbtw-add-row">
                    <?php esc_html_e( 'Add Period', 'woocommerce-car-rental' ); ?>
                </button>
            </p>
        </div>
    </div>

    <div class="options_group">
        <h3><?php esc_html_e( 'Recurring Closures', 'woocommerce-car-rental' ); ?></h3>
        
        <p class="form-field">
            <label for="_rental_weekly_closures"><?php esc_html_e( 'Weekly Closures', 'woocommerce-car-rental' ); ?></label>
            <span class="wcrbtw-checkboxes">
                <?php
                $days_of_week = array(
                    '1' => __( 'Monday', 'woocommerce-car-rental' ),
                    '2' => __( 'Tuesday', 'woocommerce-car-rental' ),
                    '3' => __( 'Wednesday', 'woocommerce-car-rental' ),
                    '4' => __( 'Thursday', 'woocommerce-car-rental' ),
                    '5' => __( 'Friday', 'woocommerce-car-rental' ),
                    '6' => __( 'Saturday', 'woocommerce-car-rental' ),
                    '0' => __( 'Sunday', 'woocommerce-car-rental' ),
                );
                
                $selected_days = $availability['weekly_closures'] ?? array();
                
                foreach ( $days_of_week as $day_num => $day_name ) :
                ?>
                    <label style="display: inline-block; margin-right: 15px;">
                        <input type="checkbox" 
                               name="_rental_weekly_closures[]" 
                               value="<?php echo esc_attr( $day_num ); ?>"
                               <?php checked( in_array( $day_num, $selected_days, false ) ); ?> />
                        <?php echo esc_html( $day_name ); ?>
                    </label>
                <?php endforeach; ?>
            </span>
            <span class="description" style="display: block; margin-top: 5px;">
                <?php esc_html_e( 'Select days of the week when the vehicle is not available for rental.', 'woocommerce-car-rental' ); ?>
            </span>
        </p>
    </div>

    <div class="options_group">
        <h3><?php esc_html_e( 'Maintenance Notes', 'woocommerce-car-rental' ); ?></h3>
        
        <?php
        // Maintenance notes
        woocommerce_wp_textarea_input( array(
            'id'          => '_rental_maintenance_notes',
            'label'       => __( 'Maintenance Notes', 'woocommerce-car-rental' ),
            'value'       => $availability['maintenance_notes'] ?? '',
            'desc_tip'    => true,
            'description' => __( 'Internal notes about vehicle maintenance and availability.', 'woocommerce-car-rental' ),
            'rows'        => 5,
            'cols'        => 40,
        ) );
        ?>
    </div>
</div>

<script type="text/template" id="tmpl-quantity-period-row">
    <tr class="wcrbtw-repeater-row">
        <td>
            <input type="date" 
                   name="_rental_quantity_periods[{{data.index}}][start_date]" />
        </td>
        <td>
            <input type="date" 
                   name="_rental_quantity_periods[{{data.index}}][end_date]" />
        </td>
        <td>
            <input type="number" 
                   name="_rental_quantity_periods[{{data.index}}][quantity]" 
                   value="1" 
                   min="0" 
                   step="1" />
        </td>
        <td>
            <button type="button" class="button wcrbtw-remove-row">
                <?php esc_html_e( 'Remove', 'woocommerce-car-rental' ); ?>
            </button>
        </td>
    </tr>
</script>
