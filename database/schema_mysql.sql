-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image_path VARCHAR(255),
    is_visible TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Create product_variations table
CREATE TABLE IF NOT EXISTS product_variations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT,
    name VARCHAR(255) NOT NULL,
    price_adjustment DECIMAL(10,2) DEFAULT 0,
    stock INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Create ring_attributes table
CREATE TABLE IF NOT EXISTS `ring_attributes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `type` enum('size','metal','gemstone','finish') NOT NULL,
    `description` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name_type` (`name`, `type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create ring_attribute_values table
CREATE TABLE IF NOT EXISTS `ring_attribute_values` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `attribute_id` int(11) NOT NULL,
    `value` varchar(50) NOT NULL,
    `description` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `attribute_id` (`attribute_id`),
    CONSTRAINT `ring_attribute_values_ibfk_1` FOREIGN KEY (`attribute_id`) REFERENCES `ring_attributes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create ring_variations table
CREATE TABLE IF NOT EXISTS `ring_variations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) NOT NULL,
    `sku` varchar(50) NOT NULL,
    `size_value_id` int(11),
    `metal_value_id` int(11),
    `gemstone_value_id` int(11),
    `finish_value_id` int(11),
    `price_adjustment` decimal(10,2) NOT NULL DEFAULT 0.00,
    `stock` int(11) NOT NULL DEFAULT 0,
    `image_path` varchar(255),
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `sku` (`sku`),
    KEY `product_id` (`product_id`),
    KEY `size_value_id` (`size_value_id`),
    KEY `metal_value_id` (`metal_value_id`),
    KEY `gemstone_value_id` (`gemstone_value_id`),
    KEY `finish_value_id` (`finish_value_id`),
    CONSTRAINT `ring_variations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    CONSTRAINT `ring_variations_ibfk_2` FOREIGN KEY (`size_value_id`) REFERENCES `ring_attribute_values` (`id`) ON DELETE SET NULL,
    CONSTRAINT `ring_variations_ibfk_3` FOREIGN KEY (`metal_value_id`) REFERENCES `ring_attribute_values` (`id`) ON DELETE SET NULL,
    CONSTRAINT `ring_variations_ibfk_4` FOREIGN KEY (`gemstone_value_id`) REFERENCES `ring_attribute_values` (`id`) ON DELETE SET NULL,
    CONSTRAINT `ring_variations_ibfk_5` FOREIGN KEY (`finish_value_id`) REFERENCES `ring_attribute_values` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create ring_custom_attributes table
CREATE TABLE IF NOT EXISTS ring_custom_attributes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    variation_id INT NOT NULL,
    attribute_id INT NOT NULL,
    value VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (variation_id) REFERENCES ring_variations(id),
    FOREIGN KEY (attribute_id) REFERENCES ring_attributes(id)
);

-- Create customers table
CREATE TABLE IF NOT EXISTS customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(50),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    total_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    payment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Create order_items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    product_id INT,
    variation_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (variation_id) REFERENCES product_variations(id) ON DELETE SET NULL
);

-- Create admin_users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    reset_token VARCHAR(255),
    reset_token_expires DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO admin_users (email, password, name) VALUES 
('admin@glamourshop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User');

-- Insert default categories
INSERT INTO categories (name, slug, description) VALUES 
('Rings', 'rings', 'Beautiful rings collection'),
('Necklaces', 'necklaces', 'Elegant necklaces'),
('Earrings', 'earrings', 'Stylish earrings'),
('Bracelets', 'bracelets', 'Trendy bracelets'),
('Pendants', 'pendants', 'Charming pendants');

-- Insert default ring attributes
INSERT INTO `ring_attributes` (`name`, `type`, `description`) VALUES
('Ring Size', 'size', 'Standard ring sizes'),
('Metal Type', 'metal', 'Types of metals used in rings'),
('Gemstone Type', 'gemstone', 'Types of gemstones used in rings'),
('Finish Type', 'finish', 'Surface finish types for rings');

-- Insert default ring sizes
INSERT INTO `ring_attribute_values` (`attribute_id`, `value`) VALUES
(1, '4'), (1, '4.5'), (1, '5'), (1, '5.5'), (1, '6'), (1, '6.5'), (1, '7'), (1, '7.5'), (1, '8'), (1, '8.5'), (1, '9'), (1, '9.5'), (1, '10');

-- Insert default metal types
INSERT INTO `ring_attribute_values` (`attribute_id`, `value`) VALUES
(2, 'Yellow Gold'), (2, 'White Gold'), (2, 'Rose Gold'), (2, 'Platinum'), (2, 'Silver');

-- Insert default gemstone types
INSERT INTO `ring_attribute_values` (`attribute_id`, `value`) VALUES
(3, 'Diamond'), (3, 'Ruby'), (3, 'Sapphire'), (3, 'Emerald'), (3, 'Pearl'), (3, 'No Gemstone');

-- Insert default finish types
INSERT INTO `ring_attribute_values` (`attribute_id`, `value`) VALUES
(4, 'Polished'), (4, 'Matte'), (4, 'Brushed'), (4, 'Hammered'), (4, 'Satin'); 