-- OneClick Insurance Bill Generator Database
-- Created: September 2025

DROP DATABASE IF EXISTS oneclick_bills;
CREATE DATABASE oneclick_bills CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE oneclick_bills;

-- Partners Table
CREATE TABLE partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_name VARCHAR(255) NOT NULL,
    company_address TEXT NOT NULL,
    gstin VARCHAR(20) NOT NULL,
    pan VARCHAR(12) NOT NULL,
    state_code VARCHAR(5) NOT NULL,
    place_of_supply VARCHAR(100) NOT NULL,
    logo VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_state_code (state_code)
);

-- Bills Table
CREATE TABLE bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT NOT NULL,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    invoice_date DATE NOT NULL,
    description TEXT NOT NULL,
    commission_amount DECIMAL(12,2) NOT NULL,
    cgst_rate DECIMAL(5,2) DEFAULT 9.00,
    sgst_rate DECIMAL(5,2) DEFAULT 9.00,
    igst_rate DECIMAL(5,2) DEFAULT 18.00,
    cgst_amount DECIMAL(12,2) DEFAULT 0.00,
    sgst_amount DECIMAL(12,2) DEFAULT 0.00,
    igst_amount DECIMAL(12,2) DEFAULT 0.00,
    total_gst DECIMAL(12,2) NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    amount_in_words VARCHAR(500) NOT NULL,
    pdf_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE,
    INDEX idx_partner_id (partner_id),
    INDEX idx_invoice_date (invoice_date),
    INDEX idx_invoice_number (invoice_number)
);

-- Admin Users Table
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert Default Admin (password: password)
INSERT INTO admin_users (username, password, name, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@oneclickinsurer.com');

-- Insert Sample Partners (Based on your bill samples)
INSERT INTO partners (partner_name, company_address, gstin, pan, state_code, place_of_supply) VALUES
('STAR UNION DAI-ICHI LIFE INSURANCE COMPANY LTD.', '1ST FLOOR 103-105 MARCANTILE HOUSE KASTURBA GANDHI MARG NEW DELHI 110001', '07AALCS3949Q1ZE', 'AALCS3949Q', '07', 'DELHI'),

('PNB MET LIFE INDIA INSURANCE COMPANY LTD.', 'UNIT NO 302 3RD FLOOR TOWER 3 WORLDMARK MAIDAWAS SECTOR -65 GURUGRAM HARYANA 122018', '06AACCM6448H1ZE', 'AACCM6448H', '06', 'GURUGRAM'),

('Bandhan Life Insurance Limited', 'A201 2nd Floor Leela Business Park Andheri Kurla Road Andheri East Mumbai Maharashtra 400059', '27AAGCA3203J1ZY', 'AAGCA3203J', '27', 'MUMBAI'),

('Reliance General Insurance Company Ltd.', 'RELIANCE CENTRE, SOUTH WING, 4TH FLOOR, OFF WESTERN EXPRESS HIGHWAY, SANTACRUZ EAST, MUMBAI 400055', '27AAACU1596F1Z2', 'AAACU1596F', '27', 'MUMBAI'),

('Future Generali India Insurance Company Ltd.', 'INDIABULLS FINANCE CENTRE, TOWER 3, 6TH FLOOR, SENAPATI BAPAT MARG, ELPHINSTONE ROAD, MUMBAI 400013', '27AABCF2924M1ZE', 'AABCF2924M', '27', 'MUMBAI'),

('Care Health Insurance Limited', 'UNIT NO.5001, 5TH FLOOR, OBEROI COMMERZ, INTERNATIONAL BUSINESS PARK, OBEROI GARDEN CITY, OFF WESTERN EXPRESS HIGHWAY, GOREGAON EAST, MUMBAI 400063', '27AABCC6131Q1ZP', 'AABCC6131Q', '27', 'MUMBAI');

-- Create Views for Reports
CREATE VIEW monthly_revenue AS
SELECT 
    YEAR(invoice_date) as year,
    MONTH(invoice_date) as month,
    COUNT(*) as total_bills,
    SUM(commission_amount) as total_commission,
    SUM(total_gst) as total_gst,
    SUM(total_amount) as total_revenue
FROM bills 
GROUP BY YEAR(invoice_date), MONTH(invoice_date)
ORDER BY year DESC, month DESC;

CREATE VIEW partner_wise_revenue AS
SELECT 
    p.partner_name,
    COUNT(b.id) as total_bills,
    SUM(b.commission_amount) as total_commission,
    SUM(b.total_amount) as total_revenue,
    MAX(b.invoice_date) as last_bill_date
FROM partners p
LEFT JOIN bills b ON p.id = b.partner_id
GROUP BY p.id, p.partner_name
ORDER BY total_revenue DESC;

-- Indexes for better performance
CREATE INDEX idx_bills_date_range ON bills(invoice_date, total_amount);
CREATE INDEX idx_partners_active ON partners(status, partner_name);

-- Sample Data for Testing
INSERT INTO bills (partner_id, invoice_number, invoice_date, description, commission_amount, cgst_amount, sgst_amount, igst_amount, total_gst, total_amount, amount_in_words) VALUES
(1, 'OCI202409001', '2024-09-01', 'Insurance Commission for September 2024', 10000.00, 900.00, 900.00, 0.00, 1800.00, 11800.00, 'Eleven thousand eight hundred rupees only'),
(2, 'OCI202409002', '2024-09-02', 'Insurance Commission for Term Life Policy', 15000.00, 0.00, 0.00, 2700.00, 2700.00, 17700.00, 'Seventeen thousand seven hundred rupees only'),
(3, 'OCI202409003', '2024-09-03', 'Insurance Commission for Health Policy', 8000.00, 0.00, 0.00, 1440.00, 1440.00, 9440.00, 'Nine thousand four hundred forty rupees only');
