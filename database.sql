-- =====================================================================
-- PaperGlow Billing System - Database Schema & Initial Data
-- MySQL 8.0+ Compatible
-- Target: paperglow_billing
-- Character Set: utf8mb4 | Collation: utf8mb4_unicode_ci
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `paperglow_billing` 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `paperglow_billing`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `invoice_items`;
DROP TABLE IF EXISTS `invoices`;
DROP TABLE IF EXISTS `quotation_items`;
DROP TABLE IF EXISTS `quotations`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `company_settings`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Table: users
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'manager', 'staff') NOT NULL DEFAULT 'admin',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: company_settings
-- ---------------------------------------------------------------------
CREATE TABLE `company_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `company_name` VARCHAR(150) NOT NULL,
  `logo` VARCHAR(255) NULL,
  `address` TEXT NULL,
  `phone` VARCHAR(50) NULL,
  `email` VARCHAR(150) NULL,
  `website` VARCHAR(150) NULL,
  `tax_number` VARCHAR(50) NULL,
  `currency` VARCHAR(10) NOT NULL DEFAULT 'KES',
  `bank_name` VARCHAR(100) NULL,
  `bank_account_name` VARCHAR(150) NULL,
  `bank_account_number` VARCHAR(50) NULL,
  `bank_routing` VARCHAR(50) NULL,
  `bank_swift` VARCHAR(50) NULL,
  `mobile_money_name` VARCHAR(100) NULL,
  `mobile_money_number` VARCHAR(50) NULL,
  `default_invoice_terms` TEXT NULL,
  `default_quotation_terms` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_company_user` (`user_id`),
  CONSTRAINT `fk_company_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: customers
-- ---------------------------------------------------------------------
CREATE TABLE `customers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `company` VARCHAR(150) NULL,
  `email` VARCHAR(150) NULL,
  `phone` VARCHAR(50) NULL,
  `address` TEXT NULL,
  `tax_number` VARCHAR(50) NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_customers_user` (`user_id`),
  KEY `idx_customers_name` (`name`),
  KEY `idx_customers_email` (`email`),
  CONSTRAINT `fk_customers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: quotations
-- ---------------------------------------------------------------------
CREATE TABLE `quotations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `quotation_number` VARCHAR(50) NOT NULL,
  `quotation_date` DATE NOT NULL,
  `expiry_date` DATE NOT NULL,
  `status` ENUM('Draft', 'Sent', 'Accepted', 'Rejected', 'Expired') NOT NULL DEFAULT 'Draft',
  `subtotal` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `discount_total` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `tax_total` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `grand_total` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `converted_invoice_id` INT UNSIGNED NULL,
  `notes` TEXT NULL,
  `terms` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_quotation_number` (`quotation_number`),
  KEY `idx_quotations_user` (`user_id`),
  KEY `idx_quotations_customer` (`customer_id`),
  KEY `idx_quotations_status` (`status`),
  KEY `idx_quotations_date` (`quotation_date`),
  CONSTRAINT `fk_quotations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_quotations_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: quotation_items
-- ---------------------------------------------------------------------
CREATE TABLE `quotation_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `quotation_id` INT UNSIGNED NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `quantity` DECIMAL(10, 2) NOT NULL DEFAULT 1.00,
  `unit_price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `tax_rate` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `line_total` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_qitems_quotation` (`quotation_id`),
  CONSTRAINT `fk_qitems_quotation` FOREIGN KEY (`quotation_id`) REFERENCES `quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: invoices
-- ---------------------------------------------------------------------
CREATE TABLE `invoices` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `from_quotation_id` INT UNSIGNED NULL,
  `invoice_number` VARCHAR(50) NOT NULL,
  `invoice_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `status` ENUM('Draft', 'Unpaid', 'Partially Paid', 'Paid', 'Overdue', 'Cancelled') NOT NULL DEFAULT 'Draft',
  `subtotal` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `discount_total` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `tax_total` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `grand_total` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `balance` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `notes` TEXT NULL,
  `terms` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_invoice_number` (`invoice_number`),
  KEY `idx_invoices_user` (`user_id`),
  KEY `idx_invoices_customer` (`customer_id`),
  KEY `idx_invoices_status` (`status`),
  KEY `idx_invoices_date` (`invoice_date`),
  KEY `idx_invoices_due` (`due_date`),
  CONSTRAINT `fk_invoices_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_invoices_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: invoice_items
