<?php
/**
 * REST API Controller for Rental Vehicles
 *
 * Provides REST API endpoints for rental vehicle management
 *
 * @package WooCommerce_Car_Rental
 * @subpackage API
 * @since 1.0.0
 */

declare( strict_types=1 );

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API Controller Class
 *
 * @class WCRBTW_REST_API_Controller
 * @version 1.0.0
 */
final class WCRBTW_REST_API_Controller {

    /**
     * Namespace for API endpoints
     *
     * @var string
     */
    const NAMESPACE = 'wcr/v1';

    /**
     * Instance of this class
     *
     * @var ?WCRBTW_REST_API_Controller
     */
    private static ?WCRBTW_REST_API_Controller $instance = null;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Get singleton instance
     *
     * @since 1.0.0
     * @return WCRBTW_REST_API_Controller
     */
    public static function get_instance(): WCRBTW_REST_API_Controller {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register REST API routes
     *
     * @since 1.0.0
     * @return void
     */
    public function register_routes(): void {
        // Rental Vehicles endpoints
        register_rest_route( self::NAMESPACE, '/vehicles', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_vehicles' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
                'args'                => $this->get_collection_params(),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'create_vehicle' ),
                'permission_callback' => array( $this, 'create_item_permissions_check' ),
                'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
            ),
            'schema' => array( $this, 'get_public_item_schema' ),
        ) );

        register_rest_route( self::NAMESPACE, '/vehicles/(?P<id>[\d]+)', array(
            'args' => array(
                'id' => array(
                    'description' => __( 'Unique identifier for the vehicle.', 'woocommerce-car-rental' ),
                    'type'        => 'integer',
                ),
            ),
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_vehicle' ),
                'permission_callback' => array( $this, 'get_item_permissions_check' ),
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_vehicle' ),
                'permission_callback' => array( $this, 'update_item_permissions_check' ),
                'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( $this, 'delete_vehicle' ),
                'permission_callback' => array( $this, 'delete_item_permissions_check' ),
            ),
            'schema' => array( $this, 'get_public_item_schema' ),
        ) );

        // Availability endpoint
        register_rest_route( self::NAMESPACE, '/vehicles/(?P<id>[\d]+)/availability', array(
            'args' => array(
                'id' => array(
                    'description' => __( 'Unique identifier for the vehicle.', 'woocommerce-car-rental' ),
                    'type'        => 'integer',
                ),
            ),
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_vehicle_availability' ),
                'permission_callback' => array( $this, 'get_item_permissions_check' ),
                'args'                => array(
                    'start_date' => array(
                        'description' => __( 'Start date for availability check.', 'woocommerce-car-rental' ),
                        'type'        => 'string',
                        'format'      => 'date',
                        'required'    => true,
                    ),
                    'end_date' => array(
                        'description' => __( 'End date for availability check.', 'woocommerce-car-rental' ),
                        'type'        => 'string',
                        'format'      => 'date',
                        'required'    => true,
                    ),
                ),
            ),
        ) );

        // Bookings endpoints
        register_rest_route( self::NAMESPACE, '/bookings', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_bookings' ),
                'permission_callback' => array( $this, 'get_bookings_permissions_check' ),
                'args'                => $this->get_booking_collection_params(),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'create_booking' ),
                'permission_callback' => array( $this, 'create_booking_permissions_check' ),
                'args'                => $this->get_booking_args(),
            ),
        ) );

        register_rest_route( self::NAMESPACE, '/bookings/(?P<id>[\d]+)', array(
            'args' => array(
                'id' => array(
                    'description' => __( 'Unique identifier for the booking.', 'woocommerce-car-rental' ),
                    'type'        => 'integer',
                ),
            ),
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_booking' ),
                'permission_callback' => array( $this, 'get_booking_permissions_check' ),
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_booking' ),
                'permission_callback' => array( $this, 'update_booking_permissions_check' ),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( $this, 'cancel_booking' ),
                'permission_callback' => array( $this, 'delete_booking_permissions_check' ),
            ),
        ) );

        // Services endpoint
        register_rest_route( self::NAMESPACE, '/services', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_services' ),
                'permission_callback' => '__return_true', // Public endpoint
            ),
        ) );

        // Insurance options endpoint
        register_rest_route( self::NAMESPACE, '/insurance', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_insurance_options' ),
                'permission_callback' => '__return_true', // Public endpoint
            ),
        ) );
    }

    /**
     * Get rental vehicles
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_vehicles( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $request->get_param( 'per_page' ) ?? 10,
            'paged'          => $request->get_param( 'page' ) ?? 1,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'product_type',
                    'field'    => 'slug',
                    'terms'    => array( 'rental_vehicle' ),
                ),
            ),
        );

        $meta_query = array();

        // Add filters
        $vehicle_type = $request->get_param( 'vehicle_type' );
        if ( $vehicle_type ) {
            $vehicle_type = sanitize_text_field( (string) $vehicle_type );
            $meta_query['relation'] = 'AND';
            $meta_query[]           = array(
                'relation' => 'OR',
                array(
                    'key'     => '_wcrbtw_vehicle_type',
                    'value'   => $vehicle_type,
                    'compare' => '=',
                ),
                array(
                    'key'     => '_rental_vehicle_type',
                    'value'   => $vehicle_type,
                    'compare' => '=',
                ),
            );
        }

        if ( ! empty( $meta_query ) ) {
            $args['meta_query'] = $meta_query;
        }

        $query = new WP_Query( $args );
        $vehicles = array();

        foreach ( $query->posts as $post ) {
            $vehicles[] = $this->prepare_vehicle_for_response( $post->ID );
        }

        $response = new WP_REST_Response( $vehicles );
        $response->header( 'X-WP-Total', (string) $query->found_posts );
        $response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );

        return $response;
    }

    /**
     * Get single rental vehicle
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_vehicle( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $vehicle_id = (int) $request->get_param( 'id' );
        $product = wc_get_product( $vehicle_id );

        if ( ! $product || 'rental_vehicle' !== $product->get_type() ) {
            return new WP_Error( 
                'wcrbtw_vehicle_not_found', 
                __( 'Rental vehicle not found.', 'woocommerce-car-rental' ), 
                array( 'status' => 404 ) 
            );
        }

        return new WP_REST_Response( $this->prepare_vehicle_for_response( $vehicle_id ) );
    }

    /**
     * Create rental vehicle
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function create_vehicle( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $product = new WC_Product_Rental_Vehicle();
        
        // Set basic product data
        $product->set_name( sanitize_text_field( $request->get_param( 'name' ) ) );
        $product->set_status( 'publish' );
        $product->set_catalog_visibility( 'visible' );
        
        // Set rental-specific data
        $this->update_vehicle_data( $product, $request );
        
        // Save the product
        $product->save();

        return new WP_REST_Response( 
            $this->prepare_vehicle_for_response( $product->get_id() ), 
            201 
        );
    }

    /**
     * Update rental vehicle
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function update_vehicle( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $vehicle_id = (int) $request->get_param( 'id' );
        $product = wc_get_product( $vehicle_id );

        if ( ! $product || 'rental_vehicle' !== $product->get_type() ) {
            return new WP_Error( 
                'wcrbtw_vehicle_not_found', 
                __( 'Rental vehicle not found.', 'woocommerce-car-rental' ), 
                array( 'status' => 404 ) 
            );
        }

        // Update product data
        if ( $request->get_param( 'name' ) ) {
            $product->set_name( sanitize_text_field( $request->get_param( 'name' ) ) );
        }

        $this->update_vehicle_data( $product, $request );
        $product->save();

        return new WP_REST_Response( $this->prepare_vehicle_for_response( $vehicle_id ) );
    }

    /**
     * Delete rental vehicle
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function delete_vehicle( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $vehicle_id = (int) $request->get_param( 'id' );
        $product = wc_get_product( $vehicle_id );

        if ( ! $product || 'rental_vehicle' !== $product->get_type() ) {
            return new WP_Error( 
                'wcrbtw_vehicle_not_found', 
                __( 'Rental vehicle not found.', 'woocommerce-car-rental' ), 
                array( 'status' => 404 ) 
            );
        }

        $product->delete( true );

        return new WP_REST_Response( null, 204 );
    }

    /**
     * Get vehicle availability
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_vehicle_availability( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $vehicle_id = (int) $request->get_param( 'id' );
        $start_date = $request->get_param( 'start_date' );
        $end_date = $request->get_param( 'end_date' );

        $product = wc_get_product( $vehicle_id );
        if ( ! $product || 'rental_vehicle' !== $product->get_type() ) {
            return new WP_Error( 
                'wcrbtw_vehicle_not_found', 
                __( 'Rental vehicle not found.', 'woocommerce-car-rental' ), 
                array( 'status' => 404 ) 
            );
        }

        // Get availability data
        $availability_data = function_exists( 'wcrbtw_get_vehicle_availability' )
            ? wcrbtw_get_vehicle_availability( $vehicle_id )
            : array();

        $blocked_dates     = isset( $availability_data['blocked_dates'] ) ? (array) $availability_data['blocked_dates'] : array();
        $quantity_periods  = isset( $availability_data['quantity_periods'] ) ? (array) $availability_data['quantity_periods'] : array();
        $weekly_closures   = isset( $availability_data['weekly_closures'] ) ? (array) $availability_data['weekly_closures'] : array();
        $maintenance_notes = isset( $availability_data['maintenance_notes'] ) ? (string) $availability_data['maintenance_notes'] : '';

        // Check availability for date range
        $is_available = $this->check_availability( $vehicle_id, $start_date, $end_date );

        return new WP_REST_Response( array(
            'vehicle_id'        => $vehicle_id,
            'start_date'        => $start_date,
            'end_date'          => $end_date,
            'available'         => $is_available,
            'blocked_dates'     => array_values( $blocked_dates ),
            'quantity_periods'  => array_values( $quantity_periods ),
            'weekly_closures'   => array_values( $weekly_closures ),
            'maintenance_notes' => $maintenance_notes,
        ) );
    }

    /**
     * Get bookings
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error
     */
    public function get_bookings( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $args = array(
            'limit'  => $request->get_param( 'per_page' ) ?? 10,
            'paged'  => $request->get_param( 'page' ) ?? 1,
            'status' => $request->get_param( 'status' ) ?? 'any',
            'meta_key' => '_contains_rental_vehicles',
            'meta_value' => 'yes',
        );

        $orders = wc_get_orders( $args );
        $bookings = array();

        foreach ( $orders as $order ) {
            $bookings[] = $this->prepare_booking_for_response( $order );
        }

        return new WP_REST_Response( $bookings );
    }

    /**
     * Prepare vehicle data for response
     *
     * @since 1.0.0
     * @param int $vehicle_id Vehicle ID
     * @return array
     */
    private function prepare_vehicle_for_response( int $vehicle_id ): array {
        $product = wc_get_product( $vehicle_id );
        $rental_data = WCRBTW_Admin_Product_Data::get_rental_data( $vehicle_id );

        return array(
            'id'          => $vehicle_id,
            'name'        => $product->get_name(),
            'slug'        => $product->get_slug(),
            'permalink'   => $product->get_permalink(),
            'price'       => $product->get_price(),
            'image'       => wp_get_attachment_url( $product->get_image_id() ),
            'details'     => $rental_data['details'],
            'rates'       => $rental_data['rates'],
            'availability' => $rental_data['availability'],
            'services'    => $rental_data['services'],
            'insurance'   => $rental_data['insurance'],
            'settings'    => $rental_data['settings'],
        );
    }

    /**
     * Prepare booking data for response
     *
     * @since 1.0.0
     * @param WC_Order $order Order object
     * @return array
     */
    private function prepare_booking_for_response( WC_Order $order ): array {
        $rental_items = WCRBTW_HPOS_Compatibility::get_rental_items_from_order( $order );
        $items = array();

        foreach ( $rental_items as $item ) {
            $items[] = array(
                'product_id' => $item->get_product_id(),
                'name'       => $item->get_name(),
                'quantity'   => $item->get_quantity(),
                'total'      => $item->get_total(),
                'start_date' => $item->get_meta( '_rental_start_date' ),
                'end_date'   => $item->get_meta( '_rental_end_date' ),
            );
        }

        return array(
            'id'         => $order->get_id(),
            'status'     => $order->get_status(),
            'total'      => $order->get_total(),
            'currency'   => $order->get_currency(),
            'customer'   => array(
                'id'    => $order->get_customer_id(),
                'email' => $order->get_billing_email(),
                'name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            ),
            'rental_items' => $items,
            'created_date' => $order->get_date_created()->date( 'c' ),
        );
    }

    /**
     * Update vehicle data from request
     *
     * @since 1.0.0
     * @param WC_Product_Rental_Vehicle $product Product object
     * @param WP_REST_Request $request Request object
     * @return void
     */
    private function update_vehicle_data( WC_Product_Rental_Vehicle $product, WP_REST_Request $request ): void {
        $product_id = $product->get_id();

        // Update price
        if ( $request->has_param( 'price' ) ) {
            $price = $request->get_param( 'price' );

            if ( is_array( $price ) ) {
                $price = '';
            }

            $product->set_regular_price( wc_format_decimal( $price ) );
        }

        // Vehicle details
        if ( $request->has_param( 'details' ) ) {
            $details = $request->get_param( 'details' );

            if ( is_array( $details ) ) {
                if ( array_key_exists( 'vehicle_type', $details ) ) {
                    $value = $details['vehicle_type'];
                    update_post_meta( $product_id, '_wcrbtw_vehicle_type', is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '' );
                }

                if ( array_key_exists( 'seats', $details ) ) {
                    $value = $details['seats'];
                    update_post_meta( $product_id, '_wcrbtw_seats', absint( $value ) );
                }

                if ( array_key_exists( 'fuel_type', $details ) ) {
                    $value = $details['fuel_type'];
                    update_post_meta( $product_id, '_wcrbtw_fuel_type', is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '' );
                }

                if ( array_key_exists( 'transmission', $details ) ) {
                    $value = $details['transmission'];
                    update_post_meta( $product_id, '_wcrbtw_transmission', is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '' );
                }

                if ( array_key_exists( 'fleet_quantity', $details ) ) {
                    $value = $details['fleet_quantity'];
                    update_post_meta( $product_id, '_wcrbtw_fleet_quantity', absint( $value ) );
                }

                if ( array_key_exists( 'additional_details', $details ) ) {
                    $value = $details['additional_details'];
                    update_post_meta( $product_id, '_wcrbtw_additional_details', is_scalar( $value ) ? wp_kses_post( (string) $value ) : '' );
                }
            }
        }

        // Rates
        if ( $request->has_param( 'rates' ) ) {
            $rates = $request->get_param( 'rates' );

            if ( is_array( $rates ) ) {
                if ( array_key_exists( 'base_daily_rate', $rates ) ) {
                    $value = $rates['base_daily_rate'];
                    if ( is_array( $value ) ) {
                        $value = '';
                    }

                    update_post_meta( $product_id, '_wcrbtw_base_daily_rate', wc_format_decimal( $value ) );
                }

                if ( array_key_exists( 'seasonal_rates', $rates ) ) {
                    $seasonal_rates = array();

                    if ( is_array( $rates['seasonal_rates'] ) ) {
                        foreach ( $rates['seasonal_rates'] as $rate_data ) {
                            if ( ! is_array( $rate_data ) ) {
                                continue;
                            }

                            $name = '';
                            if ( isset( $rate_data['name'] ) && ! is_array( $rate_data['name'] ) ) {
                                $name = sanitize_text_field( (string) $rate_data['name'] );
                            }

                            if ( '' === $name ) {
                                continue;
                            }

                            $start_date = '';
                            if ( isset( $rate_data['start_date'] ) && ! is_array( $rate_data['start_date'] ) ) {
                                $start_date = sanitize_text_field( (string) $rate_data['start_date'] );
                            }

                            $end_date = '';
                            if ( isset( $rate_data['end_date'] ) && ! is_array( $rate_data['end_date'] ) ) {
                                $end_date = sanitize_text_field( (string) $rate_data['end_date'] );
                            }

                            $rate_value = 0;
                            if ( isset( $rate_data['rate'] ) && ! is_array( $rate_data['rate'] ) ) {
                                $rate_value = $rate_data['rate'];
                            }

                            $priority_value = 0;
                            if ( isset( $rate_data['priority'] ) && ! is_array( $rate_data['priority'] ) ) {
                                $priority_value = $rate_data['priority'];
                            }

                            $recurring_flag = $rate_data['recurring'] ?? 'no';
                            if ( function_exists( 'wcrbtw_is_meta_flag_enabled' ) ) {
                                $recurring = wcrbtw_is_meta_flag_enabled( $recurring_flag ) ? 'yes' : 'no';
                            } else {
                                $recurring = in_array( $recurring_flag, array( '1', 'yes', 'on', 'true' ), true ) ? 'yes' : 'no';
                            }

                            $seasonal_rates[] = array(
                                'name'       => $name,
                                'start_date' => $start_date,
                                'end_date'   => $end_date,
                                'rate'       => wc_format_decimal( $rate_value ),
                                'priority'   => absint( $priority_value ),
                                'recurring'  => $recurring,
                            );
                        }
                    }

                    update_post_meta( $product_id, '_wcrbtw_seasonal_rates', wp_json_encode( $seasonal_rates ) );
                }
            }
        }

        // Availability
        if ( $request->has_param( 'availability' ) ) {
            $availability = $request->get_param( 'availability' );

            if ( is_array( $availability ) ) {
                if ( array_key_exists( 'blocked_dates', $availability ) ) {
                    $blocked_dates = array();

                    if ( is_array( $availability['blocked_dates'] ) ) {
                        foreach ( $availability['blocked_dates'] as $date ) {
                            if ( is_scalar( $date ) ) {
                                $blocked_dates[] = sanitize_text_field( (string) $date );
                            }
                        }
                    }

                    update_post_meta( $product_id, '_wcrbtw_blocked_dates', wp_json_encode( $blocked_dates ) );
                }

                if ( array_key_exists( 'quantity_periods', $availability ) ) {
                    $quantity_periods = array();

                    if ( is_array( $availability['quantity_periods'] ) ) {
                        foreach ( $availability['quantity_periods'] as $period ) {
                            if ( ! is_array( $period ) ) {
                                continue;
                            }

                            $start_date = '';
                            if ( isset( $period['start_date'] ) && ! is_array( $period['start_date'] ) ) {
                                $start_date = sanitize_text_field( (string) $period['start_date'] );
                            }

                            if ( '' === $start_date ) {
                                continue;
                            }

                            $end_date = '';
                            if ( isset( $period['end_date'] ) && ! is_array( $period['end_date'] ) ) {
                                $end_date = sanitize_text_field( (string) $period['end_date'] );
                            }

                            $quantity_value = $period['quantity'] ?? 0;

                            $quantity_periods[] = array(
                                'start_date' => $start_date,
                                'end_date'   => $end_date,
                                'quantity'   => absint( $quantity_value ),
                            );
                        }
                    }

                    update_post_meta( $product_id, '_wcrbtw_quantity_periods', wp_json_encode( $quantity_periods ) );
                }

                if ( array_key_exists( 'weekly_closures', $availability ) ) {
                    $weekly_closures = array();

                    if ( is_array( $availability['weekly_closures'] ) ) {
                        foreach ( $availability['weekly_closures'] as $day ) {
                            $weekly_closures[] = absint( $day );
                        }
                    }

                    update_post_meta( $product_id, '_wcrbtw_weekly_closures', wp_json_encode( $weekly_closures ) );
                }

                if ( array_key_exists( 'maintenance_notes', $availability ) ) {
                    $value = $availability['maintenance_notes'];
                    update_post_meta( $product_id, '_wcrbtw_maintenance_notes', is_scalar( $value ) ? wp_kses_post( (string) $value ) : '' );
                }
            }
        }

        // Services
        if ( $request->has_param( 'services' ) ) {
            $services = $request->get_param( 'services' );
            $sanitized_services = array();

            if ( is_array( $services ) ) {
                foreach ( $services as $service ) {
                    if ( ! is_array( $service ) ) {
                        continue;
                    }

                    $name = isset( $service['name'] ) && ! is_array( $service['name'] ) ? sanitize_text_field( (string) $service['name'] ) : '';

                    if ( '' === $name ) {
                        continue;
                    }

                    $price_type = isset( $service['price_type'] ) && ! is_array( $service['price_type'] ) ? sanitize_text_field( (string) $service['price_type'] ) : 'flat';
                    $price      = isset( $service['price'] ) && ! is_array( $service['price'] ) ? wc_format_decimal( $service['price'] ) : wc_format_decimal( 0 );
                    $description = isset( $service['description'] ) && ! is_array( $service['description'] ) ? wp_kses_post( (string) $service['description'] ) : '';
                    $enabled_flag = $service['enabled'] ?? 'no';

                    if ( function_exists( 'wcrbtw_is_meta_flag_enabled' ) ) {
                        $enabled = wcrbtw_is_meta_flag_enabled( $enabled_flag ) ? 'yes' : 'no';
                    } else {
                        $enabled = in_array( $enabled_flag, array( '1', 'yes', 'on', 'true' ), true ) ? 'yes' : 'no';
                    }

                    $sanitized_services[] = array(
                        'name'        => $name,
                        'price_type'  => $price_type,
                        'price'       => $price,
                        'description' => $description,
                        'enabled'     => $enabled,
                    );
                }
            }

            update_post_meta( $product_id, '_wcrbtw_services', wp_json_encode( $sanitized_services ) );
        }

        // Insurance
        if ( $request->has_param( 'insurance' ) ) {
            $insurance = $request->get_param( 'insurance' );
            $sanitized_insurance = array();

            if ( is_array( $insurance ) ) {
                foreach ( $insurance as $option ) {
                    if ( ! is_array( $option ) ) {
                        continue;
                    }

                    $name = isset( $option['name'] ) && ! is_array( $option['name'] ) ? sanitize_text_field( (string) $option['name'] ) : '';

                    if ( '' === $name ) {
                        continue;
                    }

                    $cost_type  = isset( $option['cost_type'] ) && ! is_array( $option['cost_type'] ) ? sanitize_text_field( (string) $option['cost_type'] ) : 'daily';
                    $cost       = isset( $option['cost'] ) && ! is_array( $option['cost'] ) ? wc_format_decimal( $option['cost'] ) : wc_format_decimal( 0 );
                    $deductible = isset( $option['deductible'] ) && ! is_array( $option['deductible'] ) ? wc_format_decimal( $option['deductible'] ) : wc_format_decimal( 0 );
                    $description = isset( $option['description'] ) && ! is_array( $option['description'] ) ? wp_kses_post( (string) $option['description'] ) : '';
                    $enabled_flag = $option['enabled'] ?? 'no';

                    if ( function_exists( 'wcrbtw_is_meta_flag_enabled' ) ) {
                        $enabled = wcrbtw_is_meta_flag_enabled( $enabled_flag ) ? 'yes' : 'no';
                    } else {
                        $enabled = in_array( $enabled_flag, array( '1', 'yes', 'on', 'true' ), true ) ? 'yes' : 'no';
                    }

                    $sanitized_insurance[] = array(
                        'name'        => $name,
                        'cost_type'   => $cost_type,
                        'cost'        => $cost,
                        'deductible'  => $deductible,
                        'description' => $description,
                        'enabled'     => $enabled,
                    );
                }
            }

            update_post_meta( $product_id, '_wcrbtw_insurance', wp_json_encode( $sanitized_insurance ) );
        }

        // Settings
        if ( $request->has_param( 'settings' ) ) {
            $settings = $request->get_param( 'settings' );

            if ( is_array( $settings ) ) {
                if ( array_key_exists( 'min_days', $settings ) ) {
                    update_post_meta( $product_id, '_wcrbtw_min_days', absint( $settings['min_days'] ) );
                }

                if ( array_key_exists( 'max_days', $settings ) ) {
                    update_post_meta( $product_id, '_wcrbtw_max_days', absint( $settings['max_days'] ) );
                }

                if ( array_key_exists( 'extra_day_hour', $settings ) ) {
                    update_post_meta( $product_id, '_wcrbtw_extra_day_hour', absint( $settings['extra_day_hour'] ) );
                }

                if ( array_key_exists( 'security_deposit', $settings ) ) {
                    $value = $settings['security_deposit'];
                    if ( is_array( $value ) ) {
                        $value = '';
                    }

                    update_post_meta( $product_id, '_wcrbtw_security_deposit', wc_format_decimal( $value ) );
                }

                if ( array_key_exists( 'cancellation_policy', $settings ) ) {
                    $value = $settings['cancellation_policy'];
                    update_post_meta( $product_id, '_wcrbtw_cancellation_policy', is_scalar( $value ) ? wp_kses_post( (string) $value ) : '' );
                }

                if ( array_key_exists( 'additional_settings', $settings ) ) {
                    $value = $settings['additional_settings'];
                    update_post_meta( $product_id, '_wcrbtw_additional_settings', is_scalar( $value ) ? wp_kses_post( (string) $value ) : '' );
                }
            }
        }
    }

    /**
     * Check availability for date range
     *
     * @since 1.0.0
     * @param int $vehicle_id Vehicle ID
     * @param string $start_date Start date
     * @param string $end_date End date
     * @return bool
     */
    private function check_availability( int $vehicle_id, string $start_date, string $end_date ): bool {
        if ( function_exists( 'wcrbtw_is_vehicle_available' ) ) {
            return wcrbtw_is_vehicle_available( $vehicle_id, $start_date, $end_date );
        }

        $availability_data = function_exists( 'wcrbtw_get_vehicle_availability' )
            ? wcrbtw_get_vehicle_availability( $vehicle_id )
            : array();

        $blocked_dates    = isset( $availability_data['blocked_dates'] ) ? (array) $availability_data['blocked_dates'] : array();
        $weekly_closures  = isset( $availability_data['weekly_closures'] ) ? (array) $availability_data['weekly_closures'] : array();
        $quantity_periods = isset( $availability_data['quantity_periods'] ) ? (array) $availability_data['quantity_periods'] : array();

        // Convert date strings to DateTime objects
        $start     = new DateTime( $start_date );
        $end       = new DateTime( $end_date );
        $interval  = new DateInterval( 'P1D' );
        $date_iter = new DatePeriod( $start, $interval, ( clone $end )->modify( '+1 day' ) );

        // Check if any date in range is blocked or within weekly closures
        foreach ( $date_iter as $date ) {
            $date_string = $date->format( 'Y-m-d' );
            $day_of_week = (int) $date->format( 'w' );

            if ( in_array( $date_string, $blocked_dates, true ) ) {
                return false;
            }

            if ( in_array( $day_of_week, $weekly_closures, true ) ) {
                return false;
            }
        }

        if ( ! empty( $quantity_periods ) && function_exists( 'wcrbtw_get_booked_quantity' ) ) {
            foreach ( $quantity_periods as $period ) {
                if ( empty( $period['start_date'] ) || empty( $period['end_date'] ) ) {
                    continue;
                }

                try {
                    $period_start = new DateTime( (string) $period['start_date'] );
                    $period_end   = new DateTime( (string) $period['end_date'] );
                } catch ( Exception $exception ) {
                    continue;
                }

                if ( $start <= $period_end && $end >= $period_start ) {
                    $available_qty = isset( $period['quantity'] ) ? absint( $period['quantity'] ) : 0;
                    $booked_qty    = wcrbtw_get_booked_quantity( $vehicle_id, $start_date, $end_date );

                    if ( $booked_qty >= $available_qty && 0 !== $available_qty ) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Permission check for getting items
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function get_items_permissions_check( WP_REST_Request $request ): bool|WP_Error {
        return true; // Public endpoint for reading vehicles
    }

    /**
     * Permission check for getting single item
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function get_item_permissions_check( WP_REST_Request $request ): bool|WP_Error {
        return true; // Public endpoint for reading single vehicle
    }

    /**
     * Permission check for creating items
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function create_item_permissions_check( WP_REST_Request $request ): bool|WP_Error {
        if ( ! current_user_can( 'edit_products' ) ) {
            return new WP_Error( 
                'wcrbtw_rest_cannot_create', 
                __( 'Sorry, you cannot create resources.', 'woocommerce-car-rental' ), 
                array( 'status' => rest_authorization_required_code() ) 
            );
        }
        return true;
    }

    /**
     * Permission check for updating items
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function update_item_permissions_check( WP_REST_Request $request ): bool|WP_Error {
        $vehicle_id = (int) $request->get_param( 'id' );
        
        if ( ! current_user_can( 'edit_product', $vehicle_id ) ) {
            return new WP_Error( 
                'wcrbtw_rest_cannot_edit', 
                __( 'Sorry, you cannot edit this resource.', 'woocommerce-car-rental' ), 
                array( 'status' => rest_authorization_required_code() ) 
            );
        }
        return true;
    }

    /**
     * Permission check for deleting items
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function delete_item_permissions_check( WP_REST_Request $request ): bool|WP_Error {
        $vehicle_id = (int) $request->get_param( 'id' );
        
        if ( ! current_user_can( 'delete_product', $vehicle_id ) ) {
            return new WP_Error( 
                'wcrbtw_rest_cannot_delete', 
                __( 'Sorry, you cannot delete this resource.', 'woocommerce-car-rental' ), 
                array( 'status' => rest_authorization_required_code() ) 
            );
        }
        return true;
    }

    /**
     * Permission check for getting bookings
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function get_bookings_permissions_check( WP_REST_Request $request ): bool|WP_Error {
        if ( ! current_user_can( 'edit_shop_orders' ) ) {
            return new WP_Error( 
                'wcrbtw_rest_cannot_view', 
                __( 'Sorry, you cannot view bookings.', 'woocommerce-car-rental' ), 
                array( 'status' => rest_authorization_required_code() ) 
            );
        }
        return true;
    }

    /**
     * Permission check for getting single booking
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function get_booking_permissions_check( WP_REST_Request $request ): bool|WP_Error {
        $order_id = (int) $request->get_param( 'id' );
        
        if ( ! current_user_can( 'edit_shop_order', $order_id ) ) {
            // Check if user is the customer
            $order = wc_get_order( $order_id );
            if ( $order && get_current_user_id() === $order->get_customer_id() ) {
                return true;
            }
            
            return new WP_Error( 
                'wcrbtw_rest_cannot_view', 
                __( 'Sorry, you cannot view this booking.', 'woocommerce-car-rental' ), 
                array( 'status' => rest_authorization_required_code() ) 
            );
        }
        return true;
    }

    /**
     * Permission check for creating bookings
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function create_booking_permissions_check( WP_REST_Request $request ): bool|WP_Error {
        // Allow authenticated users to create bookings
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 
                'wcrbtw_rest_cannot_create', 
                __( 'Sorry, you must be logged in to create a booking.', 'woocommerce-car-rental' ), 
                array( 'status' => rest_authorization_required_code() ) 
            );
        }
        return true;
    }

    /**
     * Permission check for updating bookings
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function update_booking_permissions_check( WP_REST_Request $request ): bool|WP_Error {
        $order_id = (int) $request->get_param( 'id' );
        
        if ( ! current_user_can( 'edit_shop_order', $order_id ) ) {
            return new WP_Error( 
                'wcrbtw_rest_cannot_edit', 
                __( 'Sorry, you cannot edit this booking.', 'woocommerce-car-rental' ), 
                array( 'status' => rest_authorization_required_code() ) 
            );
        }
        return true;
    }

    /**
     * Permission check for deleting bookings
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object
     * @return bool|WP_Error
     */
    public function delete_booking_permissions_check( WP_REST_Request $request ): bool|WP_Error {
        $order_id = (int) $request->get_param( 'id' );
        
        if ( ! current_user_can( 'delete_shop_order', $order_id ) ) {
            return new WP_Error( 
                'wcrbtw_rest_cannot_delete', 
                __( 'Sorry, you cannot delete this booking.', 'woocommerce-car-rental' ), 
                array( 'status' => rest_authorization_required_code() ) 
            );
        }
        return true;
    }

    /**
     * Get collection parameters
     *
     * @since 1.0.0
     * @return array
     */
    private function get_collection_params(): array {
        return array(
            'page' => array(
                'description'       => __( 'Current page of the collection.', 'woocommerce-car-rental' ),
                'type'              => 'integer',
                'default'           => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'description'       => __( 'Maximum number of items to be returned in result set.', 'woocommerce-car-rental' ),
                'type'              => 'integer',
                'default'           => 10,
                'minimum'           => 1,
                'maximum'           => 100,
                'sanitize_callback' => 'absint',
            ),
            'vehicle_type' => array(
                'description' => __( 'Filter by vehicle type.', 'woocommerce-car-rental' ),
                'type'        => 'string',
                'enum'        => array( 'car', 'scooter', 'van', 'suv', 'truck' ),
            ),
        );
    }

    /**
     * Get booking collection parameters
     *
     * @since 1.0.0
     * @return array
     */
    private function get_booking_collection_params(): array {
        return array(
            'page' => array(
                'description'       => __( 'Current page of the collection.', 'woocommerce-car-rental' ),
                'type'              => 'integer',
                'default'           => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'description'       => __( 'Maximum number of items to be returned in result set.', 'woocommerce-car-rental' ),
                'type'              => 'integer',
                'default'           => 10,
                'minimum'           => 1,
                'maximum'           => 100,
                'sanitize_callback' => 'absint',
            ),
            'status' => array(
                'description' => __( 'Filter by booking status.', 'woocommerce-car-rental' ),
                'type'        => 'string',
                'default'     => 'any',
            ),
        );
    }

    /**
     * Get booking arguments
     *
     * @since 1.0.0
     * @return array
     */
    private function get_booking_args(): array {
        return array(
            'vehicle_id' => array(
                'description' => __( 'Vehicle ID for the booking.', 'woocommerce-car-rental' ),
                'type'        => 'integer',
                'required'    => true,
            ),
            'start_date' => array(
                'description' => __( 'Rental start date.', 'woocommerce-car-rental' ),
                'type'        => 'string',
                'format'      => 'date',
                'required'    => true,
            ),
            'end_date' => array(
                'description' => __( 'Rental end date.', 'woocommerce-car-rental' ),
                'type'        => 'string',
                'format'      => 'date',
                'required'    => true,
            ),
            'services' => array(
                'description' => __( 'Additional services IDs.', 'woocommerce-car-rental' ),
                'type'        => 'array',
                'items'       => array(
                    'type' => 'integer',
                ),
            ),
            'insurance' => array(
                'description' => __( 'Insurance option ID.', 'woocommerce-car-rental' ),
                'type'        => 'integer',
            ),
        );
    }

    /**
     * Get endpoint args for item schema
     *
     * @since 1.0.0
     * @param string $method HTTP method
     * @return array
     */
    private function get_endpoint_args_for_item_schema( string $method = WP_REST_Server::CREATABLE ): array {
        return array(
            'name' => array(
                'description' => __( 'Vehicle name.', 'woocommerce-car-rental' ),
                'type'        => 'string',
                'required'    => ( WP_REST_Server::CREATABLE === $method ),
            ),
            'price' => array(
                'description' => __( 'Base rental price.', 'woocommerce-car-rental' ),
                'type'        => 'number',
            ),
            'details' => array(
                'description' => __( 'Vehicle details.', 'woocommerce-car-rental' ),
                'type'        => 'object',
            ),
            'rates' => array(
                'description' => __( 'Rental rates configuration.', 'woocommerce-car-rental' ),
                'type'        => 'object',
            ),
            'availability' => array(
                'description' => __( 'Availability configuration.', 'woocommerce-car-rental' ),
                'type'        => 'object',
            ),
            'services' => array(
                'description' => __( 'Available services.', 'woocommerce-car-rental' ),
                'type'        => 'array',
            ),
            'insurance' => array(
                'description' => __( 'Insurance options.', 'woocommerce-car-rental' ),
                'type'        => 'array',
            ),
            'settings' => array(
                'description' => __( 'Rental settings.', 'woocommerce-car-rental' ),
                'type'        => 'object',
            ),
        );
    }

    /**
     * Get public item schema
     *
     * @since 1.0.0
     * @return array
     */
    public function get_public_item_schema(): array {
        return array(
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'rental_vehicle',
            'type'       => 'object',
            'properties' => array(
                'id' => array(
                    'description' => __( 'Unique identifier for the vehicle.', 'woocommerce-car-rental' ),
                    'type'        => 'integer',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
                'name' => array(
                    'description' => __( 'Vehicle name.', 'woocommerce-car-rental' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                ),
                'slug' => array(
                    'description' => __( 'Vehicle slug.', 'woocommerce-car-rental' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                ),
                'permalink' => array(
                    'description' => __( 'Vehicle URL.', 'woocommerce-car-rental' ),
                    'type'        => 'string',
                    'format'      => 'uri',
                    'context'     => array( 'view' ),
                    'readonly'    => true,
                ),
                'price' => array(
                    'description' => __( 'Base rental price.', 'woocommerce-car-rental' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                ),
                'image' => array(
                    'description' => __( 'Vehicle image URL.', 'woocommerce-car-rental' ),
                    'type'        => 'string',
                    'format'      => 'uri',
                    'context'     => array( 'view' ),
                    'readonly'    => true,
                ),
                'details' => array(
                    'description' => __( 'Vehicle details.', 'woocommerce-car-rental' ),
                    'type'        => 'object',
                    'context'     => array( 'view', 'edit' ),
                ),
                'rates' => array(
                    'description' => __( 'Rental rates.', 'woocommerce-car-rental' ),
                    'type'        => 'object',
                    'context'     => array( 'view', 'edit' ),
                ),
                'availability' => array(
                    'description' => __( 'Availability data.', 'woocommerce-car-rental' ),
                    'type'        => 'object',
                    'context'     => array( 'view', 'edit' ),
                ),
                'services' => array(
                    'description' => __( 'Available services.', 'woocommerce-car-rental' ),
                    'type'        => 'array',
                    'context'     => array( 'view', 'edit' ),
                ),
                'insurance' => array(
                    'description' => __( 'Insurance options.', 'woocommerce-car-rental' ),
                    'type'        => 'array',
                    'context'     => array( 'view', 'edit' ),
                ),
                'settings' => array(
                    'description' => __( 'Rental settings.', 'woocommerce-car-rental' ),
                    'type'        => 'object',
                    'context'     => array( 'view', 'edit' ),
                ),
            ),
        );
    }
}

// Initialize the REST API Controller
if ( class_exists( 'WooCommerce' ) ) {
    WCRBTW_REST_API_Controller::get_instance();
}
