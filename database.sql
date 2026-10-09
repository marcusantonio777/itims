CREATE DATABASE IF NOT EXISTS itims_db;
USE itims_db;

CREATE TABLE IF NOT EXISTS it_staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Tech') DEFAULT 'Tech',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_password_change TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    prefix VARCHAR(10) NOT NULL,
    requires_serial_number TINYINT(1) NOT NULL DEFAULT 1,
    show_technical_specs TINYINT(1) NOT NULL DEFAULT 1,
    show_other_specs TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE IF NOT EXISTS vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    support_email VARCHAR(100),
    hotline VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    department VARCHAR(100),
    position VARCHAR(100),
    employee_id VARCHAR(50) UNIQUE NOT NULL,
    username VARCHAR(50) -- For default login logic
);

CREATE TABLE IF NOT EXISTS assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_tag VARCHAR(50) UNIQUE NOT NULL,
    category_id INT,
    brand VARCHAR(100),
    model VARCHAR(100),
    serial_number VARCHAR(100) UNIQUE,
    processor VARCHAR(100),
    ram VARCHAR(50),
    storage VARCHAR(50),
    other_specs TEXT,
    vendor_id INT,
    purchase_date DATE,
    warranty_expiry DATE,
    next_service_date DATE,
    status ENUM('Available', 'Deployed', 'Repair', 'Retired') DEFAULT 'Available',
    photo_path VARCHAR(255),
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (vendor_id) REFERENCES vendors(id)
);

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

CREATE TABLE IF NOT EXISTS supply_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supply_id INT NOT NULL,
    staff_id INT NOT NULL,
    employee_id INT NULL,
    action_type ENUM('Stock In', 'Stock Out', 'Adjustment') NOT NULL,
    quantity_changed INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supply_id) REFERENCES supplies(id),
    FOREIGN KEY (staff_id) REFERENCES it_staff(id),
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

CREATE TABLE IF NOT EXISTS accountability_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT,
    user_id INT, -- Refers to employees.id
    it_staff_id INT,
    date_issued TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    keycard_level VARCHAR(80) NULL,
    keycard_access VARCHAR(120) NULL,
    keycard_issued_on DATE NULL,
    keycard_expires_on DATE NULL,
    return_date DATE,
    return_condition ENUM('Good', 'Needs Repair', 'Damaged') NULL,
    return_notes TEXT NULL,
    status ENUM('Active', 'Cleared') DEFAULT 'Active',
    signature_path VARCHAR(255),
    scan_file_path VARCHAR(255),
    FOREIGN KEY (asset_id) REFERENCES assets(id),
    FOREIGN KEY (user_id) REFERENCES employees(id),
    FOREIGN KEY (it_staff_id) REFERENCES it_staff(id)
);

CREATE TABLE IF NOT EXISTS accountability_log_employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_id INT NOT NULL,
    employee_id INT NOT NULL,
    UNIQUE KEY uniq_log_employee (log_id, employee_id),
    KEY fk_ale_employee (employee_id),
    FOREIGN KEY (log_id) REFERENCES accountability_logs(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

CREATE TABLE IF NOT EXISTS accountability_supplies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_id INT NOT NULL,
    supply_id INT NOT NULL,
    quantity INT DEFAULT 1,
    FOREIGN KEY (log_id) REFERENCES accountability_logs(id) ON DELETE CASCADE,
    FOREIGN KEY (supply_id) REFERENCES supplies(id)
);

CREATE TABLE IF NOT EXISTS maintenance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    it_staff_id INT,
    maintenance_type ENUM('Repair', 'Service', 'Inspection', 'Upgrade') NOT NULL,
    description TEXT,
    maintenance_date DATE NOT NULL,
    cost DECIMAL(10,2),
    vendor_id INT,
    next_maintenance_date DATE,
    status ENUM('Pending', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id),
    FOREIGN KEY (it_staff_id) REFERENCES it_staff(id),
    FOREIGN KEY (vendor_id) REFERENCES vendors(id)
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action_type ENUM('Create', 'Update', 'Delete', 'Login', 'Logout', 'Issue', 'Return') NOT NULL,
    table_affected VARCHAR(50),
    record_id INT,
    old_value TEXT,
    new_value TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES it_staff(id)
);

CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES it_staff(id) ON DELETE SET NULL
);

INSERT INTO system_settings (setting_key, setting_value)
VALUES ('allow_delete_assets_with_history', '0')
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);

CREATE TABLE IF NOT EXISTS asset_disposal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    disposal_date DATE NOT NULL,
    reason VARCHAR(255) NOT NULL,
    method ENUM('Scrap', 'Sold', 'Donated', 'Destroyed') NOT NULL,
    notes TEXT,
    staff_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES it_staff(id)
);

CREATE TABLE IF NOT EXISTS room_deployments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    target_type ENUM('Room', 'Employee', 'Other') NOT NULL DEFAULT 'Room',
    room_number VARCHAR(50) NULL,
    employee_id INT NULL,
    target_label VARCHAR(255) NULL,
    notes TEXT,
    deployed_by INT NOT NULL,
    deployed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (deployed_by) REFERENCES it_staff(id)
);

CREATE TABLE IF NOT EXISTS room_deployment_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deployment_id INT NOT NULL,
    supply_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (deployment_id) REFERENCES room_deployments(id) ON DELETE CASCADE,
    FOREIGN KEY (supply_id) REFERENCES supplies(id)
);

CREATE TABLE IF NOT EXISTS software_licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    software_name VARCHAR(255) NOT NULL,
    vendor_name VARCHAR(255) DEFAULT NULL,
    license_key VARCHAR(255) DEFAULT NULL,
    license_type VARCHAR(100) DEFAULT NULL,
    seats INT NOT NULL DEFAULT 1,
    cost DECIMAL(10,2) DEFAULT 0.00,
    billing_cycle ENUM('Monthly', 'Quarterly', 'Semi-Annual', 'Annual', 'One-Time', 'Custom') NOT NULL DEFAULT 'Annual',
    start_date DATE DEFAULT NULL,
    renewal_date DATE DEFAULT NULL,
    update_due_date DATE DEFAULT NULL,
    status ENUM('Active', 'Expiring Soon', 'Expired', 'Cancelled') NOT NULL DEFAULT 'Active',
    owner_name VARCHAR(255) DEFAULT NULL,
    notes TEXT,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES it_staff(id)
);

-- Insert default admin (password: admin123)
INSERT INTO it_staff (full_name, username, password_hash, role, is_active)
VALUES ('System Admin', 'admin', '$2y$10$jyQgummwO7cPLI9MBoDPEe5/MA0duyaekYpK90RnDz08uo.9GDw7q', 'admin', 1)
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    role = VALUES(role),
    is_active = VALUES(is_active),
    password_hash = CASE
        WHEN password_hash = '' OR password_hash IS NULL THEN VALUES(password_hash)
        ELSE password_hash
    END;

-- Insert some categories
INSERT INTO categories (category_name, prefix) VALUES ('Laptop', 'LPT'), ('Keycard', 'KCD'), ('Phone', 'PHN'), ('Desktop', 'DSK'), ('Monitor', 'MON');
