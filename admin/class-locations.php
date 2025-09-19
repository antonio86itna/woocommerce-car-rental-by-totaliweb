<?php
/**
 * Rental Locations Management
 *
 * Manages pickup and dropoff locations for rental vehicles
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
 * Rental Locations Class
 *
 * @class WCRBTW_Locations
 * @version 1.0.0
 */
final class WCRBTW_Locations {

    /**
     * Instance of this class
     *
     * @var ?WCRBTW_Locations
     */
    private static ?WCRBTW_Locations $instance = null;

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
     * @return WCRBTW_Locations
     */
    public static function get_instance(): WCRBTW_Locations {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize hooks
     *
     * @since 1.0.0
     */
    private function init_hooks() {
        // Add menu item for locations management
        add_action( 'admin_menu', array( $this, 'add_locations_menu' ), 99 );
        
        // Register settings
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        
        // AJAX handlers for locations
        add_action( 'wp_ajax_wcrbtw_save_location', array( $this, 'ajax_save_location' ) );
        add_action( 'wp_ajax_wcrbtw_delete_location', array( $this, 'ajax_delete_location' ) );
        add_action( 'wp_ajax_wcrbtw_get_locations', array( $this, 'ajax_get_locations' ) );
        add_action( 'wp_ajax_nopriv_wcrbtw_get_locations', array( $this, 'ajax_get_locations' ) );
    }

    /**
     * Add locations menu under WooCommerce
     *
     * @since 1.0.0
     */
    public function add_locations_menu() {
        add_submenu_page(
            'woocommerce',
            __( 'Rental Locations', 'woocommerce-car-rental' ),
            __( 'Rental Locations', 'woocommerce-car-rental' ),
            'manage_woocommerce',
            'wcrbtw-locations',
            array( $this, 'render_locations_page' )
        );
    }

    /**
     * Register settings
     *
     * @since 1.0.0
     */
    public function register_settings() {
        register_setting( 'wcrbtw_locations', 'wcrbtw_rental_locations' );
    }

    /**
     * Render locations management page
     *
     * @since 1.0.0
     */
    public function render_locations_page() {
        $locations = $this->get_all_locations();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Rental Locations', 'woocommerce-car-rental' ); ?></h1>
            
            <div id="wcrbtw-locations-wrapper">
                <div class="wcrbtw-locations-grid">
                    <div class="wcrbtw-locations-list">
                        <h2><?php esc_html_e( 'Existing Locations', 'woocommerce-car-rental' ); ?></h2>
                        
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Name', 'woocommerce-car-rental' ); ?></th>
                                    <th><?php esc_html_e( 'Address', 'woocommerce-car-rental' ); ?></th>
                                    <th><?php esc_html_e( 'Type', 'woocommerce-car-rental' ); ?></th>
                                    <th><?php esc_html_e( 'Actions', 'woocommerce-car-rental' ); ?></th>
                                </tr>
                            </thead>
                            <tbody id="wcrbtw-locations-tbody">
                                <?php if ( ! empty( $locations ) ) : ?>
                                    <?php foreach ( $locations as $index => $location ) : ?>
                                        <tr data-location-id="<?php echo esc_attr( $index ); ?>">
                                            <td><?php echo esc_html( $location['name'] ); ?></td>
                                            <td><?php echo esc_html( $location['address'] ); ?></td>
                                            <td>
                                                <?php 
                                                $types = array();
                                                if ( $location['is_pickup'] === 'yes' ) {
                                                    $types[] = __( 'Pickup', 'woocommerce-car-rental' );
                                                }
                                                if ( $location['is_dropoff'] === 'yes' ) {
                                                    $types[] = __( 'Drop-off', 'woocommerce-car-rental' );
                                                }
                                                echo esc_html( implode( ', ', $types ) );
                                                ?>
                                            </td>
                                            <td>
                                                <button class="button wcrbtw-edit-location" data-index="<?php echo esc_attr( $index ); ?>">
                                                    <?php esc_html_e( 'Edit', 'woocommerce-car-rental' ); ?>
                                                </button>
                                                <button class="button wcrbtw-delete-location" data-index="<?php echo esc_attr( $index ); ?>">
                                                    <?php esc_html_e( 'Delete', 'woocommerce-car-rental' ); ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="4"><?php esc_html_e( 'No locations added yet.', 'woocommerce-car-rental' ); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="wcrbtw-location-form">
                        <h2><?php esc_html_e( 'Add/Edit Location', 'woocommerce-car-rental' ); ?></h2>
                        
                        <form id="wcrbtw-location-form" method="post">
                            <input type="hidden" id="location_index" name="location_index" value="">
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="location_name"><?php esc_html_e( 'Location Name', 'woocommerce-car-rental' ); ?> *</label>
                                    </th>
                                    <td>
                                        <input type="text" id="location_name" name="location_name" class="regular-text" required>
                                        <p class="description"><?php esc_html_e( 'e.g., Milan Central Station', 'woocommerce-car-rental' ); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="location_address"><?php esc_html_e( 'Address', 'woocommerce-car-rental' ); ?> *</label>
                                    </th>
                                    <td>
                                        <textarea id="location_address" name="location_address" class="large-text" rows="3" required></textarea>
                                        <p class="description"><?php esc_html_e( 'Full address of the location', 'woocommerce-car-rental' ); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="location_city"><?php esc_html_e( 'City', 'woocommerce-car-rental' ); ?> *</label>
                                    </th>
                                    <td>
                                        <input type="text" id="location_city" name="location_city" class="regular-text" required>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="location_postcode"><?php esc_html_e( 'Postal Code', 'woocommerce-car-rental' ); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="location_postcode" name="location_postcode" class="regular-text">
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="location_latitude"><?php esc_html_e( 'Latitude', 'woocommerce-car-rental' ); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="location_latitude" name="location_latitude" class="regular-text">
                                        <p class="description"><?php esc_html_e( 'For map integration (e.g., 45.4642)', 'woocommerce-car-rental' ); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="location_longitude"><?php esc_html_e( 'Longitude', 'woocommerce-car-rental' ); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="location_longitude" name="location_longitude" class="regular-text">
                                        <p class="description"><?php esc_html_e( 'For map integration (e.g., 9.1900)', 'woocommerce-car-rental' ); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <?php esc_html_e( 'Location Type', 'woocommerce-car-rental' ); ?>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox" id="location_is_pickup" name="location_is_pickup" value="1" checked>
                                            <?php esc_html_e( 'Pickup Location', 'woocommerce-car-rental' ); ?>
                                        </label>
                                        <br>
                                        <label>
                                            <input type="checkbox" id="location_is_dropoff" name="location_is_dropoff" value="1" checked>
                                            <?php esc_html_e( 'Drop-off Location', 'woocommerce-car-rental' ); ?>
                                        </label>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="location_phone"><?php esc_html_e( 'Phone Number', 'woocommerce-car-rental' ); ?></label>
                                    </th>
                                    <td>
                                        <input type="tel" id="location_phone" name="location_phone" class="regular-text">
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="location_email"><?php esc_html_e( 'Email', 'woocommerce-car-rental' ); ?></label>
                                    </th>
                                    <td>
                                        <input type="email" id="location_email" name="location_email" class="regular-text">
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="location_extra_fee"><?php esc_html_e( 'Extra Fee', 'woocommerce-car-rental' ); ?></label>
                                    </th>
                                    <td>
                                        <input type="number" id="location_extra_fee" name="location_extra_fee" class="regular-text" step="0.01" min="0">
                                        <p class="description"><?php esc_html_e( 'Additional fee for this location (if any)', 'woocommerce-car-rental' ); ?></p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p class="submit">
                                <button type="submit" class="button button-primary">
                                    <?php esc_html_e( 'Save Location', 'woocommerce-car-rental' ); ?>
                                </button>
                                <button type="button" id="wcrbtw-clear-form" class="button">
                                    <?php esc_html_e( 'Clear Form', 'woocommerce-car-rental' ); ?>
                                </button>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
            .wcrbtw-locations-grid {
                display: grid;
                grid-template-columns: 2fr 1fr;
                gap: 20px;
                margin-top: 20px;
            }
            .wcrbtw-locations-list,
            .wcrbtw-location-form {
                background: #fff;
                border: 1px solid #ccd0d4;
                padding: 20px;
            }
            @media screen and (max-width: 1200px) {
                .wcrbtw-locations-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Save location
            $('#wcrbtw-location-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = {
                    action: 'wcrbtw_save_location',
                    nonce: '<?php echo wp_create_nonce( 'wcrbtw_locations_nonce' ); ?>',
                    index: $('#location_index').val(),
                    name: $('#location_name').val(),
                    address: $('#location_address').val(),
                    city: $('#location_city').val(),
                    postcode: $('#location_postcode').val(),
                    latitude: $('#location_latitude').val(),
                    longitude: $('#location_longitude').val(),
                    is_pickup: $('#location_is_pickup').is(':checked') ? 'yes' : 'no',
                    is_dropoff: $('#location_is_dropoff').is(':checked') ? 'yes' : 'no',
                    phone: $('#location_phone').val(),
                    email: $('#location_email').val(),
                    extra_fee: $('#location_extra_fee').val()
                };
                
                $.post(ajaxurl, formData, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                });
            });
            
