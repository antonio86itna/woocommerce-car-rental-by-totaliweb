# WooCommerce Car Rental - Post Meta Keys Documentation

This document lists all post meta keys used by the WooCommerce Car Rental plugin. These keys are accessible via Elementor Dynamic Tags, ACF, and other page builders.

## Meta Keys Reference

All meta keys use the prefix `_wcrbtw_` for easy identification and to avoid conflicts.

### Vehicle Details (Tab 1)
- `_wcrbtw_vehicle_type` - Vehicle type (car/scooter/van/suv/truck)
- `_wcrbtw_seats` - Number of seats (integer)
- `_wcrbtw_fuel_type` - Fuel type (gasoline/diesel/electric/hybrid/lpg)
- `_wcrbtw_transmission` - Transmission type (manual/automatic/semi-auto)
- `_wcrbtw_fleet_quantity` - Total fleet quantity (integer)
- `_wcrbtw_additional_details` - Additional details (text/HTML)

### Rental Rates (Tab 2)
- `_wcrbtw_base_daily_rate` - Base daily rental rate (decimal)
- `_wcrbtw_seasonal_rates` - Seasonal rates (JSON string)
  ```json
  [
    {
      "name": "Summer Season",
      "start_date": "2024-06-01",
      "end_date": "2024-08-31",
      "rate": 150.00,
      "priority": 10,
      "recurring": "yes"
    }
  ]
  ```

### Availability (Tab 3)
- `_wcrbtw_blocked_dates` - Blocked dates (JSON array of date strings)
  ```json
  ["2024-12-25", "2024-12-26", "2024-12-31"]
  ```
- `_wcrbtw_quantity_periods` - Quantity per period (JSON string)
  ```json
  [
    {
      "start_date": "2024-06-01",
      "end_date": "2024-08-31",
      "quantity": 5
    }
  ]
  ```
- `_wcrbtw_weekly_closures` - Weekly closure days (JSON array of day numbers, 0=Sunday)
  ```json
  [0, 6]
  ```
- `_wcrbtw_maintenance_notes` - Maintenance notes (text/HTML)

### Services (Tab 4)
- `_wcrbtw_services` - Extra services (JSON string)
  ```json
  [
    {
      "name": "GPS Navigation",
      "price_type": "daily",
      "price": 15.00,
      "description": "GPS navigation system",
      "enabled": "yes"
    }
  ]
  ```

### Insurance (Tab 5)
- `_wcrbtw_insurance` - Insurance options (JSON string)
  ```json
  [
    {
      "name": "Full Coverage",
      "cost_type": "daily",
      "cost": 25.00,
      "deductible": 500.00,
      "description": "Complete coverage with low deductible",
      "enabled": "yes"
    }
  ]
  ```

### Settings (Tab 6)
- `_wcrbtw_min_days` - Minimum rental days (integer)
- `_wcrbtw_max_days` - Maximum rental days (integer)
- `_wcrbtw_extra_day_hour` - Hour after which extra day is charged (integer, 0-23)
- `_wcrbtw_security_deposit` - Security deposit amount (decimal)
- `_wcrbtw_cancellation_policy` - Cancellation policy text (text/HTML)
- `_wcrbtw_additional_settings` - Additional settings/rules (text/HTML)

## Usage Examples

### Elementor Dynamic Tags
In Elementor, you can access these fields using:
1. Dynamic Tags → Post → Post Custom Field
2. Enter the meta key (e.g., `_wcrbtw_vehicle_type`)
3. For JSON fields, you may need custom processing

### ACF (Advanced Custom Fields)
To display these fields with ACF:
```php
// Get single value
$vehicle_type = get_field('_wcrbtw_vehicle_type', $product_id);

// Get JSON field and decode
$services = json_decode(get_field('_wcrbtw_services', $product_id), true);
```

### Direct WordPress Usage
```php
// Get single meta value
$seats = get_post_meta($product_id, '_wcrbtw_seats', true);

// Get and decode JSON meta
$seasonal_rates = json_decode(get_post_meta($product_id, '_wcrbtw_seasonal_rates', true), true);
```

### Display in Templates
```php
// Display vehicle details
echo 'Vehicle Type: ' . get_post_meta($product_id, '_wcrbtw_vehicle_type', true);
echo 'Seats: ' . get_post_meta($product_id, '_wcrbtw_seats', true);
echo 'Fuel: ' . get_post_meta($product_id, '_wcrbtw_fuel_type', true);

// Display base rate
echo 'Daily Rate: ' . wc_price(get_post_meta($product_id, '_wcrbtw_base_daily_rate', true));
```

## Helper Functions

The plugin provides helper functions that automatically handle the meta retrieval:

```php
// Get all rental data
$rental_data = wcrbtw_get_rental_data($product_id);

// Get specific sections
$details = wcrbtw_get_vehicle_details($product_id);
$rates = wcrbtw_get_vehicle_rates($product_id);
$availability = wcrbtw_get_vehicle_availability($product_id);
$services = wcrbtw_get_vehicle_services($product_id);
$insurance = wcrbtw_get_vehicle_insurance($product_id);
$settings = wcrbtw_get_vehicle_settings($product_id);
```

## Notes

- All meta keys are prefixed with underscore (_) to be hidden from custom fields UI by default
- JSON fields are stored as strings and need to be decoded before use
- All data is sanitized before saving
- Empty fields may return empty string or null
- For repeater fields (services, insurance, seasonal rates), the data is stored as JSON

## Future Location/Pickup Points

The plugin is prepared for location management with reserved meta keys:
- `_wcrbtw_pickup_locations` - Available pickup locations (future implementation)
- `_wcrbtw_dropoff_locations` - Available drop-off locations (future implementation)

Each location will have:
- Name
- Address
- Latitude
- Longitude
- Additional fees (if any)
