<?php
/**
 * Rental Vehicle Product Type Class
 *
 * Custom product type for rental vehicles extending WooCommerce base product class
 *
 * @package WooCommerce_Car_Rental
 * @subpackage Classes/Products
 * @since 1.0.0
 */

declare( strict_types=1 );

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WC_Product_Rental_Vehicle class
 *
 * @class WC_Product_Rental_Vehicle
 * @extends WC_Product
 * @version 1.0.0
 */
class WC_Product_Rental_Vehicle extends WC_Product {

    /**
     * Product type name
     *
     * @var string
     */
    protected $product_type = 'rental_vehicle';

    /**
     * Constructor
     *
     * Initialize the rental vehicle product
     *
     * @since 1.0.0
     * @param WC_Product|int $product Product object or ID
     */
    public function __construct( $product = 0 ) {
        // Define extra data for this product type
        $this->data['rental_price_per_day'] = 0;
        $this->data['rental_minimum_days'] = 1;
        $this->data['rental_maximum_days'] = 30;
        $this->data['rental_buffer_days'] = 0;
        
        // Set default product data for WooCommerce 9.x
        $this->supports = array(
            'ajax_add_to_cart',
            'stock_status',
        );
        
        // Call parent constructor
        parent::__construct( $product );
    }

    /**
     * Get product type
     *
     * @since 1.0.0
     * @return string Product type
     */
    public function get_type() {
        return 'rental_vehicle';
    }

    /**
     * Get the add to cart button text
     *
     * @since 1.0.0
     * @return string Add to cart button text
     */
    public function add_to_cart_text() {
        /**
         * Filter the add to cart text for rental vehicle products
         *
         * @since 1.0.0
         * @param string $text Default button text
         * @param WC_Product_Rental_Vehicle $this Current product object
         */
        return apply_filters( 
            'wcrbtw_rental_vehicle_add_to_cart_text', 
            __( 'Book Now', 'woocommerce-car-rental' ), 
            $this 
        );
    }

    /**
     * Get the single add to cart button text
     *
     * @since 1.0.0
     * @return string Single add to cart button text
     */
    public function single_add_to_cart_text() {
        /**
         * Filter the single add to cart text for rental vehicle products
         *
         * @since 1.0.0
         * @param string $text Default button text
         * @param WC_Product_Rental_Vehicle $this Current product object
         */
        return apply_filters( 
            'wcrbtw_rental_vehicle_single_add_to_cart_text', 
            __( 'Book This Vehicle', 'woocommerce-car-rental' ), 
            $this 
        );
    }

    /**
     * Check if the product is purchasable
     *
     * @since 1.0.0
     * @return bool True if purchasable, false otherwise
     */
    public function is_purchasable() {
        $purchasable = parent::is_purchasable();
        
        // Additional checks for rental vehicles
        if ( $purchasable && $this->get_price( 'edit' ) !== '' ) {
            $purchasable = true;
        }

        /**
         * Filter whether a rental vehicle is purchasable
         *
         * @since 1.0.0
         * @param bool $purchasable Default purchasable status
         * @param WC_Product_Rental_Vehicle $this Current product object
         */
        return apply_filters( 
            'wcrbtw_is_rental_vehicle_purchasable', 
            $purchasable, 
            $this 
        );
    }

    /**
     * Check if the product is sold individually
     *
     * Rental vehicles are typically sold individually (one booking at a time)
     *
     * @since 1.0.0
     * @return bool True if sold individually, false otherwise
     */
    public function is_sold_individually() {
        /**
         * Filter whether a rental vehicle is sold individually
         *
         * @since 1.0.0
         * @param bool $sold_individually Default status (true for rental vehicles)
         * @param WC_Product_Rental_Vehicle $this Current product object
         */
        return apply_filters( 
            'wcrbtw_is_rental_vehicle_sold_individually', 
            true, 
            $this 
        );
    }

    /**
     * Check if the product is virtual
     *
     * Rental vehicles are not virtual products
     *
     * @since 1.0.0
     * @return bool False as rental vehicles are physical products
     */
    public function is_virtual() {
        return false;
    }

    /**
     * Check if the product is downloadable
     *
     * Rental vehicles are not downloadable
     *
     * @since 1.0.0
     * @return bool False as rental vehicles are not downloadable
     */
    public function is_downloadable() {
        return false;
    }

    /**
     * Get the product's availability status
     *
     * @since 1.0.0
     * @return array Availability data
     */
    public function get_availability() {
        $availability = parent::get_availability();

        /**
         * Filter the availability of a rental vehicle
         *
         * @since 1.0.0
         * @param array $availability Availability data
         * @param WC_Product_Rental_Vehicle $this Current product object
         */
        return apply_filters( 
            'wcrbtw_rental_vehicle_availability', 
            $availability, 
            $this 
        );
    }

    /**
     * Returns whether the product is in stock
     *
     * @since 1.0.0
     * @return bool True if in stock, false otherwise
     */
    public function is_in_stock() {
        $in_stock = parent::is_in_stock();

        /**
         * Filter the stock status of a rental vehicle
         *
         * @since 1.0.0
         * @param bool $in_stock Default stock status
         * @param WC_Product_Rental_Vehicle $this Current product object
         */
        return apply_filters( 
            'wcrbtw_rental_vehicle_is_in_stock', 
            $in_stock, 
            $this 
        );
    }

