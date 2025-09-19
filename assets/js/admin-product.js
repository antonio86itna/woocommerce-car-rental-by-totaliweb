/**
 * Admin Product JavaScript
 *
 * Handles the rental vehicle product admin interface
 *
 * @package WooCommerce_Car_Rental
 * @since 1.0.0
 */

(function($, window, document) {
    'use strict';

    // Wait for document ready
    $(document).ready(function() {
        console.log('WCRBTW: Initializing admin interface...');
        
        /**
         * Function to handle tab visibility
         */
        function handleRentalTabs() {
            var productType = $('#product-type').val();
            console.log('WCRBTW: Product type is:', productType);
            
            if (productType === 'rental_vehicle') {
                // Add class to body for CSS targeting
                $('body').addClass('rental_vehicle');
                
                // Show rental tabs
                $('.rental_details_tab').show();
                $('.rental_rates_tab').show();
                $('.rental_availability_tab').show();
                $('.rental_services_tab').show();
                $('.rental_insurance_tab').show();
                $('.rental_settings_tab').show();
                
                // Hide standard tabs we don't need
                $('.shipping_tab').hide();
                $('.linked_product_tab').hide();
                
                // Ensure panels are in correct state
                $('#rental_details_data').removeClass('hidden');
                $('#rental_rates_data').removeClass('hidden');
                $('#rental_availability_data').removeClass('hidden');
                $('#rental_services_data').removeClass('hidden');
                $('#rental_insurance_data').removeClass('hidden');
                $('#rental_settings_data').removeClass('hidden');
                
                console.log('WCRBTW: Rental tabs shown');
            } else {
                // Remove body class
                $('body').removeClass('rental_vehicle');
                
                // Hide rental tabs
                $('.rental_details_tab').hide();
                $('.rental_rates_tab').hide();
                $('.rental_availability_tab').hide();
                $('.rental_services_tab').hide();
                $('.rental_insurance_tab').hide();
                $('.rental_settings_tab').hide();
                
                // Show standard tabs
                $('.shipping_tab').show();
                $('.linked_product_tab').show();
                
                // Hide rental panels
                $('#rental_details_data').addClass('hidden');
                $('#rental_rates_data').addClass('hidden');
                $('#rental_availability_data').addClass('hidden');
                $('#rental_services_data').addClass('hidden');
                $('#rental_insurance_data').addClass('hidden');
                $('#rental_settings_data').addClass('hidden');
                
                console.log('WCRBTW: Rental tabs hidden');
            }
        }
        
        /**
         * Initialize on page load
         */
        function initRentalVehicle() {
            // Initial check
            handleRentalTabs();
            
            // Listen for product type changes
            $('#product-type').on('change', function() {
                console.log('WCRBTW: Product type changed');
                handleRentalTabs();
            });
            
            // Listen for WooCommerce product type change event
            $(document.body).on('woocommerce-product-type-change', function(e, productType) {
                console.log('WCRBTW: WC product type change event fired:', productType);
                setTimeout(handleRentalTabs, 10);
            });
            
            // Initialize repeater functionality
            initRepeaterFields();
        }
        
        /**
         * Initialize repeater fields
         */
        function initRepeaterFields() {
            // Add row functionality
            $(document).on('click', '.wcrbtw-add-row', function(e) {
                e.preventDefault();
                console.log('WCRBTW: Adding repeater row');
                
                var $button = $(this);
                var $container = $button.closest('.wcrbtw-repeater-container');
                var $tbody = $container.find('tbody');
                var $lastRow = $tbody.find('tr:last');
                
                if ($lastRow.length) {
                    var $newRow = $lastRow.clone();
                    var newIndex = $tbody.find('tr').length;
                    
                    // Update field names
                    $newRow.find('input, select, textarea').each(function() {
                        var name = $(this).attr('name');
                        if (name) {
                            name = name.replace(/\[\d+\]/, '[' + newIndex + ']');
                            $(this).attr('name', name);
                        }
                        
                        // Clear values
                        if ($(this).attr('type') === 'checkbox') {
                            $(this).prop('checked', false);
                        } else {
                            $(this).val('');
                        }
                    });
                    
                    $tbody.append($newRow);
                }
            });
            
            // Remove row functionality
            $(document).on('click', '.wcrbtw-remove-row', function(e) {
                e.preventDefault();
                console.log('WCRBTW: Removing repeater row');
                
                var $button = $(this);
                var $row = $button.closest('tr');
                var $tbody = $row.closest('tbody');
                
                if ($tbody.find('tr').length > 1) {
                    $row.remove();
                } else {
                    // Clear the last row
                    $row.find('input:not([type="checkbox"]), textarea').val('');
                    $row.find('input[type="checkbox"]').prop('checked', false);
                    $row.find('select').val('');
                }
            });
        }
        
        // Start initialization
        initRentalVehicle();
        
        // Double-check after WooCommerce loads
        $(document.body).on('wc-init-tabbed-panels', function() {
            console.log('WCRBTW: WC tabbed panels initialized, rechecking...');
            setTimeout(handleRentalTabs, 100);
        });
        
        // Fallback check after delay
        setTimeout(function() {
            console.log('WCRBTW: Final check after 1 second');
            handleRentalTabs();
        }, 1000);
        
        // Check if product type is already set (editing existing product)
        if (wcrbtw_admin && wcrbtw_admin.product_type === 'rental_vehicle') {
            console.log('WCRBTW: Existing rental vehicle detected');
            $('body').addClass('rental_vehicle');
            setTimeout(handleRentalTabs, 200);
        }
    });
    
})(jQuery, window, document);