            // Edit location
            $('.wcrbtw-edit-location').on('click', function() {
                var index = $(this).data('index');
                var locations = <?php echo json_encode( $locations ); ?>;
                var location = locations[index];
                
                $('#location_index').val(index);
                $('#location_name').val(location.name);
                $('#location_address').val(location.address);
                $('#location_city').val(location.city || '');
                $('#location_postcode').val(location.postcode || '');
                $('#location_latitude').val(location.latitude || '');
                $('#location_longitude').val(location.longitude || '');
                $('#location_is_pickup').prop('checked', location.is_pickup === 'yes');
                $('#location_is_dropoff').prop('checked', location.is_dropoff === 'yes');
                $('#location_phone').val(location.phone || '');
                $('#location_email').val(location.email || '');
                $('#location_extra_fee').val(location.extra_fee || '');
                
                $('html, body').animate({
                    scrollTop: $('#wcrbtw-location-form').offset().top - 100
                }, 500);
            });
            
            // Delete location
            $('.wcrbtw-delete-location').on('click', function() {
                if (!confirm('<?php esc_html_e( 'Are you sure you want to delete this location?', 'woocommerce-car-rental' ); ?>')) {
                    return;
                }
                
                var index = $(this).data('index');
                
                $.post(ajaxurl, {
                    action: 'wcrbtw_delete_location',
                    nonce: '<?php echo wp_create_nonce( 'wcrbtw_locations_nonce' ); ?>',
                    index: index
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                });
            });
            
