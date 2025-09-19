<?php
/**
 * HPOS (High Performance Order Storage) Compatibility Helper
 *
 * Provides compatibility layer for WooCommerce HPOS/Custom Order Tables
 *
 * @package WooCommerce_Car_Rental
 * @subpackage Classes/Compatibility
 * @since 1.0.0
 */

declare( strict_types=1 );

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Automattic\WooCommerce\Utilities\OrderUtil;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

/**
 * HPOS Compatibility Class
 *
 * @class WCRBTW_HPOS_Compatibility
 * @version 1.0.0
 */
final class WCRBTW_HPOS_Compatibility {

    /**
     * Instance of this class
     *
     * @var ?WCRBTW_HPOS_Compatibility
     */
    private static ?WCRBTW_HPOS_Compatibility $instance = null;

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
     * @return WCRBTW_HPOS_Compatibility
     */
    public static function get_instance(): WCRBTW_HPOS_Compatibility {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize hooks for HPOS compatibility
     *
     * @since 1.0.0
     * @return void
     */
    private function init_hooks(): void {
        // Add order meta compatibility
        add_action( 'woocommerce_checkout_create_order', array( $this, 'add_rental_meta_to_order' ), 10, 2 );
        add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_rental_meta_to_order_item' ), 10, 4 );
    }

    /**
     * Check if HPOS is enabled
     *
     * @since 1.0.0
     * @return bool
     */
    public static function is_hpos_enabled(): bool {
        if ( class_exists( OrderUtil::class ) ) {
            return OrderUtil::custom_orders_table_usage_is_enabled();
        }
        return false;
    }

    /**
     * Get order object compatible with both legacy and HPOS
     *
     * @since 1.0.0
     * @param int|WC_Order $order Order ID or order object
     * @return ?WC_Order
     */
    public static function get_order( int|WC_Order $order ): ?WC_Order {
        if ( $order instanceof WC_Order ) {
            return $order;
        }

        // Use WooCommerce function that works with both storage methods
        return wc_get_order( $order );
    }

    /**
     * Get order ID from various sources (HPOS compatible)
     *
     * @since 1.0.0
     * @param mixed $order Order object, ID, or post
     * @return int
     */
    public static function get_order_id( mixed $order ): int {
        if ( is_numeric( $order ) ) {
            return (int) $order;
        }

        if ( $order instanceof WC_Order ) {
            return $order->get_id();
        }

        if ( isset( $order->ID ) ) {
            return (int) $order->ID;
        }

        return 0;
    }

    /**
     * Get order meta (HPOS compatible)
     *
     * @since 1.0.0
     * @param int|WC_Order $order Order ID or object
     * @param string $key Meta key
     * @param bool $single Return single value
     * @return mixed
     */
    public static function get_order_meta( int|WC_Order $order, string $key, bool $single = true ): mixed {
        $order_obj = self::get_order( $order );
        
        if ( ! $order_obj ) {
            return $single ? '' : array();
        }

        return $order_obj->get_meta( $key, $single );
    }

    /**
     * Update order meta (HPOS compatible)
     *
     * @since 1.0.0
     * @param int|WC_Order $order Order ID or object
     * @param string $key Meta key
     * @param mixed $value Meta value
     * @return void
     */
    public static function update_order_meta( int|WC_Order $order, string $key, mixed $value ): void {
        $order_obj = self::get_order( $order );
        
        if ( ! $order_obj ) {
            return;
        }

        $order_obj->update_meta_data( $key, $value );
        $order_obj->save();
    }

    /**
     * Delete order meta (HPOS compatible)
     *
     * @since 1.0.0
     * @param int|WC_Order $order Order ID or object
     * @param string $key Meta key
     * @return void
     */
    public static function delete_order_meta( int|WC_Order $order, string $key ): void {
        $order_obj = self::get_order( $order );
        
        if ( ! $order_obj ) {
            return;
        }

        $order_obj->delete_meta_data( $key );
        $order_obj->save();
    }

    /**
     * Add rental metadata to order during checkout
     *
     * @since 1.0.0
     * @param WC_Order $order Order object
     * @param array $data Checkout data
     * @return void
     */
    public function add_rental_meta_to_order( WC_Order $order, array $data ): void {
        // Check if order contains rental vehicles
        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();
            
            if ( $product && 'rental_vehicle' === $product->get_type() ) {
                // Add order-level rental meta
                $order->update_meta_data( '_contains_rental_vehicles', 'yes' );
                
                /**
                 * Action hook for adding custom rental meta to order
                 *
                 * @since 1.0.0
                 * @param WC_Order $order Order object
                 * @param array $data Checkout data
                 */
                do_action( 'wcrbtw_add_rental_meta_to_order', $order, $data );
                break;
            }
        }
    }

