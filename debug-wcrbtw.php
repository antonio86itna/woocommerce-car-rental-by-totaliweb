<?php
/**
 * Debug Helper for WooCommerce Car Rental Plugin
 * 
 * Place this file in your WordPress root directory and access it via browser
 * to debug tab visibility issues.
 * 
 * @package WooCommerce_Car_Rental
 * @since 1.0.0
 */

// Load WordPress
require_once 'wp-load.php';

// Check if user is admin
if ( ! current_user_can( 'manage_options' ) ) {
    die( 'Access denied. You must be an administrator.' );
}

// Get plugin status
$plugin_active = is_plugin_active( 'woocommerce-car-rental-by-totaliweb/woocommerce-car-rental-by-totaliweb.php' );
$wc_active = class_exists( 'WooCommerce' );

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>WCRBTW Debug Info</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .status {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre {
            background: #f0f0f0;
            padding: 10px;
            overflow-x: auto;
            border-radius: 3px;
        }
        h2 {
            border-bottom: 2px solid #0073aa;
            padding-bottom: 10px;
        }
        .test-button {
            background: #0073aa;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 0;
        }
        .test-button:hover {
            background: #005a87;
        }
    </style>
    <?php wp_head(); ?>
</head>
<body>
    <h1>WooCommerce Car Rental Plugin Debug</h1>
    
    <div class="status">
        <h2>Plugin Status</h2>
        <p>Plugin Active: <span class="<?php echo $plugin_active ? 'success' : 'error'; ?>">
            <?php echo $plugin_active ? 'YES ✓' : 'NO ✗'; ?>
        </span></p>
        <p>WooCommerce Active: <span class="<?php echo $wc_active ? 'success' : 'error'; ?>">
            <?php echo $wc_active ? 'YES ✓' : 'NO ✗'; ?>
        </span></p>
    </div>
    
    <?php if ( $plugin_active && $wc_active ) : ?>
    
    <div class="status">
        <h2>Product Types</h2>
        <?php
        $product_types = wc_get_product_types();
        echo '<pre>';
        print_r( $product_types );
        echo '</pre>';
        
        $has_rental = isset( $product_types['rental_vehicle'] );
        ?>
        <p>Rental Vehicle Type Registered: <span class="<?php echo $has_rental ? 'success' : 'error'; ?>">
            <?php echo $has_rental ? 'YES ✓' : 'NO ✗'; ?>
        </span></p>
    </div>
    
    <div class="status">
        <h2>Registered Tabs</h2>
        <?php
        $tabs = apply_filters( 'woocommerce_product_data_tabs', array() );
        $rental_tabs = array_filter( $tabs, function( $key ) {
            return strpos( $key, 'rental' ) !== false;
        }, ARRAY_FILTER_USE_KEY );
        
        echo '<h3>Rental Tabs Found:</h3>';
        if ( ! empty( $rental_tabs ) ) {
            echo '<pre>';
            print_r( $rental_tabs );
            echo '</pre>';
        } else {
            echo '<p class="error">No rental tabs found!</p>';
        }
        ?>
    </div>
    
    <div class="status">
        <h2>JavaScript Test</h2>
        <p>Open browser console (F12) and click the button below to test JavaScript:</p>
        <button class="test-button" onclick="testRentalTabs()">Test Tab Visibility</button>
        
        <script>
        function testRentalTabs() {
            console.log('=== WCRBTW Debug Test ===');
            
            if (typeof jQuery === 'undefined') {
                console.error('jQuery not loaded!');
                return;
            }
            
            console.log('jQuery version:', jQuery.fn.jquery);
            
            // Check if we're on product edit page
            var productType = jQuery('#product-type');
            if (productType.length) {
                console.log('Product type select found');
                console.log('Current value:', productType.val());
            } else {
                console.log('Not on product edit page or product type select not found');
            }
            
            // Check for tabs
            var tabs = jQuery('.product_data_tabs li');
            console.log('Total tabs found:', tabs.length);
            
            tabs.each(function(index) {
                var $tab = jQuery(this);
                var classes = $tab.attr('class');
                var $link = $tab.find('a');
                var href = $link.attr('href');
                var text = $link.text();
                
                console.log('Tab ' + index + ':', {
                    text: text,
                    href: href,
                    classes: classes,
                    visible: $tab.is(':visible')
                });
            });
            
            // Check for rental tabs specifically
            var rentalTabs = jQuery('.product_data_tabs li').filter(function() {
                var href = jQuery(this).find('a').attr('href');
                return href && href.indexOf('rental') !== -1;
            });
            
            console.log('Rental tabs found:', rentalTabs.length);
            
            console.log('=== End Debug Test ===');
        }
        </script>
    </div>
    
    <div class="status">
        <h2>Quick Fix</h2>
        <p>Add this code to your theme's functions.php to force tab visibility:</p>
        <pre>
add_action('admin_footer', function() {
    global $post_type;
    if ($post_type == 'product') {
        ?>
        &lt;script&gt;
        jQuery(document).ready(function($) {
            $('#product-type').on('change', function() {
                if ($(this).val() === 'rental_vehicle') {
                    // Force show rental tabs
                    $('.product_data_tabs li').each(function() {
                        var href = $(this).find('a').attr('href');
                        if (href && href.indexOf('rental_') !== -1) {
                            $(this).show();
                        }
                    });
                    // Hide shipping tab
                    $('.shipping_tab').hide();
                    console.log('Rental tabs forced visible');
                }
            }).trigger('change');
        });
        &lt;/script&gt;
        <?php
    }
});
        </pre>
    </div>
    
    <?php endif; ?>
    
    <div class="status">
        <h2>System Info</h2>
        <p>WordPress Version: <?php echo get_bloginfo( 'version' ); ?></p>
        <p>PHP Version: <?php echo phpversion(); ?></p>
        <p>WooCommerce Version: <?php echo defined( 'WC_VERSION' ) ? WC_VERSION : 'Not detected'; ?></p>
        <p>Active Theme: <?php echo wp_get_theme()->get( 'Name' ); ?></p>
    </div>
    
    <?php wp_footer(); ?>
</body>
</html>