            // Clear form
            $('#wcrbtw-clear-form').on('click', function() {
                $('#wcrbtw-location-form')[0].reset();
                $('#location_index').val('');
            });
        });
        </script>
        <?php
    }

    /**
     * Get all locations
     *
     * @since 1.0.0
     * @return array
     */
    public function get_all_locations(): array {
        $locations = get_option( 'wcrbtw_rental_locations', array() );
        return is_array( $locations ) ? $locations : array();
    }

    /**
     * Get pickup locations
     *
     * @since 1.0.0
     * @return array
     */
    public function get_pickup_locations(): array {
        $all_locations = $this->get_all_locations();
        return array_filter( $all_locations, function( $location ) {
            return isset( $location['is_pickup'] ) && $location['is_pickup'] === 'yes';
        } );
    }

    /**
     * Get dropoff locations
     *
     * @since 1.0.0
     * @return array
     */
    public function get_dropoff_locations(): array {
        $all_locations = $this->get_all_locations();
        return array_filter( $all_locations, function( $location ) {
            return isset( $location['is_dropoff'] ) && $location['is_dropoff'] === 'yes';
        } );
    }

    /**
     * AJAX handler to save location
     *
     * @since 1.0.0
     */
    public function ajax_save_location() {
        check_ajax_referer( 'wcrbtw_locations_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied', 'woocommerce-car-rental' ) ) );
        }
        
        $locations = $this->get_all_locations();
        
        $location_data = array(
            'name'        => sanitize_text_field( $_POST['name'] ?? '' ),
            'address'     => sanitize_textarea_field( $_POST['address'] ?? '' ),
            'city'        => sanitize_text_field( $_POST['city'] ?? '' ),
            'postcode'    => sanitize_text_field( $_POST['postcode'] ?? '' ),
            'latitude'    => sanitize_text_field( $_POST['latitude'] ?? '' ),
            'longitude'   => sanitize_text_field( $_POST['longitude'] ?? '' ),
            'is_pickup'   => sanitize_text_field( $_POST['is_pickup'] ?? 'no' ),
            'is_dropoff'  => sanitize_text_field( $_POST['is_dropoff'] ?? 'no' ),
            'phone'       => sanitize_text_field( $_POST['phone'] ?? '' ),
            'email'       => sanitize_email( $_POST['email'] ?? '' ),
            'extra_fee'   => floatval( $_POST['extra_fee'] ?? 0 ),
        );
        
        if ( isset( $_POST['index'] ) && $_POST['index'] !== '' ) {
            // Update existing location
            $index = intval( $_POST['index'] );
            $locations[ $index ] = $location_data;
        } else {
            // Add new location
            $locations[] = $location_data;
        }
        
        update_option( 'wcrbtw_rental_locations', $locations );
        
        wp_send_json_success( array( 'message' => __( 'Location saved successfully', 'woocommerce-car-rental' ) ) );
    }

    /**
     * AJAX handler to delete location
     *
     * @since 1.0.0
     */
    public function ajax_delete_location() {
        check_ajax_referer( 'wcrbtw_locations_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied', 'woocommerce-car-rental' ) ) );
        }
        
        $index = intval( $_POST['index'] ?? -1 );
        
        if ( $index < 0 ) {
            wp_send_json_error( array( 'message' => __( 'Invalid location', 'woocommerce-car-rental' ) ) );
        }
        
        $locations = $this->get_all_locations();
        
        if ( isset( $locations[ $index ] ) ) {
            unset( $locations[ $index ] );
            $locations = array_values( $locations ); // Reindex array
            update_option( 'wcrbtw_rental_locations', $locations );
            wp_send_json_success( array( 'message' => __( 'Location deleted successfully', 'woocommerce-car-rental' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Location not found', 'woocommerce-car-rental' ) ) );
        }
    }

    /**
     * AJAX handler to get locations (public)
     *
     * @since 1.0.0
     */
    public function ajax_get_locations() {
        $type = sanitize_text_field( $_POST['type'] ?? 'all' );
        
        switch ( $type ) {
            case 'pickup':
                $locations = $this->get_pickup_locations();
                break;
            case 'dropoff':
                $locations = $this->get_dropoff_locations();
                break;
            default:
                $locations = $this->get_all_locations();
                break;
        }
        
        // Remove sensitive data for public requests
        if ( ! is_user_logged_in() || ! current_user_can( 'manage_woocommerce' ) ) {
            $locations = array_map( function( $location ) {
                return array(
                    'name'       => $location['name'],
                    'city'       => $location['city'] ?? '',
                    'latitude'   => $location['latitude'] ?? '',
                    'longitude'  => $location['longitude'] ?? '',
                    'extra_fee'  => $location['extra_fee'] ?? 0,
                );
            }, $locations );
        }
        
        wp_send_json_success( $locations );
    }
}

// Initialize locations management
if ( class_exists( 'WooCommerce' ) ) {
    WCRBTW_Locations::get_instance();
}
