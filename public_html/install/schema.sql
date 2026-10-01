-- Database Schema for AlixDeal Shopping
SET FOREIGN_KEY_CHECKS=0;

-- Admins Table
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `slug` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(50) DEFAULT 'folder',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products Table
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sku` VARCHAR(50) UNIQUE,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `original_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `rating` DECIMAL(2,1) DEFAULT 4.5,
  `reviews_count` INT DEFAULT 0,
  `category_id` INT,
  `image_url` TEXT,
  `is_deal` TINYINT(1) DEFAULT 0,
  `is_trending` TINYINT(1) DEFAULT 0,
  `stock` INT DEFAULT 100,
  `colors` VARCHAR(255) DEFAULT 'Standard',
  `sizes` VARCHAR(255) DEFAULT 'Standard',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders Table
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_number` VARCHAR(30) NOT NULL UNIQUE,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_email` VARCHAR(100) NOT NULL,
  `customer_phone` VARCHAR(30) NOT NULL,
  `shipping_address` TEXT NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `postal_code` VARCHAR(20),
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Pending',
  `payment_method` VARCHAR(50) DEFAULT 'Cash On Delivery',
  `payment_status` ENUM('Unpaid', 'Paid') DEFAULT 'Unpaid',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order Items Table
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT,
  `product_name` VARCHAR(255) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `selected_color` VARCHAR(50),
  `selected_size` VARCHAR(50),
  `subtotal` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Site Settings Table
CREATE TABLE IF NOT EXISTS `settings` (
  `key_name` VARCHAR(50) PRIMARY KEY,
  `value` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Categories
INSERT IGNORE INTO `categories` (`id`, `name`, `slug`, `icon`) VALUES
(1, 'Smart Electronics', 'smart-electronics', 'bolt'),
(2, 'Trending Apparel', 'trending-apparel', 'checkroom'),
(3, 'Home Comforts', 'home-comforts', 'chair'),
(4, 'Beauty & Tech', 'beauty-tech', 'sparkles');

-- Seed Products matching AlixDeal App Catalog
INSERT IGNORE INTO `products` (`id`, `sku`, `name`, `description`, `price`, `original_price`, `rating`, `reviews_count`, `category_id`, `image_url`, `is_deal`, `is_trending`, `stock`, `colors`, `sizes`) VALUES
(1, 'p1', 'Fast 3-in-1 Wireless Charger Stand', 'Fast wireless charging dock for smartphones, smartwatch, and earbuds simultaneously. Compact anti-slip design with over-temperature protection, suitable for bedside and office desk.', 499.00, 1299.00, 4.8, 428, 1, 'https://images.unsplash.com/photo-1586953208448-b95a79798f07?w=500&q=80', 1, 1, 50, 'Matte Black, Pure White', 'Standard'),
(2, 'p2', 'Wireless Bluetooth Earbuds (Deep Bass)', 'True wireless stereo earbuds with crystal-clear high bass and environmental noise cancellation. Offers 40 hours playtime with fast USB-C pocket charging case.', 699.00, 1999.00, 4.7, 812, 1, 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?w=500&q=80', 1, 1, 120, 'Pearl White, Jet Black, Navy Blue', 'Standard'),
(3, 'p3', 'Waterproof Laptop College & Office Backpack', 'Heavy-duty waterproof backpack with dedicated 15.6 inch padded laptop compartment, external USB mobile charging port, and anti-theft zipper for safe travel.', 599.00, 1499.00, 4.9, 286, 2, 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=500&q=80', 0, 1, 40, 'Navy Blue, Charcoal Black, Military Green', 'Medium (25L), Large (35L)'),
(4, 'p4', 'Ultrasonic Aroma Diffuser & Room Humidifier', 'Aroma essential oil diffuser with cool mist spray and 7-color soothing LED night light. Keeps room air fresh and fragrant for healthy breathing and sound sleep.', 399.00, 899.00, 4.6, 489, 3, 'https://images.unsplash.com/photo-1608571423902-eed4a5ad8108?w=500&q=80', 1, 0, 60, 'Natural Wood, Dark Walnut', '300ml, 500ml'),
(5, 'p5', 'Silicone Sonic Face Cleansing & Massager Brush', 'Waterproof rechargeable facial cleansing brush with gentle high-frequency vibrations. Removes dirt, dead cells, and makeup residue for a natural glowing face.', 249.00, 599.00, 4.5, 315, 4, 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?w=500&q=80', 1, 0, 85, 'Baby Pink, Rose Red, Sky Blue', 'Standard'),
(6, 'p6', 'Insulated Stainless Steel Coffee & Chai Mug', 'Double-wall vacuum insulated mug with leak-proof flip lid. Keeps hot chai or coffee warm for 6 hours and cold drinks chilled for 12 hours.', 349.00, 799.00, 4.7, 238, 3, 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?w=500&q=80', 0, 1, 30, 'Matte Black, Pearl White, Steel Grey', '380ml, 510ml'),
(7, 'p7', 'Portable USB Rechargeable Mini Juicer Blender', 'Portable 6-blade smoothie and fruit juicer bottle with USB rechargeable battery. Blend fresh fruit shakes, baby food, and protein drinks in seconds on the go.', 449.00, 999.00, 4.6, 562, 3, 'https://images.unsplash.com/photo-1578643463396-0997cb5328c1?w=500&q=80', 1, 1, 95, 'Mint Green, Coral Pink, Sky Blue', '400ml'),
(8, 'p8', 'Bluetooth Calling Smart Watch (Fitness & SpO2)', '1.85 inch bright HD full touch screen with Bluetooth calling, instant dialer, heart rate, blood oxygen monitor, and 100+ fitness tracking sports modes.', 1299.00, 2999.00, 4.8, 1040, 1, 'https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?w=500&q=80', 1, 1, 75, 'Space Black, Midnight Blue, Rose Gold', 'Free Size (Silicone Strap)'),
(9, 'p9', 'Pure Cotton Casual Slim Fit Full Sleeve Shirt', '100% premium breathable cotton casual shirt for men. Soft wash finish with classic spread collar, ideal for office, college, and casual outings.', 499.00, 1199.00, 4.7, 320, 2, 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=500&q=80', 1, 1, 80, 'Sky Blue, Snow White, Olive Green', 'M (38), L (40), XL (42), XXL (44)'),
(10, 'p10', 'Smart LED Temperature Display Water Bottle', 'Touch screen LED temperature display vacuum thermos flask. Made with food-grade 304 stainless steel with tea infuser filter.', 299.00, 699.00, 4.7, 415, 3, 'https://images.unsplash.com/photo-1602143407151-7111542de6e8?w=500&q=80', 1, 0, 90, 'Matte Black, Metallic Red, Royal Blue', '500ml');

-- Seed Settings
INSERT IGNORE INTO `settings` (`key_name`, `value`) VALUES
('site_name', 'AlixDeal Shopping'),
('site_description', 'Exclusive daily deals on electronics, fashion, and home essentials.'),
('currency_symbol', '₹'),
('contact_email', 'support@alixdeal.shop'),
('contact_phone', '+91 98765 43210'),
('address', 'Mumbai, Maharashtra, India');

SET FOREIGN_KEY_CHECKS=1;
