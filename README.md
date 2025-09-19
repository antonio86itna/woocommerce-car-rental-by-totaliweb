# WooCommerce Car Rental by TotaliWeb

A comprehensive WordPress plugin that extends WooCommerce by adding a complete vehicle rental system with full HPOS (High Performance Order Storage) compatibility and REST API support.

## Description

This plugin adds a "Rental Vehicle" product type to WooCommerce, providing a complete rental management system with advanced features for vehicle rentals, including dynamic pricing, availability management, services, insurance options, and a REST API for external integrations.

## Features

### Core Features
- ✅ **New "Rental Vehicle" product type** in WooCommerce
- ✅ **Full HPOS (High Performance Order Storage) compatibility**
- ✅ **REST API v1** (`/wcr/v1/`) for complete programmatic access
- ✅ **Multi-tab admin interface** with 6 specialized sections
- ✅ **Dynamic pricing system** with seasonal rates
- ✅ **Availability management** with calendar blocking
- ✅ **Extra services** management (GPS, child seats, etc.)
- ✅ **Insurance options** with multiple coverage levels
- ✅ **Fleet management** for multiple vehicles
- ✅ Compatible with WooCommerce Cart and Checkout blocks
- ✅ Compatible with Product Block Editor
- ✅ PHP 8.3 type hints and modern PHP features
- ✅ Fully internationalized (i18n ready)

### Admin Interface Tabs

#### 1. **Details Tab**
- Vehicle type (car, scooter, van, SUV, truck)
- Number of seats
- Fuel type (gasoline, diesel, electric, hybrid, LPG)
- Transmission (manual, automatic, semi-automatic)
- Fleet quantity management
- Additional vehicle details

#### 2. **Rates Tab**
- Base daily rate configuration
- Seasonal/dynamic pricing rules
- Priority-based rate system
- Recurring annual rates support

#### 3. **Availability Tab**
- Manual date blocking calendar
- Quantity management by period
- Weekly recurring closures
- Maintenance notes

#### 4. **Services Tab**
- Unlimited extra services
- Flat rate or per-day pricing
- Service descriptions
- Enable/disable toggles

#### 5. **Insurance Tab**
- Multiple insurance options
- Per-day, flat rate, or percentage pricing
- Deductible amounts
- Coverage descriptions

#### 6. **Settings Tab**
- Minimum/maximum rental days
- Extra day hour threshold
- Security deposit amount
- Cancellation policy
- Additional rules and requirements

## Requirements

- WordPress 6.6 or higher
- PHP 8.3 or higher
- WooCommerce 9.0 or higher

## Installation

1. Upload the `woocommerce-car-rental-by-totaliweb` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure WooCommerce is installed and activated
4. Start creating rental vehicle products!

## Usage

### Creating a Rental Vehicle
1. Go to **Products > Add New** in WordPress admin
2. Select **"Rental Vehicle"** from the Product Data dropdown
3. Configure your vehicle using the 6 specialized tabs
4. Publish your rental vehicle product

### REST API Endpoints

The plugin provides a complete REST API under the namespace `/wcr/v1/`:

#### Vehicles Endpoints
```
GET    /wp-json/wcr/v1/vehicles           - List all rental vehicles
POST   /wp-json/wcr/v1/vehicles           - Create new vehicle
GET    /wp-json/wcr/v1/vehicles/{id}      - Get specific vehicle
PUT    /wp-json/wcr/v1/vehicles/{id}      - Update vehicle
DELETE /wp-json/wcr/v1/vehicles/{id}      - Delete vehicle
GET    /wp-json/wcr/v1/vehicles/{id}/availability - Check availability
```

#### Bookings Endpoints
```
GET    /wp-json/wcr/v1/bookings           - List bookings
POST   /wp-json/wcr/v1/bookings           - Create booking
GET    /wp-json/wcr/v1/bookings/{id}      - Get booking details
PUT    /wp-json/wcr/v1/bookings/{id}      - Update booking
DELETE /wp-json/wcr/v1/bookings/{id}      - Cancel booking
```

#### Services & Insurance
```
GET    /wp-json/wcr/v1/services           - List available services
GET    /wp-json/wcr/v1/insurance          - List insurance options
```

### Helper Functions

The plugin provides numerous helper functions for developers:

