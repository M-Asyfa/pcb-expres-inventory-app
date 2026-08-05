-- InventoryApp MariaDB Schema

CREATE DATABASE IF NOT EXISTS inventory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inventory_db;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category_id INT NULL,
    location_id INT NULL,
    quantity INT NOT NULL DEFAULT 0,
    min_stock INT NOT NULL DEFAULT 5,
    unit VARCHAR(50) DEFAULT 'pcs',
    unit_price DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL,
    INDEX idx_sku (sku),
    INDEX idx_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    type ENUM('in','out','adjustment') NOT NULL,
    quantity INT NOT NULL,
    previous_qty INT NOT NULL,
    new_qty INT NOT NULL,
    reason VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB;

-- Seed data
INSERT INTO categories (name, description) VALUES 
('Resistors', 'All resistor types'),
('Capacitors', 'Ceramic, electrolytic, etc'),
('ICs', 'Integrated circuits'),
('Connectors', 'Headers, terminals')
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO locations (name, description) VALUES
('Warehouse A - Shelf 1', 'Main storage'),
('Warehouse A - Shelf 2', 'Secondary'),
('Lab Bench', 'Active prototyping stock')
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO products (sku, name, quantity, min_stock, category_id, location_id, unit_price) VALUES
('RES-10K-001', '10K Ohm Resistor 0805', 500, 100, 1, 1, 0.02),
('CAP-100N-001', '100nF Ceramic Capacitor', 250, 50, 2, 1, 0.05),
('IC-ATMEGA-001', 'ATmega328P MCU', 25, 10, 3, 3, 3.50)
ON DUPLICATE KEY UPDATE name=VALUES(name);
