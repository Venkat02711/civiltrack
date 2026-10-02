-- ========================================================
-- CivilTrack - Construction Site Management System
-- Database Schema & Comprehensive Seed Data
-- ========================================================

CREATE DATABASE IF NOT EXISTS `civiltrack` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `civiltrack`;

-- Disable foreign key checks during initialization
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables to ensure clean setup
DROP TABLE IF EXISTS `expenses`;
DROP TABLE IF EXISTS `materials`;
DROP TABLE IF EXISTS `payouts`;
DROP TABLE IF EXISTS `attendance`;
DROP TABLE IF EXISTS `workers`;
DROP TABLE IF EXISTS `sites`;
DROP TABLE IF EXISTS `users`;

-- Enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Table: users (System administrators & supervisors)
-- --------------------------------------------------------
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `role` VARCHAR(30) NOT NULL DEFAULT 'Admin',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: sites (Construction site projects)
-- --------------------------------------------------------
CREATE TABLE `sites` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `site_name` VARCHAR(150) NOT NULL,
  `client_name` VARCHAR(150) NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `start_date` DATE NOT NULL,
  `expected_end_date` DATE DEFAULT NULL,
  `status` ENUM('Active', 'Completed', 'On Hold') NOT NULL DEFAULT 'Active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: workers (Temporary / daily-wage workers)
