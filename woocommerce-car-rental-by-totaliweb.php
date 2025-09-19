<?php
/**
 * Plugin Name: WooCommerce Car Rental by TotaliWeb
 * Plugin URI: https://totaliweb.it/plugins/woocommerce-car-rental
 * Description: Extends WooCommerce by adding a new product type for vehicle rentals with HPOS compatibility
 * Version: 1.0.0
 * Author: TotaliWeb
 * Author URI: https://totaliweb.it
 * Text Domain: woocommerce-car-rental
 * Domain Path: /languages
 * Requires at least: 6.6
 * Requires PHP: 8.3
 * WC requires at least: 9.0
 * WC tested up to: 9.5
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package WooCommerce_Car_Rental
 * @author TotaliWeb
 * @since 1.0.0
 */

declare( strict_types=1 );

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin class
 *
 * @class WCRBTW_Car_Rental
 * @version 1.0.0
 */
final class WCRBTW_Car_Rental {

    /**
     * Plugin version
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Instance of this class
     *
     * @var ?WCRBTW_Car_Rental
     */
    protected static ?WCRBTW_Car_Rental $instance = null;

    /**
     * Initialize the plugin
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Define plugin constants
        $this->define_constants();

        // Check if WooCommerce is active
        if ( ! $this->wcrbtw_is_woocommerce_active() ) {
            add_action( 'admin_notices', array( $this, 'wcrbtw_woocommerce_missing_notice' ) );
            return;
        }

        // Declare HPOS compatibility
        add_action( 'before_woocommerce_init', array( $this, 'wcrbtw_declare_hpos_compatibility' ) );

        // Load plugin text domain for translations - Priority 0 to load early in init
        add_action( 'init', array( $this, 'wcrbtw_load_textdomain' ), 0 );

        // Include required files after WooCommerce has loaded
        add_action( 'woocommerce_loaded', array( $this, 'wcrbtw_includes' ), 5 );

        // Initialize the plugin - Higher priority to ensure everything is loaded
        add_action( 'init', array( $this, 'wcrbtw_init' ), 20 );

        // Register the new product type
        add_filter( 'product_type_selector', array( $this, 'wcrbtw_add_rental_vehicle_product_type' ) );

        // Load the custom product class
        add_filter( 'woocommerce_product_class', array( $this, 'wcrbtw_product_class' ), 10, 2 );

        // Register product type in WooCommerce
        add_action( 'woocommerce_loaded', array( $this, 'wcrbtw_register_product_type' ) );
    }

    /**
     * Get the singleton instance of the plugin
     *
     * @since 1.0.0
     * @return WCRBTW_Car_Rental
     */
    public static function wcrbtw_get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Define plugin constants
     *
     * @since 1.0.0
     */
    private function define_constants() {
        // Plugin version
        if ( ! defined( 'WCRBTW_VERSION' ) ) {
            define( 'WCRBTW_VERSION', self::VERSION );
        }

        // Plugin directory path
        if ( ! defined( 'WCRBTW_PLUGIN_DIR' ) ) {
            define( 'WCRBTW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
        }

        // Plugin directory URL
        if ( ! defined( 'WCRBTW_PLUGIN_URL' ) ) {
            define( 'WCRBTW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
        }

        // Plugin basename
        if ( ! defined( 'WCRBTW_PLUGIN_BASENAME' ) ) {
            define( 'WCRBTW_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
        }

        // Plugin file
        if ( ! defined( 'WCRBTW_PLUGIN_FILE' ) ) {
            define( 'WCRBTW_PLUGIN_FILE', __FILE__ );
        }
    }

    /**
     * Check if WooCommerce is active
     *
     * @since 1.0.0
     * @return bool
     */
    private function wcrbtw_is_woocommerce_active(): bool {
        return class_exists( 'WooCommerce' );
    }

    /**
     * Display admin notice if WooCommerce is not active
     *
     * @since 1.0.0
     */
    public function wcrbtw_woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p>
                <?php
                echo esc_html__(
                    'WooCommerce Car Rental requires WooCommerce to be installed and active.',
                    'woocommerce-car-rental'
                );
                ?>
            </p>
        </div>
        <?php
    }

    /**
     * Declare High Performance Order Storage (HPOS) compatibility
     *
     * @since 1.0.0
     */
    public function wcrbtw_declare_hpos_compatibility() {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 
                'custom_order_tables',
                WCRBTW_PLUGIN_FILE,
                true 
            );

            // Also declare compatibility with other WooCommerce features
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'product_block_editor',
                WCRBTW_PLUGIN_FILE,
                true
            );

            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'cart_checkout_blocks',
                WCRBTW_PLUGIN_FILE,
                true
            );
        }
    }

    /**
     * Load plugin text domain for translations
     *
     * @since 1.0.0
     */
    public function wcrbtw_load_textdomain() {
        load_plugin_textdomain(
            'woocommerce-car-rental',
            false,
            dirname( WCRBTW_PLUGIN_BASENAME ) . '/languages'
        );
    }

    /**
     * Include required core files
     *
     * @since 1.0.0
     */
    public function wcrbtw_includes() {
        // Include the custom product class file
        require_once WCRBTW_PLUGIN_DIR . 'includes/class-wc-product-rental-vehicle.php';

        // Include helper functions
        require_once WCRBTW_PLUGIN_DIR . 'includes/rental-functions.php';

        // Include HPOS compatibility helper if needed
        if ( $this->wcrbtw_is_hpos_enabled() ) {
            require_once WCRBTW_PLUGIN_DIR . 'includes/class-hpos-compatibility.php';
        }

        // Include REST API controller
        require_once WCRBTW_PLUGIN_DIR . 'includes/class-rest-api-controller.php';

        // Include admin files if in admin area
        if ( is_admin() ) {
            require_once WCRBTW_PLUGIN_DIR . 'admin/class-admin-product-data.php';
            require_once WCRBTW_PLUGIN_DIR . 'admin/class-locations.php';
            // Initialize admin product data handler
            WCRBTW_Admin_Product_Data::get_instance();
        }
    }

    /**
     * Check if HPOS is enabled
     *
     * @since 1.0.0
     * @return bool
     */
    private function wcrbtw_is_hpos_enabled(): bool {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ) {
            return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }
        return false;
    }

    /**
     * Initialize plugin functionality
     *
     * @since 1.0.0
     */
    public function wcrbtw_init() {
        // Hook for developers to extend plugin initialization
        do_action( 'wcrbtw_init' );
    }

    /**
     * Register the rental vehicle product type
     *
     * @since 1.0.0
     */
    public function wcrbtw_register_product_type() {
        if ( class_exists( 'WC_Product_Rental_Vehicle' ) ) {
            // Register product type for WooCommerce 9.x compatibility
            add_filter( 'woocommerce_product_type_query', array( $this, 'wcrbtw_add_product_type_query' ), 10, 2 );
        }
    }

    /**
     * Add product type to query
     *
     * @since 1.0.0
     * @param array $types Product types
     * @param int $product_id Product ID
     * @return array
     */
    public function wcrbtw_add_product_type_query( array $types, int $product_id ): array {
        $types[] = 'rental_vehicle';
        return $types;
    }

    /**
     * Add Rental Vehicle product type to the product type selector
     *
     * @since 1.0.0
     * @param array $types Existing product types
     * @return array Modified product types array
     */
    public function wcrbtw_add_rental_vehicle_product_type( array $types ): array {
        $types['rental_vehicle'] = __( 'Rental Vehicle', 'woocommerce-car-rental' );
        return $types;
    }

    /**
     * Load the custom product class when a rental_vehicle product is loaded
     *
     * @since 1.0.0
     * @param string $classname The product class name
     * @param string $product_type The product type
     * @return string Modified class name
     */
    public function wcrbtw_product_class( string $classname, string $product_type ): string {
        if ( 'rental_vehicle' === $product_type ) {
            $classname = 'WC_Product_Rental_Vehicle';
        }
        return $classname;
    }
}

/**
 * Initialize the plugin
 *
 * @since 1.0.0
 * @return WCRBTW_Car_Rental|null
 */
function wcrbtw_car_rental() {
    // Check if WooCommerce is active first
    if ( ! class_exists( 'WooCommerce' ) ) {
        return null;
    }
    
    return WCRBTW_Car_Rental::wcrbtw_get_instance();
}

// Start the plugin - Priority 20 to ensure WooCommerce is fully loaded
add_action( 'plugins_loaded', 'wcrbtw_car_rental', 20 );
