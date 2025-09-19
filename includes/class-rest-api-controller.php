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
            'posts_per_page' => $request->get_param( 'per_page' ) ?? 10,
            'paged'          => $request->get_param( 'page' ) ?? 1,
            'meta_query'     => array(
                array(
                    'key'     => '_product_type',
                    'value'   => 'rental_vehicle',
                    'compare' => '=',
                ),
            ),
        );

        // Add filters
        if ( $vehicle_type = $request->get_param( 'vehicle_type' ) ) {
            $args['meta_query'][] = array(
                'key'     => '_rental_vehicle_type',
                'value'   => $vehicle_type,
                'compare' => '=',
            );
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
        $availability_data = get_post_meta( $vehicle_id, '_rental_availability', true ) ?: array();
        
        // Check availability for date range
        $is_available = $this->check_availability( $vehicle_id, $start_date, $end_date );

        return new WP_REST_Response( array(
            'vehicle_id' => $vehicle_id,
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'available'  => $is_available,
            'blocked_dates' => $availability_data['blocked_dates'] ?? array(),
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
        // Update price
        if ( $request->get_param( 'price' ) ) {
            $product->set_regular_price( wc_format_decimal( $request->get_param( 'price' ) ) );
        }

        // Update rental-specific meta
        $meta_fields = array( 'details', 'rates', 'availability', 'services', 'insurance', 'settings' );
        
        foreach ( $meta_fields as $field ) {
            if ( $request->get_param( $field ) ) {
                update_post_meta( 
                    $product->get_id(), 
                    "_rental_{$field}", 
                    $request->get_param( $field ) 
                );
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
        $availability_data = get_post_meta( $vehicle_id, '_rental_availability', true ) ?: array();
        $blocked_dates = $availability_data['blocked_dates'] ?? array();

        // Convert date strings to DateTime objects
        $start = new DateTime( $start_date );
        $end = new DateTime( $end_date );
        $interval = new DateInterval( 'P1D' );
        $date_range = new DatePeriod( $start, $interval, $end->modify( '+1 day' ) );

        // Check if any date in range is blocked
        foreach ( $date_range as $date ) {
            if ( in_array( $date->format( 'Y-m-d' ), $blocked_dates, true ) ) {
                return false;
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
