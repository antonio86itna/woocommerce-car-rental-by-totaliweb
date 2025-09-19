<?php
/**
 * Rental Details Panel Template
 *
 * @package WooCommerce_Car_Rental
 * @subpackage Admin/Views
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Get saved data from individual post meta
$vehicle_type = get_post_meta( $product_id, '_wcrbtw_vehicle_type', true );
$seats = get_post_meta( $product_id, '_wcrbtw_seats', true );
$fuel_type = get_post_meta( $product_id, '_wcrbtw_fuel_type', true );
$transmission = get_post_meta( $product_id, '_wcrbtw_transmission', true );
$fleet_quantity = get_post_meta( $product_id, '_wcrbtw_fleet_quantity', true );
$additional_details = get_post_meta( $product_id, '_wcrbtw_additional_details', true );
?>

<div id="rental_details_data" class="panel woocommerce_options_panel show_if_rental_vehicle hidden">
    <div class="options_group">
        <h3><?php esc_html_e( 'Vehicle Information', 'woocommerce-car-rental' ); ?></h3>
        
        <?php
        // Vehicle type
        woocommerce_wp_select( array(
            'id'      => '_rental_vehicle_type',
            'label'   => __( 'Vehicle Type', 'woocommerce-car-rental' ),
            'options' => array(
                ''        => __( 'Select type...', 'woocommerce-car-rental' ),
                'car'     => __( 'Car', 'woocommerce-car-rental' ),
                'scooter' => __( 'Scooter', 'woocommerce-car-rental' ),
                'van'     => __( 'Van', 'woocommerce-car-rental' ),
                'suv'     => __( 'SUV', 'woocommerce-car-rental' ),
                'truck'   => __( 'Truck', 'woocommerce-car-rental' ),
            ),
            'value'       => $vehicle_type ?: '',
            'desc_tip'    => true,
            'description' => __( 'Select the type of vehicle.', 'woocommerce-car-rental' ),
        ) );

        // Number of seats
        woocommerce_wp_text_input( array(
            'id'          => '_rental_seats',
            'label'       => __( 'Number of Seats', 'woocommerce-car-rental' ),
            'type'        => 'number',
            'value'       => $seats ?: '',
            'custom_attributes' => array(
                'min'  => '1',
                'step' => '1',
            ),
            'desc_tip'    => true,
            'description' => __( 'Enter the number of passenger seats.', 'woocommerce-car-rental' ),
        ) );

        // Fuel type
        woocommerce_wp_select( array(
            'id'      => '_rental_fuel_type',
            'label'   => __( 'Fuel Type', 'woocommerce-car-rental' ),
            'options' => array(
                ''         => __( 'Select fuel type...', 'woocommerce-car-rental' ),
                'gasoline' => __( 'Gasoline', 'woocommerce-car-rental' ),
                'diesel'   => __( 'Diesel', 'woocommerce-car-rental' ),
                'electric' => __( 'Electric', 'woocommerce-car-rental' ),
                'hybrid'   => __( 'Hybrid', 'woocommerce-car-rental' ),
                'lpg'      => __( 'LPG', 'woocommerce-car-rental' ),
            ),
            'value'       => $fuel_type ?: '',
            'desc_tip'    => true,
            'description' => __( 'Select the fuel type of the vehicle.', 'woocommerce-car-rental' ),
        ) );

        // Transmission
        woocommerce_wp_select( array(
            'id'      => '_rental_transmission',
            'label'   => __( 'Transmission', 'woocommerce-car-rental' ),
            'options' => array(
                ''          => __( 'Select transmission...', 'woocommerce-car-rental' ),
                'manual'    => __( 'Manual', 'woocommerce-car-rental' ),
                'automatic' => __( 'Automatic', 'woocommerce-car-rental' ),
                'semi-auto' => __( 'Semi-Automatic', 'woocommerce-car-rental' ),
            ),
            'value'       => $transmission ?: '',
            'desc_tip'    => true,
            'description' => __( 'Select the transmission type.', 'woocommerce-car-rental' ),
        ) );
        ?>
    </div>

    <div class="options_group">
        <h3><?php esc_html_e( 'Fleet Management', 'woocommerce-car-rental' ); ?></h3>
        
        <?php
        // Fleet quantity
        woocommerce_wp_text_input( array(
            'id'          => '_rental_fleet_quantity',
            'label'       => __( 'Fleet Quantity', 'woocommerce-car-rental' ),
            'type'        => 'number',
            'value'       => $fleet_quantity ?: '1',
            'custom_attributes' => array(
                'min'  => '1',
                'step' => '1',
            ),
            'desc_tip'    => true,
            'description' => __( 'Total number of this vehicle model in the fleet.', 'woocommerce-car-rental' ),
        ) );
        ?>
    </div>

    <div class="options_group">
        <h3><?php esc_html_e( 'Additional Information', 'woocommerce-car-rental' ); ?></h3>
        
        <?php
        // Additional details
        woocommerce_wp_textarea_input( array(
            'id'          => '_rental_additional_details',
            'label'       => __( 'Additional Details', 'woocommerce-car-rental' ),
            'value'       => $additional_details ?: '',
            'desc_tip'    => true,
            'description' => __( 'Additional information about the vehicle (features, equipment, etc.).', 'woocommerce-car-rental' ),
            'rows'        => 5,
            'cols'        => 40,
        ) );
        ?>
    </div>
</div>
