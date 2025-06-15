# Glamour Shop - Jewelry E-commerce Website

A fully functional jewelry e-commerce website built with HTML, CSS, JavaScript, PHP, and SQLite.

## Features

### Customer Features
- Browse products by category
- Filter and sort products
- View product details
- Add to cart functionality
- Secure checkout process
- Order tracking

### Admin Features
- Secure admin dashboard
- Product management (CRUD operations)
- Order management
- Customer management
- Sales analytics
- Stock management

## Project Structure
```
glamour-shop/
├── admin/             # Admin dashboard files
├── assets/           # Static assets (images, CSS, JS)
├── includes/         # PHP includes and functions
├── database/         # SQLite database files
└── public/           # Public-facing website files
```

## Setup Instructions

1. Clone the repository
2. Configure your web server (Apache/Nginx) to point to the project directory
3. Ensure PHP 7.4+ is installed with SQLite support
4. Set up the database by running the setup script
5. Configure email settings for password reset functionality

## Requirements
- PHP 7.4 or higher
- SQLite3
- Web server (Apache/Nginx)
- Modern web browser with JavaScript enabled

## Security
- All passwords are hashed using PHP's password_hash()
- SQL injection prevention using prepared statements
- XSS protection through proper escaping
- CSRF protection on forms 