-- ---------------------------------------------------------------------
CREATE TABLE `invoice_items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `invoice_id` INT UNSIGNED NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `quantity` DECIMAL(10, 2) NOT NULL DEFAULT 1.00,
  `unit_price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `tax_rate` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `line_total` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_iitems_invoice` (`invoice_id`),
  CONSTRAINT `fk_iitems_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: payments
-- ---------------------------------------------------------------------
CREATE TABLE `payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `invoice_id` INT UNSIGNED NOT NULL,
  `payment_date` DATE NOT NULL,
  `amount` DECIMAL(12, 2) NOT NULL,
  `payment_method` ENUM('Cash', 'Bank Transfer', 'Mobile Money', 'Card', 'Other') NOT NULL,
  `reference_number` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_payments_user` (`user_id`),
  KEY `idx_payments_invoice` (`invoice_id`),
  KEY `idx_payments_date` (`payment_date`),
  CONSTRAINT `fk_payments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- Seed Data: Sample Demo Dataset for PaperGlow Billing
-- Default Password for admin@paperglow.com is: Password123!
-- Generated via password_hash('Password123!', PASSWORD_BCRYPT)
-- =====================================================================

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`) 
VALUES (
  1, 
  'Elena Vance', 
  'admin@paperglow.com', 
  '$2y$10$wK6FqGZ9z1/r0Z27eS1nDuEaW0X99p5BfV0y7V4g6iLzK.mN12aWW', 
  'admin', 
  'active'
);

INSERT INTO `company_settings` (
  `id`, `user_id`, `company_name`, `logo`, `address`, `phone`, `email`, 
  `website`, `tax_number`, `currency`, `bank_name`, `bank_account_name`, 
  `bank_account_number`, `bank_routing`, `bank_swift`, `mobile_money_name`, 
  `mobile_money_number`, `default_invoice_terms`, `default_quotation_terms`
) VALUES (
  1,
  1,
  'PaperGlow Studio LLC',
  'uploads/logos/sample_logo.png',
  '452 Amberstone Way, Suite 300\nSeattle, WA 98101\nUnited States',
  '+1 (206) 555-0184',
  'billing@paperglowstudio.com',
  'https://paperglow.internal',
  'US-948271039',
  'KES',
  'Cascade Horizon Bank',
  'PaperGlow Studio LLC',
  '884920491029',
  '125000024',
  'CHBKUS33XXX',
  'PaperGlow Pay / M-Pesa',
  '+1 (206) 555-0199',
  'Payment due within 30 days of invoice date. Late payments accrue interest at 1.5% per month. Thank you for your partnership.',
  'Quotation valid for 30 calendar days from issue date. Estimates subject to final scope review.'
);

INSERT INTO `customers` (`id`, `user_id`, `name`, `company`, `email`, `phone`, `address`, `tax_number`, `notes`)
VALUES
  (1, 1, 'Marcus Thorne', 'Aura Architectures Inc', 'm.thorne@auraarch.com', '+1 (415) 555-2401', '788 Market Street, Suite 500, San Francisco, CA 94103', 'TAX-CA-9921', 'Preferred enterprise billing contact. Requires PO reference.'),
  (2, 1, 'Sophia Chen', 'Nebula Creative Lab', 'sophia@nebulacreative.io', '+1 (503) 555-9012', '1040 Pearl Blvd, Portland, OR 97201', 'TAX-OR-1823', 'Design and digital publishing agency.'),
  (3, 1, 'Devon Miller', 'Apex Logistics Group', 'dmiller@apexlogistics.net', '+1 (312) 555-8819', '200 Michigan Ave, Chicago, IL 60601', 'TAX-IL-7740', 'Quarterly retainer for logistics audit and design.');

-- Quotations
INSERT INTO `quotations` (`id`, `user_id`, `customer_id`, `quotation_number`, `quotation_date`, `expiry_date`, `status`, `subtotal`, `discount_total`, `tax_total`, `grand_total`, `converted_invoice_id`, `notes`, `terms`)
VALUES
  (1, 1, 1, 'QUO-2026-0001', '2026-09-01', '2026-10-01', 'Accepted', 4800.00, 200.00, 368.00, 4968.00, 1, 'Architecture brand identity suite and 3D architectural asset guidelines.', '30 days validity. 50% deposit required upon kick-off.'),
  (2, 1, 2, 'QUO-2026-0002', '2026-09-15', '2026-10-15', 'Sent', 2650.00, 0.00, 212.00, 2862.00, NULL, 'Brand guidelines collateral and interactive digital system overhaul.', 'Net 15 upon completion.'),
  (3, 1, 3, 'QUO-2026-0003', '2026-09-20', '2026-10-20', 'Draft', 1500.00, 50.00, 116.00, 1566.00, NULL, 'Quarterly reporting template development and visual compliance review.', 'Net 30 terms apply.');

