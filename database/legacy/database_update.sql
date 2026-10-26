-- Add maintenance_logs table for tracking repairs/services
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

-- Add audit_logs table for tracking system changes
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

-- Add password_change_requests for tracking password history
ALTER TABLE it_staff ADD COLUMN last_password_change TIMESTAMP NULL;
