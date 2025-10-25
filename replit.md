# Standalone Ecommerce Application

## Overview
Aplikasi ecommerce standalone yang dimigrasi dari WooCommerce WordPress plugin. Dibangun dengan PHP native menggunakan Slim Framework, Twig templating, dan SQLite database.

## Project Structure
```
├── public/             # Web root directory
│   └── index.php      # Application entry point & routing
├── src/               # Source code
│   └── Database.php   # Database connection & initialization
├── templates/         # Twig templates
│   ├── index.twig     # Homepage
│   └── admin/         # Admin panel templates
│       ├── layout.twig
│       ├── login.twig
│       ├── dashboard.twig
│       ├── products.twig
│       ├── orders.twig
│       └── customers.twig
├── config/            # Configuration files
│   ├── config.php     # Application configuration
│   └── schema.sql     # Database schema
├── vendor/            # Composer dependencies
├── database.sqlite    # SQLite database file
└── composer.json      # PHP dependencies
```

## Core Features
✅ **Admin Authentication** - Login system dengan password hashing, session management, dan CSRF protection
✅ **Admin Dashboard** - Statistics overview (products, orders, customers count)
✅ **Product Management** - View products dengan SKU, price, stock information
✅ **Order Management** - View orders dengan status dan customer information
✅ **Customer Management** - View customer list dengan contact details
✅ **Database Schema** - Complete schema untuk products, categories, customers, orders, carts
✅ **Security** - CSRF tokens, password hashing, session regeneration, foreign key constraints

## Database Schema
Aplikasi menggunakan SQLite dengan tabel-tabel berikut:
- `products` - Product catalog dengan pricing dan stock
- `categories` - Product categories
- `product_categories` - Product-category relationships
- `customers` - Customer accounts
- `customer_addresses` - Customer shipping/billing addresses
- `orders` - Order records
- `order_items` - Order line items
- `carts` - Shopping carts
- `cart_items` - Cart contents
- `admin_users` - Admin panel users
- `settings` - Application settings

## Admin Panel Access
- URL: `/admin`
- Default credentials: Check database seed in `config/schema.sql`
- Password: `admin123` (default - harus diganti di production!)

## Technology Stack
- **PHP 8.2** - Server-side language
- **Slim Framework 4** - Micro-framework untuk routing
- **Twig 3** - Templating engine
- **PHP-DI** - Dependency injection container
- **SQLite** - Database (dengan foreign key constraints enabled)
- **Composer** - Dependency management

## Development
Server development sudah dikonfigurasi dan running pada port 5000:
```bash
php -S 0.0.0.0:5000 -t public
```

## Deployment
Aplikasi dikonfigurasi untuk Autoscale deployment:
- Type: `autoscale` (stateless web application)
- Command: `php -S 0.0.0.0:5000 -t public`
- Port: 5000

## Security Notes
⚠️ **Important Security Considerations:**
1. Ganti default admin password sebelum production
2. CSRF protection sudah enabled untuk admin login
3. Session IDs di-regenerate setelah login
4. Foreign key constraints enabled di SQLite
5. Password hashing menggunakan PHP `password_hash()`

## Migration Notes
Aplikasi ini adalah simplified version dari WooCommerce plugin dengan fokus pada:
- Core ecommerce functionality (products, customers, orders)
- Standalone operation (tidak bergantung WordPress)
- Clean architecture dengan separation of concerns
- Security best practices untuk authentication

## Next Steps (Future Development)
- [ ] Implementasi full CRUD untuk Products (Create, Update, Delete)
- [ ] Implementasi Category Management
- [ ] Implementasi Customer detail view dan edit
- [ ] Implementasi Order status update
- [ ] Frontend shopping cart dan checkout flow
- [ ] Payment gateway integration
- [ ] Shipping method configuration
- [ ] Email notifications
- [ ] Product images upload
- [ ] Search and filtering
- [ ] Reports dan analytics

## Recent Changes
- 2025-10-25: Initial migration dari WooCommerce plugin
- Database schema implemented dengan SQLite
- Admin panel dengan authentication dan basic CRUD views
- Security hardening: CSRF protection, session regeneration, foreign key constraints

## User Preferences
- Menggunakan Bahasa Indonesia untuk komunikasi
- Focus pada core ecommerce features
- Security dan best practices sebagai prioritas
