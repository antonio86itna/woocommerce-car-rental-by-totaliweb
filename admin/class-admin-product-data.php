<?php
/**
 * Admin Product Data Interface for Rental Vehicle
 *
 * Handles the product data tabs and fields for rental vehicle products
 *
 * @package WooCommerce_Car_Rental
 * @subpackage Admin
 * @since 1.0.0
 */

declare( strict_types=1 );

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin Product Data Class
 *
 * @class WCRBTW_Admin_Product_Data
 * @version 1.0.0
 */
final class WCRBTW_Admin_Product_Data {

    /**
     * Instance of this class
     *
     * @var ?WCRBTW_Admin_Product_Data
     */
    private static ?WCRBTW_Admin_Product_Data $instance = null;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Get singleton instance
     *
     * @since 1.0.0
     * @return WCRBTW_Admin_Product_Data
     */
    public static function get_instance(): WCRBTW_Admin_Product_Data {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize hooks
     *
     * @since 1.0.0
     * @return void
     */
    private function init_hooks(): void {
        // Add product data tabs
        add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_rental_tabs' ), 50 );
        
        // Add tab panels
        add_action( 'woocommerce_product_data_panels', array( $this, 'add_rental_panels' ) );
        
        // Save product data
        add_action( 'woocommerce_process_product_meta', array( $this, 'save_rental_data' ), 10, 2 );
        
        // Remove unnecessary tabs for rental products
        add_filter( 'woocommerce_product_data_tabs', array( $this, 'modify_product_tabs' ), 100 );
        
        // Enqueue admin scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        
        // Add inline script for immediate execution
        add_action( 'admin_footer', array( $this, 'add_inline_script' ) );
    }

    /**
     * Add rental vehicle tabs to product data
     *
     * @since 1.0.0
     * @param array $tabs Existing tabs
     * @return array Modified tabs array
     */
    public function add_rental_tabs( array $tabs ): array {
        global $post;
        
        // Check if we're editing a product
        $product_type = 'simple';
        if ( $post && $post->ID ) {
            $product = wc_get_product( $post->ID );
            if ( $product ) {
                $product_type = $product->get_type();
            }
        }
        
        // Details tab
        $tabs['rental_details'] = array(
            'label'    => __( 'Details', 'woocommerce-car-rental' ),
            'target'   => 'rental_details_data',
            'class'    => array( 'rental_details_tab', 'rental_vehicle_tab', 'show_if_rental_vehicle' ),
            'priority' => 11,
        );

        // Rates tab
        $tabs['rental_rates'] = array(
            'label'    => __( 'Rates', 'woocommerce-car-rental' ),
            'target'   => 'rental_rates_data',
            'class'    => array( 'rental_rates_tab', 'rental_vehicle_tab', 'show_if_rental_vehicle' ),
            'priority' => 12,
        );

        // Availability tab
        $tabs['rental_availability'] = array(
            'label'    => __( 'Availability', 'woocommerce-car-rental' ),
            'target'   => 'rental_availability_data',
            'class'    => array( 'rental_availability_tab', 'rental_vehicle_tab', 'show_if_rental_vehicle' ),
            'priority' => 13,
        );

        // Services tab
        $tabs['rental_services'] = array(
            'label'    => __( 'Services', 'woocommerce-car-rental' ),
            'target'   => 'rental_services_data',
            'class'    => array( 'rental_services_tab', 'rental_vehicle_tab', 'show_if_rental_vehicle' ),
            'priority' => 14,
        );

        // Insurance tab
        $tabs['rental_insurance'] = array(
            'label'    => __( 'Insurance', 'woocommerce-car-rental' ),
            'target'   => 'rental_insurance_data',
            'class'    => array( 'rental_insurance_tab', 'rental_vehicle_tab', 'show_if_rental_vehicle' ),
            'priority' => 15,
        );

        // Settings tab
        $tabs['rental_settings'] = array(
            'label'    => __( 'Settings', 'woocommerce-car-rental' ),
            'target'   => 'rental_settings_data',
            'class'    => array( 'rental_settings_tab', 'rental_vehicle_tab', 'show_if_rental_vehicle' ),
            'priority' => 16,
        );

        return $tabs;
    }

    /**
     * Modify product tabs for rental vehicles
     *
     * @since 1.0.0
     * @param array $tabs Product data tabs
     * @return array Modified tabs
     */
    public function modify_product_tabs( array $tabs ): array {
        // Hide unnecessary tabs for rental vehicles
        $tabs_to_hide = array( 'shipping', 'linked_product', 'advanced' );
        
        foreach ( $tabs_to_hide as $tab ) {
            if ( isset( $tabs[ $tab ] ) ) {
                $tabs[ $tab ]['class'][] = 'hide_if_rental_vehicle';
            }
        }

        // Modify inventory tab
        if ( isset( $tabs['inventory'] ) ) {
            $tabs['inventory']['class'][] = 'show_if_rental_vehicle';
        }

        return $tabs;
    }

    /**
     * Add rental panels content
     *
     * @since 1.0.0
     * @return void
     */
    public function add_rental_panels(): void {
        global $post, $thepostid, $product_object;

        // Ensure we have a product ID
        $product_id = empty( $thepostid ) ? $post->ID : $thepostid;

        // Include panel templates
        include WCRBTW_PLUGIN_DIR . 'admin/views/panel-rental-details.php';
        include WCRBTW_PLUGIN_DIR . 'admin/views/panel-rental-rates.php';
        include WCRBTW_PLUGIN_DIR . 'admin/views/panel-rental-availability.php';
        include WCRBTW_PLUGIN_DIR . 'admin/views/panel-rental-services.php';
        include WCRBTW_PLUGIN_DIR . 'admin/views/panel-rental-insurance.php';
        include WCRBTW_PLUGIN_DIR . 'admin/views/panel-rental-settings.php';
    }

    /**
     * Save rental vehicle data
     *
     * @since 1.0.0
     * @param int $post_id Product post ID
     * @param WP_Post $post Product post object
     * @return void
     */
    public function save_rental_data( int $post_id, WP_Post $post ): void {
        // Check product type
        $product_type = isset( $_POST['product-type'] ) ? sanitize_text_field( $_POST['product-type'] ) : '';
        
        if ( 'rental_vehicle' !== $product_type ) {
            return;
        }

        // Verify nonce
        if ( ! isset( $_POST['woocommerce_meta_nonce'] ) || 
             ! wp_verify_nonce( $_POST['woocommerce_meta_nonce'], 'woocommerce_save_data' ) ) {
            return;
        }

        // Save Details tab data
        $this->save_details_data( $post_id );
        
        // Save Rates tab data
        $this->save_rates_data( $post_id );
        
        // Save Availability tab data
        $this->save_availability_data( $post_id );
        
        // Save Services tab data
        $this->save_services_data( $post_id );
        
        // Save Insurance tab data
        $this->save_insurance_data( $post_id );
        
        // Save Settings tab data
        $this->save_settings_data( $post_id );

        /**
         * Action after saving rental data
         *
         * @since 1.0.0
         * @param int $post_id Product ID
         * @param array $_POST Posted data
         */
        do_action( 'wcrbtw_after_save_rental_data', $post_id, $_POST );
    }

    /**
     * Save details tab data
     *
     * @since 1.0.0
     * @param int $post_id Product ID
     * @return void
     */
    private function save_details_data( int $post_id ): void {
        // Save each field as individual post meta for Elementor/ACF compatibility
        
        // Vehicle type
        if ( isset( $_POST['_rental_vehicle_type'] ) ) {
            update_post_meta( $post_id, '_wcrbtw_vehicle_type', sanitize_text_field( $_POST['_rental_vehicle_type'] ) );
        }

        // Number of seats
        if ( isset( $_POST['_rental_seats'] ) ) {
            update_post_meta( $post_id, '_wcrbtw_seats', absint( $_POST['_rental_seats'] ) );
        }

        // Fuel type
        if ( isset( $_POST['_rental_fuel_type'] ) ) {
            update_post_meta( $post_id, '_wcrbtw_fuel_type', sanitize_text_field( $_POST['_rental_fuel_type'] ) );
        }

        // Transmission
        if ( isset( $_POST['_rental_transmission'] ) ) {
            update_post_meta( $post_id, '_wcrbtw_transmission', sanitize_text_field( $_POST['_rental_transmission'] ) );
        }

        // Fleet quantity
        if ( isset( $_POST['_rental_fleet_quantity'] ) ) {
            update_post_meta( $post_id, '_wcrbtw_fleet_quantity', absint( $_POST['_rental_fleet_quantity'] ) );
        }

        // Additional details
        if ( isset( $_POST['_rental_additional_details'] ) ) {
            update_post_meta( $post_id, '_wcrbtw_additional_details', wp_kses_post( $_POST['_rental_additional_details'] ) );
        }
    }

    /**
     * Save rates tab data
     *
     * @since 1.0.0
     * @param int $post_id Product ID
     * @return void
     */
    private function save_rates_data( int $post_id ): void {
        // Base daily rate - support both legacy and new field prefixes.
        $base_rate_field = null;

        if ( isset( $_POST['_wcrbtw_base_daily_rate'] ) ) {
            $base_rate_field = '_wcrbtw_base_daily_rate';
        } elseif ( isset( $_POST['_rental_base_daily_rate'] ) ) {
            $base_rate_field = '_rental_base_daily_rate';
        }

        if ( null !== $base_rate_field ) {
            $base_daily_rate_value = wp_unslash( $_POST[ $base_rate_field ] );

            if ( is_array( $base_daily_rate_value ) ) {
                $base_daily_rate_value = '';
            }

            $base_daily_rate = wc_format_decimal( $base_daily_rate_value );
            update_post_meta( $post_id, '_wcrbtw_base_daily_rate', $base_daily_rate );
        }

        // Seasonal rates - save as JSON for structured data and support both prefixes.
        $seasonal_rates_field = null;

        if ( isset( $_POST['_wcrbtw_seasonal_rates'] ) && is_array( $_POST['_wcrbtw_seasonal_rates'] ) ) {
            $seasonal_rates_field = '_wcrbtw_seasonal_rates';
        } elseif ( isset( $_POST['_rental_seasonal_rates'] ) && is_array( $_POST['_rental_seasonal_rates'] ) ) {
            $seasonal_rates_field = '_rental_seasonal_rates';
        }

        if ( null !== $seasonal_rates_field ) {
            $seasonal_rates_input = wp_unslash( $_POST[ $seasonal_rates_field ] );

            if ( is_array( $seasonal_rates_input ) ) {
                $seasonal_rates = array();

                foreach ( $seasonal_rates_input as $rate_data ) {
                    if ( ! is_array( $rate_data ) ) {
                        continue;
                    }

                    $name = '';
                    if ( isset( $rate_data['name'] ) && ! is_array( $rate_data['name'] ) ) {
                        $name = sanitize_text_field( $rate_data['name'] );
                    }

                    if ( '' === $name ) {
                        continue;
                    }

                    $start_date = '';
                    if ( isset( $rate_data['start_date'] ) && ! is_array( $rate_data['start_date'] ) ) {
                        $start_date = sanitize_text_field( $rate_data['start_date'] );
                    }

                    $end_date = '';
                    if ( isset( $rate_data['end_date'] ) && ! is_array( $rate_data['end_date'] ) ) {
                        $end_date = sanitize_text_field( $rate_data['end_date'] );
                    }

                    $rate_value = 0;
                    if ( isset( $rate_data['rate'] ) && ! is_array( $rate_data['rate'] ) ) {
                        $rate_value = $rate_data['rate'];
                    }
                    $rate = wc_format_decimal( $rate_value );

                    $priority_value = 0;
                    if ( isset( $rate_data['priority'] ) && ! is_array( $rate_data['priority'] ) ) {
                        $priority_value = $rate_data['priority'];
                    }
                    $priority = absint( $priority_value );

                    $recurring = '';
                    if ( isset( $rate_data['recurring'] ) && ! is_array( $rate_data['recurring'] ) ) {
                        $recurring = sanitize_text_field( $rate_data['recurring'] );
                    }

                    $seasonal_rates[] = array(
                        'name'       => $name,
                        'start_date' => $start_date,
                        'end_date'   => $end_date,
                        'rate'       => $rate,
                        'priority'   => $priority,
                        'recurring'  => in_array( $recurring, array( '1', 'yes', 'on', 'true' ), true ) ? 'yes' : 'no',
                    );
                }

                // Save as JSON string for better compatibility.
                update_post_meta( $post_id, '_wcrbtw_seasonal_rates', wp_json_encode( $seasonal_rates ) );
            }
        }
    }

    /**
     * Save availability tab data
     *
     * @since 1.0.0
     * @param int $post_id Product ID
     * @return void
     */
    private function save_availability_data( int $post_id ): void {
        // Blocked dates
        if ( isset( $_POST['_rental_blocked_dates'] ) && is_array( $_POST['_rental_blocked_dates'] ) ) {
            $blocked_dates = array_map( 'sanitize_text_field', $_POST['_rental_blocked_dates'] );
            update_post_meta( $post_id, '_wcrbtw_blocked_dates', wp_json_encode( $blocked_dates ) );
        }

        // Quantity per period
        if ( isset( $_POST['_rental_quantity_periods'] ) && is_array( $_POST['_rental_quantity_periods'] ) ) {
            $quantity_periods = array();
            
            foreach ( $_POST['_rental_quantity_periods'] as $period ) {
                if ( ! empty( $period['start_date'] ) ) {
                    $quantity_periods[] = array(
                        'start_date' => sanitize_text_field( $period['start_date'] ),
                        'end_date'   => sanitize_text_field( $period['end_date'] ?? '' ),
                        'quantity'   => absint( $period['quantity'] ?? 0 ),
                    );
                }
            }
            
            update_post_meta( $post_id, '_wcrbtw_quantity_periods', wp_json_encode( $quantity_periods ) );
        }

        // Weekly closures
        if ( isset( $_POST['_rental_weekly_closures'] ) && is_array( $_POST['_rental_weekly_closures'] ) ) {
            $weekly_closures = array_map( 'absint', $_POST['_rental_weekly_closures'] );
            update_post_meta( $post_id, '_wcrbtw_weekly_closures', wp_json_encode( $weekly_closures ) );
        }

        // Maintenance notes
        if ( isset( $_POST['_rental_maintenance_notes'] ) ) {
            update_post_meta( $post_id, '_wcrbtw_maintenance_notes', wp_kses_post( $_POST['_rental_maintenance_notes'] ) );
        }
    }

    /**
     * Save services tab data
     *
     * @since 1.0.0
     * @param int $post_id Product ID
     * @return void
     */
    private function save_services_data( int $post_id ): void {
        if ( isset( $_POST['_rental_services'] ) && is_array( $_POST['_rental_services'] ) ) {
            $services = array();
            
            foreach ( $_POST['_rental_services'] as $service ) {
                if ( ! empty( $service['name'] ) ) {
                    $services[] = array(
                        'name'        => sanitize_text_field( $service['name'] ),
                        'price_type'  => sanitize_text_field( $service['price_type'] ?? 'flat' ),
                        'price'       => wc_format_decimal( $service['price'] ?? 0 ),
                        'description' => wp_kses_post( $service['description'] ?? '' ),
                        'enabled'     => isset( $service['enabled'] ) ? 'yes' : 'no',
                    );
                }
            }
            
            update_post_meta( $post_id, '_wcrbtw_services', wp_json_encode( $services ) );
        }
    }

    /**
     * Save insurance tab data
     *
     * @since 1.0.0
     * @param int $post_id Product ID
     * @return void
     */
    private function save_insurance_data( int $post_id ): void {
        if ( isset( $_POST['_rental_insurance'] ) && is_array( $_POST['_rental_insurance'] ) ) {
            $insurance_options = array();
            
            foreach ( $_POST['_rental_insurance'] as $insurance ) {
                if ( ! empty( $insurance['name'] ) ) {
                    $insurance_options[] = array(
                        'name'        => sanitize_text_field( $insurance['name'] ),
                        'cost_type'   => sanitize_text_field( $insurance['cost_type'] ?? 'daily' ),
                        'cost'        => wc_format_decimal( $insurance['cost'] ?? 0 ),
                        'deductible'  => wc_format_decimal( $insurance['deductible'] ?? 0 ),
                        'description' => wp_kses_post( $insurance['description'] ?? '' ),
                        'enabled'     => isset( $insurance['enabled'] ) ? 'yes' : 'no',
                    );
                }
            }
            
            update_post_meta( $post_id, '_wcrbtw_insurance', wp_json_encode( $insurance_options ) );
        }
    }

    /**
     * Save settings tab data
     *
     * @since 1.0.0
     * @param int $post_id Product ID
     * @return void
     */
    private function save_settings_data( int $post_id ): void {
        // Save each setting as individual post meta
        
        // Minimum rental days
        if ( isset( $_POST['_rental_min_days'] ) ) {
            update_post_meta( $post_id, '_wcrbtw_min_days', absint( $_POST['_rental_min_days'] ) );
        }

        // Maximum rental days
        if ( isset( $_POST['_rental_max_days'] ) ) {
            update_post_meta( $post_id, '_wcrbtw_max_days', absint( $_POST['_rental_max_days'] ) );
        }

        // Extra day after hour
        if ( isset( $_POST['_rental_extra_day_hour'] ) ) {
            update_post_meta( $post_id, '_wcrbtw_extra_day_hour', absint( $_POST['_rental_extra_day_hour'] ) );
        }

        // Security deposit
        if ( isset( $_POST['_rental_security_deposit'] ) ) {
            update_post_meta( $post_id, '_wcrbtw_security_deposit', wc_format_decimal( $_POST['_rental_security_deposit'] ) );
        }

        // Cancellation policy
        if ( isset( $_POST['_rental_cancellation_policy'] ) ) {
            update_post_meta( $post_id, '_wcrbtw_cancellation_policy', wp_kses_post( $_POST['_rental_cancellation_policy'] ) );
        }

        // Additional settings
        if ( isset( $_POST['_rental_additional_settings'] ) ) {
            update_post_meta( $post_id, '_wcrbtw_additional_settings', wp_kses_post( $_POST['_rental_additional_settings'] ) );
        }
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @since 1.0.0
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_admin_scripts( string $hook ): void {
        // Only load on product edit pages
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }

        $screen = get_current_screen();
        if ( ! $screen || 'product' !== $screen->post_type ) {
            return;
        }

        // Enqueue admin script with proper dependencies
        wp_enqueue_script(
            'wcrbtw-admin-product',
            WCRBTW_PLUGIN_URL . 'assets/js/admin-product.js',
            array( 'jquery' ), // Simplified dependencies for better compatibility
            WCRBTW_VERSION,
            true
        );

        // Enqueue admin styles
        wp_enqueue_style(
            'wcrbtw-admin-product',
            WCRBTW_PLUGIN_URL . 'assets/css/admin-product.css',
            array(),
            WCRBTW_VERSION
        );

        // Get current product type if editing
        $product_type = '';
        if ( isset( $_GET['post'] ) ) {
            $product = wc_get_product( $_GET['post'] );
            if ( $product ) {
                $product_type = $product->get_type();
            }
        }

        // Localize script
        wp_localize_script(
            'wcrbtw-admin-product',
            'wcrbtw_admin',
            array(
                'ajax_url'     => admin_url( 'admin-ajax.php' ),
                'nonce'        => wp_create_nonce( 'wcrbtw-admin-nonce' ),
                'product_type' => $product_type,
                'i18n'         => array(
                    'add_service'    => __( 'Add Service', 'woocommerce-car-rental' ),
                    'remove_service' => __( 'Remove', 'woocommerce-car-rental' ),
                    'add_insurance'  => __( 'Add Insurance', 'woocommerce-car-rental' ),
                    'add_rate'       => __( 'Add Seasonal Rate', 'woocommerce-car-rental' ),
                ),
            )
        );

        // Add inline CSS for debug highlighting only
        $custom_css = '
            /* Debug: Highlight rental tabs */
            .product_data_tabs .show_if_rental_vehicle {
                /* Tab visibility is now handled natively by WooCommerce */
            }
            
            /* Ensure panels have proper styling */
            #rental_details_data.active,
            #rental_rates_data.active,
            #rental_availability_data.active,
            #rental_services_data.active,
            #rental_insurance_data.active,
            #rental_settings_data.active {
                display: block;
            }
        ';
        wp_add_inline_style( 'wcrbtw-admin-product', $custom_css );
    }
    
