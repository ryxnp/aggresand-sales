-- ======================================
-- SCHEMA: aggresand_db
-- ======================================

CREATE DATABASE IF NOT EXISTS `aggresand_db`;
USE `aggresand_db`;

-- ===============================
-- TABLE: Admin
-- ===============================
CREATE TABLE IF NOT EXISTS Admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100),
    role ENUM('Admin','Supervisor','Encoder') DEFAULT 'Encoder',
    password VARCHAR(255) NOT NULL,
    last_login DATETIME,
    status ENUM('Active','Disabled') DEFAULT 'Active',
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_edited DATETIME ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NULL,
    edited_by INT NULL,
    FOREIGN KEY (created_by) REFERENCES Admin(admin_id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (edited_by) REFERENCES Admin(admin_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===============================
-- TABLE: Company
-- ===============================
CREATE TABLE IF NOT EXISTS Company (
    company_id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(100) NOT NULL,
    address TEXT,
    contact_no VARCHAR(20),
    email VARCHAR(100),
    status ENUM('Active','Inactive') DEFAULT 'Active',
    is_deleted BOOLEAN DEFAULT 0,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_edited DATETIME ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    edited_by INT,
    FOREIGN KEY (created_by) REFERENCES Admin(admin_id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (edited_by) REFERENCES Admin(admin_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===============================
-- TABLE: Contractor
-- ===============================
CREATE TABLE IF NOT EXISTS Contractor (
    contractor_id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    contact_no VARCHAR(20),
    email VARCHAR(100),
    status ENUM('Active','Inactive') DEFAULT 'Active',
    is_deleted BOOLEAN DEFAULT 0,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_edited DATETIME ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    edited_by INT,
    FOREIGN KEY (created_by) REFERENCES Admin(admin_id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (edited_by) REFERENCES Admin(admin_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===============================
-- TABLE: Site
-- ===============================
CREATE TABLE IF NOT EXISTS Site (
    site_id INT AUTO_INCREMENT PRIMARY KEY,
    site_name VARCHAR(100) NOT NULL,
    remarks VARCHAR(255),
    location TEXT,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    is_deleted BOOLEAN DEFAULT 0,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_edited DATETIME ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    edited_by INT,
    FOREIGN KEY (created_by) REFERENCES Admin(admin_id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (edited_by) REFERENCES Admin(admin_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===============================
-- TABLE: Customer
-- ===============================
CREATE TABLE IF NOT EXISTS Customer (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT,
    contractor_id INT,
    site_id INT,
    customer_name VARCHAR(100) NOT NULL,
    contact_no VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    is_deleted BOOLEAN DEFAULT 0,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_edited DATETIME ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    edited_by INT,
    FOREIGN KEY (company_id) REFERENCES Company(company_id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (contractor_id) REFERENCES Contractor(contractor_id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (site_id) REFERENCES Site(site_id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (created_by) REFERENCES Admin(admin_id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (edited_by) REFERENCES Admin(admin_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===============================
-- TABLE: Truck
-- ===============================
CREATE TABLE IF NOT EXISTS Truck (
    truck_id INT AUTO_INCREMENT PRIMARY KEY,
    plate_no VARCHAR(20) UNIQUE NOT NULL,
    capacity DECIMAL(10,2),
    truck_model VARCHAR(100),
    status ENUM('Active','Inactive','Under Maintenance') DEFAULT 'Active',
    is_deleted BOOLEAN DEFAULT 0,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_edited DATETIME ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    edited_by INT,
    FOREIGN KEY (created_by) REFERENCES Admin(admin_id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (edited_by) REFERENCES Admin(admin_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===============================
-- TABLE: Delivery
-- ===============================
CREATE TABLE IF NOT EXISTS Delivery (
    del_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    delivery_date DATE,
    dr_no VARCHAR(50),
    truck_id INT,
    billing_date DATE,
    material VARCHAR(100),
    quantity DECIMAL(10,2),
    unit_price DECIMAL(10,2),
    status ENUM('Pending','Delivered','Cancelled') DEFAULT 'Pending',
    is_deleted BOOLEAN DEFAULT 0,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_edited DATETIME ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    edited_by INT,
    FOREIGN KEY (customer_id) REFERENCES Customer(customer_id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (truck_id) REFERENCES Truck(truck_id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (created_by) REFERENCES Admin(admin_id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (edited_by) REFERENCES Admin(admin_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===============================
-- TABLE: Audit_Log
-- ===============================
CREATE TABLE IF NOT EXISTS Audit_Log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100),
    record_id INT,
    action ENUM('INSERT','UPDATE','DELETE'),
    old_data TEXT,
    new_data TEXT,
    performed_by INT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (performed_by) REFERENCES Admin(admin_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===============================
-- VIEWS: Non-deleted records (optional but recommended)
-- ===============================
CREATE OR REPLACE VIEW active_delivery AS
SELECT * FROM Delivery WHERE is_deleted = 0;

CREATE OR REPLACE VIEW active_company AS
SELECT * FROM Company WHERE is_deleted = 0;

CREATE OR REPLACE VIEW active_customer AS
SELECT * FROM Customer WHERE is_deleted = 0;

CREATE OR REPLACE VIEW active_truck AS
SELECT * FROM Truck WHERE is_deleted = 0;
