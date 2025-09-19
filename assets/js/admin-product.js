/**
 * Admin Product JavaScript
 *
 * Handles the rental vehicle product admin interface
 *
 * @package WooCommerce_Car_Rental
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    console.log('WCRBTW: Admin script loaded');

    $(document).ready(function() {
        console.log('WCRBTW: Initializing admin interface...');
        
        // Initialize repeater fields functionality
        initRepeaterFields();
        
        // Log current product type
        var productType = $('#product-type').val();
        console.log('WCRBTW: Current product type:', productType);
        
        // The tab visibility is now handled by WooCommerce natively
        // thanks to the show_if_rental_vehicle class on panels
        
        /**
         * Initialize repeater fields
         */
        function initRepeaterFields() {
            // Add row button
            $(document).on('click', '.wcrbtw-add-row', function(e) {
                e.preventDefault();
                addRepeaterRow($(this));
            });
            
            // Remove row button
            $(document).on('click', '.wcrbtw-remove-row', function(e) {
                e.preventDefault();
                removeRepeaterRow($(this));
            });
            
            // Price field formatting
            $(document).on('blur', '.wc_input_price', function() {
                var $input = $(this);
                var value = $input.val();
                
                if (value !== '') {
                    value = parseFloat(value.replace(/[^\d.-]/g, ''));
                    if (!isNaN(value)) {
                        $input.val(value.toFixed(2));
                    }
                }
            });
        }
        
        /**
         * Add a repeater row
         */
        function addRepeaterRow($button) {
            console.log('WCRBTW: Adding repeater row');
            
            var $container = $button.closest('.wcrbtw-repeater-container');
            var $tbody = $container.find('tbody');
            var $lastRow = $tbody.find('tr').last();
            
            if ($lastRow.length) {
                var $newRow = $lastRow.clone();
                var newIndex = $tbody.find('tr').length;
                
                // Update field names and clear values
                $newRow.find('input, select, textarea').each(function() {
                    var $field = $(this);
                    var name = $field.attr('name');
                    
                    if (name) {
                        // Update the index in the field name
                        name = name.replace(/\[\d+\]/, '[' + newIndex + ']');
                        $field.attr('name', name);
                    }
                    
                    // Clear field values
                    if ($field.is(':checkbox')) {
                        $field.prop('checked', false);
                    } else {
                        $field.val('');
                    }
                });
                
                $tbody.append($newRow);
            }
        }
        
        /**
         * Remove a repeater row
         */
        function removeRepeaterRow($button) {
            console.log('WCRBTW: Removing repeater row');
            
            var $row = $button.closest('tr');
            var $tbody = $row.closest('tbody');
            
            // Don't remove the last row
            if ($tbody.find('tr').length > 1) {
                $row.fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                // Clear the fields in the last row instead
                $row.find('input:not(:checkbox), textarea').val('');
                $row.find('input:checkbox').prop('checked', false);
                $row.find('select').prop('selectedIndex', 0);
            }
        }
        
        // Log that initialization is complete
        console.log('WCRBTW: Admin interface initialized');
    });

})(jQuery);