    /**
     * Add rental metadata to order items during checkout
     *
     * @since 1.0.0
     * @param WC_Order_Item_Product $item Order item
     * @param string $cart_item_key Cart item key
     * @param array $values Cart item values
     * @param WC_Order $order Order object
     * @return void
     */
    public function add_rental_meta_to_order_item( 
        WC_Order_Item_Product $item, 
        string $cart_item_key, 
        array $values, 
        WC_Order $order 
    ): void {
        $product = $item->get_product();
        
        if ( ! $product || 'rental_vehicle' !== $product->get_type() ) {
            return;
        }

        // Add item-level rental meta
        $item->update_meta_data( '_is_rental_vehicle', 'yes' );
        
        // Add rental dates if available in cart
        if ( isset( $values['rental_start_date'] ) ) {
            $item->update_meta_data( '_rental_start_date', sanitize_text_field( $values['rental_start_date'] ) );
        }
        
        if ( isset( $values['rental_end_date'] ) ) {
            $item->update_meta_data( '_rental_end_date', sanitize_text_field( $values['rental_end_date'] ) );
        }

        /**
         * Action hook for adding custom rental meta to order item
         *
         * @since 1.0.0
         * @param WC_Order_Item_Product $item Order item
         * @param array $values Cart item values
         * @param WC_Order $order Order object
         */
        do_action( 'wcrbtw_add_rental_meta_to_order_item', $item, $values, $order );
    }

    /**
     * Query orders containing rental vehicles (HPOS compatible)
     *
     * @since 1.0.0
     * @param array $args Query arguments
     * @return array Array of order IDs
     */
    public static function get_rental_orders( array $args = array() ): array {
        $default_args = array(
            'limit' => -1,
            'return' => 'ids',
            'status' => array( 'wc-processing', 'wc-completed', 'wc-on-hold' ),
            'meta_key' => '_contains_rental_vehicles',
            'meta_value' => 'yes',
            'meta_compare' => '='
        );

        $args = wp_parse_args( $args, $default_args );

        // Use wc_get_orders which is HPOS compatible
        return wc_get_orders( $args );
    }

    /**
     * Get rental items from an order
     *
     * @since 1.0.0
     * @param int|WC_Order $order Order ID or object
     * @return array Array of rental order items
     */
    public static function get_rental_items_from_order( int|WC_Order $order ): array {
        $order_obj = self::get_order( $order );
        $rental_items = array();

        if ( ! $order_obj ) {
            return $rental_items;
        }

        foreach ( $order_obj->get_items() as $item ) {
            $product = $item->get_product();
            
            if ( $product && 'rental_vehicle' === $product->get_type() ) {
                $rental_items[] = $item;
            }
        }

        return $rental_items;
    }

    /**
     * Check if an order contains rental vehicles
     *
     * @since 1.0.0
     * @param int|WC_Order $order Order ID or object
     * @return bool
     */
    public static function order_contains_rentals( int|WC_Order $order ): bool {
        $order_obj = self::get_order( $order );
        
        if ( ! $order_obj ) {
            return false;
        }

        // Check order meta first (faster)
        if ( 'yes' === $order_obj->get_meta( '_contains_rental_vehicles' ) ) {
            return true;
        }

        // Fallback: check order items
        foreach ( $order_obj->get_items() as $item ) {
            $product = $item->get_product();
            
            if ( $product && 'rental_vehicle' === $product->get_type() ) {
                return true;
            }
        }

        return false;
    }
}

// Initialize the compatibility class
if ( class_exists( 'WooCommerce' ) ) {
    WCRBTW_HPOS_Compatibility::get_instance();
}