    /**
     * Check if the product needs shipping
     *
     * Rental vehicles don't need shipping as they are picked up
     *
     * @since 1.0.0
     * @return bool False as rental vehicles don't require shipping
     */
    public function needs_shipping() {
        /**
         * Filter whether a rental vehicle needs shipping
         *
         * @since 1.0.0
         * @param bool $needs_shipping Default status (false for rental vehicles)
         * @param WC_Product_Rental_Vehicle $this Current product object
         */
        return apply_filters( 
            'wcrbtw_rental_vehicle_needs_shipping', 
            false, 
            $this 
        );
    }

    /**
     * Get internal type for compatibility
     *
     * @since 1.0.0
     * @return string Internal type
     */
    public function get_internal_type() {
        return 'rental_vehicle';
    }

    /**
     * Check if product can be backordered
     *
     * @since 1.0.0
     * @return bool
     */
    public function backorders_allowed() {
        return apply_filters( 'wcrbtw_rental_vehicle_backorders_allowed', false, $this );
    }

    /**
     * Get rental price per day
     *
     * @since 1.0.0
     * @param string $context What the value is for. Valid values are 'view' and 'edit'.
     * @return float
     */
    public function get_rental_price_per_day( string $context = 'view' ): float {
        return (float) $this->get_prop( 'rental_price_per_day', $context );
    }

    /**
     * Set rental price per day
     *
     * @since 1.0.0
     * @param float $price Price per day
     * @return void
     */
    public function set_rental_price_per_day( float $price ): void {
        $this->set_prop( 'rental_price_per_day', wc_format_decimal( $price ) );
    }

    /**
     * Get minimum rental days
     *
     * @since 1.0.0
     * @param string $context What the value is for. Valid values are 'view' and 'edit'.
     * @return int
     */
    public function get_rental_minimum_days( string $context = 'view' ): int {
        return (int) $this->get_prop( 'rental_minimum_days', $context );
    }

    /**
     * Set minimum rental days
     *
     * @since 1.0.0
     * @param int $days Minimum days
     * @return void
     */
    public function set_rental_minimum_days( int $days ): void {
        $this->set_prop( 'rental_minimum_days', max( 1, $days ) );
    }

    /**
     * Get maximum rental days
     *
     * @since 1.0.0
     * @param string $context What the value is for. Valid values are 'view' and 'edit'.
     * @return int
     */
    public function get_rental_maximum_days( string $context = 'view' ): int {
        return (int) $this->get_prop( 'rental_maximum_days', $context );
    }

    /**
     * Set maximum rental days
     *
     * @since 1.0.0
     * @param int $days Maximum days
     * @return void
     */
    public function set_rental_maximum_days( int $days ): void {
        $this->set_prop( 'rental_maximum_days', max( 1, $days ) );
    }

    /**
     * Get buffer days between rentals
     *
     * @since 1.0.0
     * @param string $context What the value is for. Valid values are 'view' and 'edit'.
     * @return int
     */
    public function get_rental_buffer_days( string $context = 'view' ): int {
        return (int) $this->get_prop( 'rental_buffer_days', $context );
    }

    /**
     * Set buffer days between rentals
     *
     * @since 1.0.0
     * @param int $days Buffer days
     * @return void
     */
    public function set_rental_buffer_days( int $days ): void {
        $this->set_prop( 'rental_buffer_days', max( 0, $days ) );
    }

    /**
     * Returns false if the product cannot be bought
     *
     * @since 1.0.0
     * @return bool
     */
    public function exists() {
        return parent::exists() && 'rental_vehicle' === $this->get_type();
    }

    /**
     * Get the add to cart URL
     *
     * @since 1.0.0
     * @return string
     */
    public function add_to_cart_url() {
        $url = $this->is_purchasable() && $this->is_in_stock() 
            ? remove_query_arg(
                'added-to-cart',
                add_query_arg(
                    array(
                        'add-to-cart' => $this->get_id(),
                    ),
                    ( function_exists( 'is_feed' ) && is_feed() ) || ( function_exists( 'is_404' ) && is_404() ) 
                        ? $this->get_permalink() 
                        : ''
                )
            )
            : $this->get_permalink();

        return apply_filters( 'woocommerce_product_add_to_cart_url', $url, $this );
    }

    /**
     * Get the add to cart button text description
     *
     * @since 1.0.0
     * @return string
     */
    public function add_to_cart_description() {
        return apply_filters( 
            'wcrbtw_rental_vehicle_add_to_cart_description', 
            __( 'Book this rental vehicle', 'woocommerce-car-rental' ), 
            $this 
        );
    }

    /**
     * Return if product is on sale
     *
     * @since 1.0.0
     * @param string $context What the value is for. Valid values are 'view' and 'edit'.
     * @return bool
     */
    public function is_on_sale( $context = 'view' ) {
        // Rental vehicles can have promotional rates
        $on_sale = parent::is_on_sale( $context );
        
        return apply_filters( 'wcrbtw_rental_vehicle_is_on_sale', $on_sale, $this, $context );
    }

    /**
     * Initialize product data
     *
     * Hook for future extensions to initialize custom product data
     *
     * @since 1.0.0
     * @return void
     */
    protected function init_product_data(): void {
        /**
         * Action hook for initializing rental vehicle product data
         *
         * @since 1.0.0
         * @param WC_Product_Rental_Vehicle $this Current product object
         */
        do_action( 'wcrbtw_init_rental_vehicle_data', $this );
    }

    /**
     * Get data store for WooCommerce 9.x compatibility
     *
     * @since 1.0.0
     * @return WC_Data_Store
     */
    public function get_data_store() {
        return WC_Data_Store::load( 'product' );
    }
}
