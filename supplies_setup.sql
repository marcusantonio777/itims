-- Table for tracking items with quantity (Keyboard, Mouse, Batteries, etc.)
CREATE TABLE IF NOT EXISTS supplies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    category_id INT,
    brand VARCHAR(100),
    model VARCHAR(100),
    quantity INT DEFAULT 0,
    unit VARCHAR(20) DEFAULT 'pcs',
    min_stock_level INT DEFAULT 5,
    vendor_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (vendor_id) REFERENCES vendors(id)
);

-- Table for tracking stock movements (In/Out)
CREATE TABLE IF NOT EXISTS supply_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supply_id INT NOT NULL,
    staff_id INT NOT NULL,
    employee_id INT NULL, -- If issued to someone
    action_type ENUM('Stock In', 'Stock Out', 'Adjustment') NOT NULL,
    quantity_changed INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supply_id) REFERENCES supplies(id),
    FOREIGN KEY (staff_id) REFERENCES it_staff(id),
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);