    /**
     * Add inline script for debug logging only
     *
     * @since 1.0.0
     * @return void
     */
    public function add_inline_script(): void {
        $screen = get_current_screen();
        if ( ! $screen || 'product' !== $screen->post_type ) {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Debug logging to verify tabs are working
            console.log('WCRBTW: Inline debug check');
            var productType = $('#product-type').val();
            console.log('WCRBTW: Product type is:', productType);
            
            // Count visible rental tabs
            var visibleRentalTabs = $('.product_data_tabs .show_if_rental_vehicle:visible').length;
            console.log('WCRBTW: Visible rental tabs:', visibleRentalTabs);
            
            // WooCommerce now handles visibility natively thanks to show_if_rental_vehicle class
        });
        </script>
        <?php
    }

    /**
     * Get rental data for a product
     *
     * @since 1.0.0
     * @param int $product_id Product ID
     * @param string $data_type Type of data to retrieve
     * @return mixed
     */
    public static function get_rental_data( int $product_id, string $data_type = '' ): mixed {
        if ( empty( $data_type ) ) {
            // Return all rental data from individual post meta
            return array(
                'details' => array(
                    'vehicle_type' => get_post_meta( $product_id, '_wcrbtw_vehicle_type', true ),
                    'seats' => get_post_meta( $product_id, '_wcrbtw_seats', true ),
                    'fuel_type' => get_post_meta( $product_id, '_wcrbtw_fuel_type', true ),
                    'transmission' => get_post_meta( $product_id, '_wcrbtw_transmission', true ),
                    'fleet_quantity' => get_post_meta( $product_id, '_wcrbtw_fleet_quantity', true ),
                    'additional_details' => get_post_meta( $product_id, '_wcrbtw_additional_details', true ),
                ),
                'rates' => array(
                    'base_daily_rate' => get_post_meta( $product_id, '_wcrbtw_base_daily_rate', true ),
                    'seasonal_rates' => json_decode( get_post_meta( $product_id, '_wcrbtw_seasonal_rates', true ), true ) ?: array(),
                ),
                'availability' => array(
                    'blocked_dates' => json_decode( get_post_meta( $product_id, '_wcrbtw_blocked_dates', true ), true ) ?: array(),
                    'quantity_periods' => json_decode( get_post_meta( $product_id, '_wcrbtw_quantity_periods', true ), true ) ?: array(),
                    'weekly_closures' => json_decode( get_post_meta( $product_id, '_wcrbtw_weekly_closures', true ), true ) ?: array(),
                    'maintenance_notes' => get_post_meta( $product_id, '_wcrbtw_maintenance_notes', true ),
                ),
                'services' => json_decode( get_post_meta( $product_id, '_wcrbtw_services', true ), true ) ?: array(),
                'insurance' => json_decode( get_post_meta( $product_id, '_wcrbtw_insurance', true ), true ) ?: array(),
                'settings' => array(
                    'min_days' => get_post_meta( $product_id, '_wcrbtw_min_days', true ),
                    'max_days' => get_post_meta( $product_id, '_wcrbtw_max_days', true ),
                    'extra_day_hour' => get_post_meta( $product_id, '_wcrbtw_extra_day_hour', true ),
                    'security_deposit' => get_post_meta( $product_id, '_wcrbtw_security_deposit', true ),
                    'cancellation_policy' => get_post_meta( $product_id, '_wcrbtw_cancellation_policy', true ),
                    'additional_settings' => get_post_meta( $product_id, '_wcrbtw_additional_settings', true ),
                ),
            );
        }

        // Return specific data type
        switch ( $data_type ) {
            case 'details':
                return array(
                    'vehicle_type' => get_post_meta( $product_id, '_wcrbtw_vehicle_type', true ),
                    'seats' => get_post_meta( $product_id, '_wcrbtw_seats', true ),
                    'fuel_type' => get_post_meta( $product_id, '_wcrbtw_fuel_type', true ),
                    'transmission' => get_post_meta( $product_id, '_wcrbtw_transmission', true ),
                    'fleet_quantity' => get_post_meta( $product_id, '_wcrbtw_fleet_quantity', true ),
                    'additional_details' => get_post_meta( $product_id, '_wcrbtw_additional_details', true ),
                );
            case 'rates':
                return array(
                    'base_daily_rate' => get_post_meta( $product_id, '_wcrbtw_base_daily_rate', true ),
                    'seasonal_rates' => json_decode( get_post_meta( $product_id, '_wcrbtw_seasonal_rates', true ), true ) ?: array(),
                );
            case 'availability':
                return array(
                    'blocked_dates' => json_decode( get_post_meta( $product_id, '_wcrbtw_blocked_dates', true ), true ) ?: array(),
                    'quantity_periods' => json_decode( get_post_meta( $product_id, '_wcrbtw_quantity_periods', true ), true ) ?: array(),
                    'weekly_closures' => json_decode( get_post_meta( $product_id, '_wcrbtw_weekly_closures', true ), true ) ?: array(),
                    'maintenance_notes' => get_post_meta( $product_id, '_wcrbtw_maintenance_notes', true ),
                );
            case 'services':
                return json_decode( get_post_meta( $product_id, '_wcrbtw_services', true ), true ) ?: array();
            case 'insurance':
                return json_decode( get_post_meta( $product_id, '_wcrbtw_insurance', true ), true ) ?: array();
            case 'settings':
                return array(
                    'min_days' => get_post_meta( $product_id, '_wcrbtw_min_days', true ),
                    'max_days' => get_post_meta( $product_id, '_wcrbtw_max_days', true ),
                    'extra_day_hour' => get_post_meta( $product_id, '_wcrbtw_extra_day_hour', true ),
                    'security_deposit' => get_post_meta( $product_id, '_wcrbtw_security_deposit', true ),
                    'cancellation_policy' => get_post_meta( $product_id, '_wcrbtw_cancellation_policy', true ),
                    'additional_settings' => get_post_meta( $product_id, '_wcrbtw_additional_settings', true ),
                );
            default:
                return array();
        }
    }
}
