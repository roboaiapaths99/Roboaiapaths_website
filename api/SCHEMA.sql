-- SQL Schema for Robo AI Paths Marketplace

-- Users table (derived from OTP login)
CREATE TABLE IF NOT EXISTS msg_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mobile VARCHAR(15) UNIQUE NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table (Kits)
CREATE TABLE IF NOT EXISTS product_kits (
    id VARCHAR(20) PRIMARY KEY, -- kit1, kit2, etc.
    name VARCHAR(255) NOT NULL,
    selling_price DECIMAL(10, 2) NOT NULL,
    mrp DECIMAL(10, 2),
    description TEXT,
    is_out_of_stock BOOLEAN DEFAULT FALSE,
    image_path VARCHAR(255)
);

-- Coupons table
CREATE TABLE IF NOT EXISTS coupons (
    code VARCHAR(50) PRIMARY KEY,
    type ENUM('percent', 'fixed') NOT NULL,
    value DECIMAL(10, 2) NOT NULL,
    active BOOLEAN DEFAULT TRUE
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_mobile VARCHAR(15) NOT NULL,
    txnid VARCHAR(100) UNIQUE NOT NULL, -- PayU Transaction ID
    subtotal DECIMAL(10, 2) NOT NULL,
    discount DECIMAL(10, 2) DEFAULT 0,
    gst DECIMAL(10, 2) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    coupon_used VARCHAR(50),
    status ENUM('pending', 'success', 'failed', 'tampered') DEFAULT 'pending',
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    zip VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Order items
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id VARCHAR(20) NOT NULL,
    quantity INT NOT NULL,
    price_at_time DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- Initial Data
INSERT INTO coupons (code, type, value) VALUES 
('ROBO10', 'percent', 10),
('FLAT500', 'fixed', 500),
('SAPNA40', 'percent', 40);

INSERT INTO product_kits (id, name, selling_price, mrp, is_out_of_stock) VALUES 
('kit1', 'Non Programmable Kit', 5909, 6500, FALSE),
('kit2', 'Introduction to Robotics', 5909, 6500, FALSE),
('kit3', 'Otto Ninja', 10067, 11074, TRUE),
('kit4', 'Plug and Play Kit', 4369, 4806, TRUE),
('kit5', 'Robo Expert', 8217, 9039, FALSE),
('kit6', 'Jetty Bot Car', 5737, 6311, FALSE),
('kit7', 'Smart IOT Home', 5394, 5933, TRUE),
('kit8', 'MR Bot', 4369, 4806, TRUE),
('kit9', 'PY Card', 5566, 6123, FALSE),
('kit10', 'PC Bot', 6593, 7252, FALSE),
('kit11', 'RC Drift Car', 6593, 7252, FALSE);