-- --------------------------------------------------------
CREATE TABLE `workers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `address` TEXT DEFAULT NULL,
  `worker_type` VARCHAR(50) NOT NULL,
  `daily_wage` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: attendance (Daily worker site attendance)
-- --------------------------------------------------------
CREATE TABLE `attendance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `worker_id` INT NOT NULL,
  `site_id` INT NOT NULL,
  `work_date` DATE NOT NULL,
  `status` ENUM('Present', 'Half Day', 'Absent') NOT NULL DEFAULT 'Present',
  `calculated_wage` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `notes` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_att_worker` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_att_site` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `unique_worker_site_date` UNIQUE (`worker_id`, `site_id`, `work_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: payouts (Worker wage payouts generated from attendance)
-- --------------------------------------------------------
CREATE TABLE `payouts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `worker_id` INT NOT NULL,
  `site_id` INT NOT NULL,
  `attendance_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` ENUM('Pending', 'Paid') NOT NULL DEFAULT 'Pending',
  `payment_date` DATE DEFAULT NULL,
  `notes` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_payout_worker` FOREIGN KEY (`worker_id`) REFERENCES `workers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_payout_site` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_payout_att` FOREIGN KEY (`attendance_id`) REFERENCES `attendance` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: materials (Quick construction material purchases)
-- --------------------------------------------------------
CREATE TABLE `materials` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `site_id` INT NOT NULL,
  `material_name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(50) NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `unit` VARCHAR(30) NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `supplier` VARCHAR(150) DEFAULT NULL,
  `purchase_date` DATE NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_mat_site` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table: expenses (Other site expenses like machinery, transport)
-- --------------------------------------------------------
CREATE TABLE `expenses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `site_id` INT NOT NULL,
  `expense_type` ENUM('Transportation', 'Equipment', 'Labour Miscellaneous', 'Utilities', 'Other') NOT NULL DEFAULT 'Other',
  `description` TEXT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `expense_date` DATE NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_exp_site` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default Data Insertions

-- Default Admin User (Password is 'admin123')
INSERT INTO `users` (`username`, `password`, `full_name`, `role`) VALUES
('admin', '$2y$10$88mYh/roeoFUbXv/qeSxeu4fJZv6Tch6wLAtOITIQ683TbYc5KRY6', 'Site Supervisor (Admin)', 'Administrator');

-- Sample Sites
INSERT INTO `sites` (`id`, `site_name`, `client_name`, `location`, `start_date`, `expected_end_date`, `status`) VALUES
(1, 'Green Valley Residence', 'Dr. Arvind Swamy', 'Plot 42, Orchid Enclave, Whitefield', '2026-08-01', '2026-12-15', 'Active'),
(2, 'Metro Commercial Complex', 'Nexus Infrastructure Ltd', 'Sector 18, Ring Road, Tech Corridor', '2026-06-15', '2027-03-31', 'Active'),
(3, 'Lakeview Villa', 'Ramesh & Sunita Varma', 'Lakefront Boulevard, East Hills', '2026-07-10', '2026-11-30', 'Active'),
(4, 'Highway Overpass Support Pier', 'National Highway Division', 'NH-48 Junction, Km Marker 24', '2026-05-01', '2026-09-30', 'Completed');

-- Sample Workers
INSERT INTO `workers` (`id`, `name`, `phone`, `address`, `worker_type`, `daily_wage`, `status`) VALUES
(1, 'Ravi Kumar', '9876543210', 'Labour Colony, Block B, Room 12', 'Mason', 800.00, 'Active'),
(2, 'Suresh', '9876543211', 'Shanti Nagar, Cross 4', 'Helper', 650.00, 'Active'),
(3, 'Mani', '9876543212', 'Timber Market Quarters, Yard 2', 'Carpenter', 1000.00, 'Active'),
(4, 'Arun', '9876543213', 'Gandhi Street, No 15', 'Electrician', 900.00, 'Active'),
(5, 'Rajesh', '9876543214', 'Pipelines Road, Near Water Tank', 'Plumber', 850.00, 'Active'),
(6, 'Vijay', '9876543215', 'Old Town, Ward 7', 'Painter', 750.00, 'Active'),
(7, 'Ramesh', '9876543216', 'Railway Gate Slums, Hutment 88', 'General Worker', 650.00, 'Active'),
(8, 'Govind', '9876543217', 'New By-pass Basti, Plot 14', 'Mason', 800.00, 'Active');

-- Sample Attendance (Past 2 days and Today)
INSERT INTO `attendance` (`id`, `worker_id`, `site_id`, `work_date`, `status`, `calculated_wage`, `notes`) VALUES
-- 2 days ago (2026-09-30)
(1, 1, 1, '2026-09-30', 'Present', 800.00, 'Brick masonry first floor'),
(2, 2, 1, '2026-09-30', 'Present', 650.00, 'Mortar mixing & carrying'),
(3, 3, 1, '2026-09-30', 'Half Day', 500.00, 'Window frame shuttering half shift'),
(4, 4, 2, '2026-09-30', 'Present', 900.00, 'Conduit piping in basement'),
(5, 5, 2, '2026-09-30', 'Present', 850.00, 'Drainage pipe jointing'),

-- Yesterday (2026-10-01)
(6, 1, 1, '2026-10-01', 'Present', 800.00, 'Lintel beam masonry'),
(7, 2, 1, '2026-10-01', 'Present', 650.00, 'Material shifting to terrace'),
(8, 3, 3, '2026-10-01', 'Present', 1000.00, 'Roof truss woodwork'),
(9, 6, 3, '2026-10-01', 'Present', 750.00, 'Primer coat exterior'),
(10, 7, 2, '2026-10-01', 'Half Day', 325.00, 'Site clearing morning shift'),
(11, 8, 2, '2026-10-01', 'Absent', 0.00, 'Informed absence due to fever'),

-- Today (2026-10-02)
(12, 1, 1, '2026-10-02', 'Present', 800.00, 'Parapet wall construction'),
(13, 2, 1, '2026-10-02', 'Present', 650.00, 'Cement bag unloading & mixing'),
(14, 4, 2, '2026-10-02', 'Present', 900.00, 'Main switchboard wiring'),
(15, 5, 2, '2026-10-02', 'Half Day', 425.00, 'Soil pipe testing half day'),
(16, 8, 3, '2026-10-02', 'Present', 800.00, 'Boundary wall stone cladding');

-- Sample Payouts generated from attendance
INSERT INTO `payouts` (`id`, `worker_id`, `site_id`, `attendance_id`, `amount`, `payment_status`, `payment_date`, `notes`) VALUES
(1, 1, 1, 1, 800.00, 'Paid', '2026-09-30', 'Cash paid by supervisor'),
(2, 2, 1, 2, 650.00, 'Paid', '2026-09-30', 'Cash paid by supervisor'),
(3, 3, 1, 3, 500.00, 'Paid', '2026-09-30', 'Paid at site'),
(4, 4, 2, 4, 900.00, 'Paid', '2026-09-30', 'Paid'),
(5, 5, 2, 5, 850.00, 'Paid', '2026-09-30', 'Paid'),

(6, 1, 1, 6, 800.00, 'Paid', '2026-10-01', 'Cash settlement'),
(7, 2, 1, 7, 650.00, 'Pending', NULL, 'Awaiting weekly batch distribution'),
(8, 3, 3, 8, 1000.00, 'Paid', '2026-10-01', 'Paid on site'),
(9, 6, 3, 9, 750.00, 'Pending', NULL, 'To be paid tomorrow'),
(10, 7, 2, 10, 325.00, 'Pending', NULL, 'Half day pending approval'),
(11, 8, 2, 11, 0.00, 'Paid', '2026-10-01', 'Zero wage for absent'),

(12, 1, 1, 12, 800.00, 'Pending', NULL, 'Daily payout queued for evening'),
(13, 2, 1, 13, 650.00, 'Pending', NULL, 'Daily payout queued for evening'),
(14, 4, 2, 14, 900.00, 'Pending', NULL, 'Daily payout queued for evening'),
(15, 5, 2, 15, 425.00, 'Pending', NULL, 'Daily payout queued for evening'),
(16, 8, 3, 16, 800.00, 'Pending', NULL, 'Daily payout queued for evening');

-- Sample Material Purchases
INSERT INTO `materials` (`id`, `site_id`, `material_name`, `category`, `quantity`, `unit`, `unit_price`, `total_amount`, `supplier`, `purchase_date`, `notes`) VALUES
(1, 1, 'Ultratech 53-Grade Cement', 'Cement', 50.00, 'Bags', 420.00, 21000.00, 'Sri Lakshmi Building Materials', '2026-09-28', 'Fresh batch for first-floor slab'),
(2, 1, 'River Sand (Washed)', 'Sand', 2.00, 'Trucks', 8500.00, 17000.00, 'Cauvery Minerals Suppliers', '2026-09-29', 'Fine plastering sand'),
(3, 1, 'Standard Wirecut Red Bricks', 'Bricks', 3000.00, 'Pieces', 9.00, 27000.00, 'Bharath Brick Kilns', '2026-09-30', 'Delivered at site entrance'),
(4, 2, 'Tata Tiscon 12mm TMT Steel Rods', 'Steel', 2.00, 'Tons', 58000.00, 116000.00, 'Apex Steel & Alloys', '2026-09-25', 'For pillar column reinforcement'),
(5, 2, 'Finolex 4-inch PVC Sewage Pipes', 'Pipes', 25.00, 'Pieces', 360.00, 9000.00, 'Maruti Plumbing Mart', '2026-10-01', 'Heavy duty 6kg pressure pipes'),
(6, 2, 'Anchor 2.5 sq mm Copper Wires', 'Electrical', 8.00, 'Coils', 1450.00, 11600.00, 'ElectroWorld Distributors', '2026-10-02', 'Red and black rolls for conduits'),
(7, 3, 'Coromandel King Cement', 'Cement', 25.00, 'Bags', 415.00, 10375.00, 'City Cement Depot', '2026-10-01', 'For compound wall plastering'),
(8, 3, 'Asian Paints Exterior Primer', 'Other', 4.00, 'Buckets (20L)', 2850.00, 11400.00, 'Classic Paints & Hardware', '2026-10-02', 'Weather proof white base coat');

-- Sample Other Expenses
INSERT INTO `expenses` (`id`, `site_id`, `expense_type`, `description`, `amount`, `expense_date`) VALUES
(1, 1, 'Equipment', 'Mini concrete mixer rental for 3 days', 4500.00, '2026-09-29'),
(2, 1, 'Transportation', 'Tractor trolley freight for debris disposal', 1800.00, '2026-10-01'),
(3, 2, 'Equipment', 'JCB Excavator 8 hours excavation rent', 12000.00, '2026-09-26'),
(4, 2, 'Utilities', 'Temporary 3-phase electricity meter connection fee', 3500.00, '2026-09-27'),
(5, 2, 'Labour Miscellaneous', 'Water tanker supplies (3 loads @ 800)', 2400.00, '2026-10-01'),
(6, 3, 'Equipment', 'Scaffolding steel pipes rental (10 sets)', 3200.00, '2026-10-01'),
(7, 3, 'Labour Miscellaneous', 'Worker safety gear: 8 helmets and 6 boots', 2900.00, '2026-10-02');