INSERT INTO `quotation_items` (`id`, `quotation_id`, `description`, `quantity`, `unit_price`, `discount`, `tax_rate`, `tax_amount`, `line_total`, `sort_order`)
VALUES
  (1, 1, 'Master Brand Identity & System Guidelines', 1.00, 3200.00, 200.00, 8.00, 240.00, 3240.00, 1),
  (2, 1, '3D Spatial Environmental Renders (x4)', 4.00, 400.00, 0.00, 8.00, 128.00, 1728.00, 2),
  (3, 2, 'Interactive Design Token System', 1.00, 1850.00, 0.00, 8.00, 148.00, 1998.00, 1),
  (4, 2, 'Typography & Asset Packaging', 1.00, 800.00, 0.00, 8.00, 64.00, 864.00, 2),
  (5, 3, 'Quarterly Executive Reporting Deck System', 1.00, 1500.00, 50.00, 8.00, 116.00, 1566.00, 1);

-- Invoices
INSERT INTO `invoices` (`id`, `user_id`, `customer_id`, `from_quotation_id`, `invoice_number`, `invoice_date`, `due_date`, `status`, `subtotal`, `discount_total`, `tax_total`, `grand_total`, `paid_amount`, `balance`, `notes`, `terms`)
VALUES
  (1, 1, 1, 1, 'INV-2026-0001', '2026-09-02', '2026-10-02', 'Partially Paid', 4800.00, 200.00, 368.00, 4968.00, 2500.00, 2468.00, 'Converted from QUO-2026-0001. Initial deposit paid.', 'Net 30. Thank you for your business.'),
  (2, 1, 2, NULL, 'INV-2026-0002', '2026-08-10', '2026-09-10', 'Paid', 3100.00, 100.00, 240.00, 3240.00, 3240.00, 0.00, 'Design sprint deliverables and design token handoff.', 'Paid in full via Bank Transfer.'),
  (3, 1, 3, NULL, 'INV-2026-0003', '2026-08-01', '2026-08-31', 'Overdue', 1800.00, 0.00, 144.00, 1944.00, 0.00, 1944.00, 'Logistics audit reporting deck.', 'Overdue. Second notice sent on Sept 10.'),
  (4, 1, 1, NULL, 'INV-2026-0004', '2026-09-22', '2026-10-22', 'Unpaid', 1250.00, 0.00, 100.00, 1350.00, 0.00, 1350.00, 'Additional high-resolution print exports.', 'Payment due within 30 days.');

INSERT INTO `invoice_items` (`id`, `invoice_id`, `description`, `quantity`, `unit_price`, `discount`, `tax_rate`, `tax_amount`, `line_total`, `sort_order`)
VALUES
  (1, 1, 'Master Brand Identity & System Guidelines', 1.00, 3200.00, 200.00, 8.00, 240.00, 3240.00, 1),
  (2, 1, '3D Spatial Environmental Renders (x4)', 4.00, 400.00, 0.00, 8.00, 128.00, 1728.00, 2),
  (3, 2, 'Design Sprint Week 1 & 2 Execution', 1.00, 2400.00, 100.00, 8.00, 184.00, 2484.00, 1),
  (4, 2, 'Design System UI Kit in PaperGlow Palette', 1.00, 700.00, 0.00, 8.00, 56.00, 756.00, 2),
  (5, 3, 'Logistics Visual Audit & Data Blueprint', 1.00, 1800.00, 0.00, 8.00, 144.00, 1944.00, 1),
  (6, 4, 'Vector & Print Master Asset Pack', 5.00, 250.00, 0.00, 8.00, 100.00, 1350.00, 1);

-- Payments
INSERT INTO `payments` (`id`, `user_id`, `invoice_id`, `payment_date`, `amount`, `payment_method`, `reference_number`, `notes`)
VALUES
  (1, 1, 1, '2026-09-03', 2500.00, 'Bank Transfer', 'WIRE-AURA-89012', '50% Initial Project Milestone Deposit'),
  (2, 1, 2, '2026-08-12', 3240.00, 'Card', 'STR-CHG-994821', 'Full payment processed online via Card');