```php
// Check if product is rental vehicle
wcrbtw_is_rental_vehicle( $product_id );

// Get rental data
wcrbtw_get_rental_data( $product_id );
wcrbtw_get_vehicle_details( $product_id );
wcrbtw_get_vehicle_rates( $product_id );
wcrbtw_get_vehicle_availability( $product_id );
wcrbtw_get_vehicle_services( $product_id );
wcrbtw_get_vehicle_insurance( $product_id );
wcrbtw_get_vehicle_settings( $product_id );

// Calculate pricing
wcrbtw_calculate_rental_price( $product_id, $start_date, $end_date );

// Check availability
wcrbtw_is_vehicle_available( $product_id, $start_date, $end_date );
wcrbtw_get_booked_quantity( $product_id, $start_date, $end_date );

// Utility functions
wcrbtw_format_rental_period( $start_date, $end_date );
wcrbtw_get_min_rental_days( $product_id );
wcrbtw_get_max_rental_days( $product_id );
wcrbtw_get_security_deposit( $product_id );
```

## HPOS Compatibility

Full support for WooCommerce High Performance Order Storage:

```php
// Check HPOS status
WCRBTW_HPOS_Compatibility::is_hpos_enabled();

// HPOS-compatible order operations
WCRBTW_HPOS_Compatibility::get_order( $order_id );
WCRBTW_HPOS_Compatibility::get_order_meta( $order, 'meta_key' );
WCRBTW_HPOS_Compatibility::update_order_meta( $order, 'meta_key', 'value' );

// Query rental orders
WCRBTW_HPOS_Compatibility::get_rental_orders( $args );
WCRBTW_HPOS_Compatibility::order_contains_rentals( $order );
```

## Hooks and Filters

### Filters
- `wcrbtw_rental_vehicle_add_to_cart_text` - Customize add to cart text
- `wcrbtw_rental_vehicle_single_add_to_cart_text` - Single product cart text
- `wcrbtw_is_rental_vehicle_purchasable` - Control purchasability
- `wcrbtw_is_rental_vehicle_sold_individually` - Control individual sales
- `wcrbtw_rental_vehicle_availability` - Modify availability
- `wcrbtw_rental_vehicle_is_in_stock` - Control stock status
- `wcrbtw_rental_vehicle_needs_shipping` - Control shipping requirement
- `wcrbtw_rental_vehicle_backorders_allowed` - Control backorders
- `wcrbtw_rental_vehicle_is_on_sale` - Control sale status
- `wcrbtw_calculated_rental_price` - Filter calculated prices
- `wcrbtw_vehicle_availability` - Filter availability checks

### Actions
- `wcrbtw_init` - Plugin initialization
- `wcrbtw_init_rental_vehicle_data` - Initialize product data
- `wcrbtw_add_rental_meta_to_order` - Add order meta
- `wcrbtw_add_rental_meta_to_order_item` - Add order item meta
- `wcrbtw_after_save_rental_data` - After saving rental data

## File Structure

```
woocommerce-car-rental-by-totaliweb/
├── woocommerce-car-rental-by-totaliweb.php   # Main plugin file
├── uninstall.php                              # Cleanup on uninstall
├── includes/
│   ├── class-wc-product-rental-vehicle.php   # Product type class
│   ├── class-hpos-compatibility.php          # HPOS compatibility
│   ├── class-rest-api-controller.php         # REST API endpoints
│   └── rental-functions.php                  # Helper functions
├── admin/
│   ├── class-admin-product-data.php          # Admin interface
│   └── views/                                # Admin panel templates
│       ├── panel-rental-details.php
│       ├── panel-rental-rates.php
│       ├── panel-rental-availability.php
│       ├── panel-rental-services.php
│       ├── panel-rental-insurance.php
│       └── panel-rental-settings.php
├── assets/
│   ├── js/
│   │   └── admin-product.js                  # Admin JavaScript
│   └── css/
│       └── admin-product.css                 # Admin styles
└── languages/                                 # Translation files
```

## API-First Architecture

The plugin follows an API-first paradigm, making it perfect for:
- Mobile applications
- External booking systems
- Multi-channel rental management
- Custom frontend implementations
- Third-party integrations

All data is available via REST API with proper authentication and permission callbacks.

## Development

### Coding Standards
- PSR-12 compliance
- WordPress coding standards
- PHP 8.3 strict types
- Complete PHPDoc documentation
- Prefix: `wcrbtw_` for all functions
- Namespace: `wcr/v1` for REST API

### Security Features
- Nonce verification on all forms
- Capability checks for all operations
- Data sanitization and validation
- SQL injection prevention
- XSS protection

## License

GPL v2 or later

## Author

**TotaliWeb** - [https://totaliweb.it](https://totaliweb.it)

## Support

For support, feature requests, or bug reports, please visit our [support page](https://totaliweb.it/support).

## Version

1.0.0

## Changelog

### 1.0.0 (2024)
- Initial release
- Full HPOS compatibility
- Complete REST API implementation
- 6-tab admin interface
- Dynamic pricing system
- Availability management
- Services and insurance options
- Helper functions library
- Support for WordPress 6.6+, WooCommerce 9.x, PHP 8.3+
