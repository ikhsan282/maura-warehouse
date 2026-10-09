-- Maura Warehouse Database Schema
-- DB: db_maura_warehouse

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

CREATE DATABASE IF NOT EXISTS `db_maura_warehouse` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_maura_warehouse`;

-- Roles
CREATE TABLE `roles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Permissions
CREATE TABLE `permissions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` VARCHAR(255)
) ENGINE=InnoDB;

-- Role Permissions
CREATE TABLE `role_permissions` (
  `role_id` INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Users
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `role_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `verification_token` VARCHAR(100) NULL,
  `reset_token` VARCHAR(100) NULL,
  `reset_token_expires` TIMESTAMP NULL DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`)
) ENGINE=InnoDB;

-- Categories
CREATE TABLE `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Units
CREATE TABLE `units` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `abbreviation` VARCHAR(10) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Suppliers
CREATE TABLE `suppliers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `contact_person` VARCHAR(100),
  `phone` VARCHAR(20),
  `email` VARCHAR(150),
  `address` TEXT,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Locations (Rak/Bin)
CREATE TABLE `locations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(255),
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Items
CREATE TABLE `items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(30) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `unit_id` INT UNSIGNED NOT NULL,
  `min_stock` INT UNSIGNED DEFAULT 0,
  `buy_price` DECIMAL(15,2) DEFAULT 0,
  `sell_price` DECIMAL(15,2) DEFAULT 0,
  `description` TEXT,
  `image` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`),
  FOREIGN KEY (`unit_id`) REFERENCES `units`(`id`)
) ENGINE=InnoDB;

-- Stock (per location)
CREATE TABLE `stock` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `item_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NOT NULL,
  `quantity` INT DEFAULT 0,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `item_location` (`item_id`, `location_id`),
  FOREIGN KEY (`item_id`) REFERENCES `items`(`id`),
  FOREIGN KEY (`location_id`) REFERENCES `locations`(`id`)
) ENGINE=InnoDB;

-- Stock In
CREATE TABLE `stock_in` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `reference_no` VARCHAR(50) NOT NULL UNIQUE,
  `supplier_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `notes` TEXT,
  `transaction_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`),
  FOREIGN KEY (`location_id`) REFERENCES `locations`(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB;

-- Stock In Details
CREATE TABLE `stock_in_details` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `stock_in_id` INT UNSIGNED NOT NULL,
  `item_id` INT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL,
  `buy_price` DECIMAL(15,2) DEFAULT 0,
  FOREIGN KEY (`stock_in_id`) REFERENCES `stock_in`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `items`(`id`)
) ENGINE=InnoDB;

-- Stock Out
CREATE TABLE `stock_out` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `reference_no` VARCHAR(50) NOT NULL UNIQUE,
  `location_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `recipient` VARCHAR(150),
  `purpose` VARCHAR(255),
  `notes` TEXT,
  `transaction_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`location_id`) REFERENCES `locations`(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB;

-- Stock Out Details
CREATE TABLE `stock_out_details` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `stock_out_id` INT UNSIGNED NOT NULL,
  `item_id` INT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL,
  `sell_price` DECIMAL(15,2) DEFAULT 0,
  FOREIGN KEY (`stock_out_id`) REFERENCES `stock_out`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `items`(`id`)
) ENGINE=InnoDB;

-- Transfers
CREATE TABLE `transfers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `reference_no` VARCHAR(50) NOT NULL UNIQUE,
  `from_location_id` INT UNSIGNED NOT NULL,
  `to_location_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `notes` TEXT,
  `transaction_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`from_location_id`) REFERENCES `locations`(`id`),
  FOREIGN KEY (`to_location_id`) REFERENCES `locations`(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB;

-- Transfer Details
CREATE TABLE `transfer_details` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `transfer_id` INT UNSIGNED NOT NULL,
  `item_id` INT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL,
  FOREIGN KEY (`transfer_id`) REFERENCES `transfers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `items`(`id`)
) ENGINE=InnoDB;

-- Stock Adjustments
CREATE TABLE `stock_adjustments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `reference_no` VARCHAR(50) NOT NULL UNIQUE,
  `location_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `approved_by` INT UNSIGNED NULL,
  `approved_at` TIMESTAMP NULL,
  `status` ENUM('draft','approved') DEFAULT 'draft',
  `notes` TEXT,
  `transaction_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`location_id`) REFERENCES `locations`(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`),
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`)
) ENGINE=InnoDB;

-- Stock Adjustment Details
CREATE TABLE `stock_adjustment_details` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `adjustment_id` INT UNSIGNED NOT NULL,
  `item_id` INT UNSIGNED NOT NULL,
  `system_qty` INT NOT NULL,
  `physical_qty` INT NOT NULL,
  `difference` INT NOT NULL,
  `reason` VARCHAR(255),
  FOREIGN KEY (`adjustment_id`) REFERENCES `stock_adjustments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `items`(`id`)
) ENGINE=InnoDB;

-- Mutation Log (audit trail)
CREATE TABLE `mutations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `item_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NOT NULL,
  `type` ENUM('in','out','transfer_in','transfer_out','adjustment') NOT NULL,
  `quantity` INT NOT NULL,
  `reference_no` VARCHAR(50),
  `reference_type` ENUM('stock_in','stock_out','transfer','adjustment') NOT NULL,
  `reference_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`item_id`) REFERENCES `items`(`id`),
  FOREIGN KEY (`location_id`) REFERENCES `locations`(`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB;

-- =====================
-- SEED DATA
-- =====================

INSERT INTO `roles` (`name`, `description`) VALUES
('Super Admin', 'Akses penuh ke semua fitur'),
('Admin', 'Kelola data master dan transaksi'),
('Staff Gudang', 'Input barang masuk dan keluar'),
('Viewer', 'Hanya lihat data dan laporan');

INSERT INTO `permissions` (`name`, `description`) VALUES
('dashboard.view', 'Lihat dashboard'),
('users.view', 'Lihat daftar pengguna'),
('users.create', 'Tambah pengguna'),
('users.edit', 'Edit pengguna'),
('users.delete', 'Hapus pengguna'),
('roles.view', 'Lihat daftar peran'),
('roles.edit', 'Edit peran dan izin'),
('categories.view', 'Lihat kategori'),
('categories.create', 'Tambah kategori'),
('categories.edit', 'Edit kategori'),
('categories.delete', 'Hapus kategori'),
('units.view', 'Lihat satuan'),
('units.create', 'Tambah satuan'),
('units.edit', 'Edit satuan'),
('units.delete', 'Hapus satuan'),
('suppliers.view', 'Lihat supplier'),
('suppliers.create', 'Tambah supplier'),
('suppliers.edit', 'Edit supplier'),
('suppliers.delete', 'Hapus supplier'),
('locations.view', 'Lihat lokasi'),
('locations.create', 'Tambah lokasi'),
('locations.edit', 'Edit lokasi'),
('locations.delete', 'Hapus lokasi'),
('items.view', 'Lihat barang'),
('items.create', 'Tambah barang'),
('items.edit', 'Edit barang'),
('items.delete', 'Hapus barang'),
('stock_in.view', 'Lihat barang masuk'),
('stock_in.create', 'Input barang masuk'),
('stock_in.delete', 'Hapus barang masuk'),
('stock_out.view', 'Lihat barang keluar'),
('stock_out.create', 'Input barang keluar'),
('stock_out.delete', 'Hapus barang keluar'),
('transfers.view', 'Lihat transfer'),
('transfers.create', 'Input transfer'),
('transfers.delete', 'Hapus transfer'),
('adjustments.view', 'Lihat penyesuaian stok'),
('adjustments.create', 'Buat penyesuaian stok'),
('adjustments.approve', 'Approve penyesuaian stok'),
('stock.view', 'Lihat stok'),
('reports.view', 'Lihat laporan');

-- Super Admin: all permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`;

-- Admin: all except users.delete, roles.edit
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `permissions` WHERE `name` NOT IN ('users.delete','roles.edit');

-- Staff Gudang
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, id FROM `permissions` WHERE `name` IN (
  'dashboard.view','categories.view','units.view','suppliers.view','locations.view',
  'items.view','stock_in.view','stock_in.create','stock_out.view','stock_out.create',
  'transfers.view','transfers.create','adjustments.view','adjustments.create','stock.view','reports.view'
);

-- Viewer
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, id FROM `permissions` WHERE `name` IN (
  'dashboard.view','categories.view','units.view','suppliers.view','locations.view',
  'items.view','stock_in.view','stock_out.view','transfers.view','adjustments.view','stock.view','reports.view'
);

-- Default Super Admin user (password: Admin@123)
INSERT INTO `users` (`role_id`, `name`, `username`, `email`, `password`, `email_verified_at`, `is_active`)
VALUES (1, 'Super Administrator', 'superadmin', 'admin@maurawarehouse.com',
  '$2y$12$KBlsNPjTdH35lmxPkbhn..nl8LSF1UwPcHer.WsGRiEQkhKe8QY6G', -- password: Admin@123
  NOW(), 1);

-- Seed: categories
INSERT INTO `categories` (`code`, `name`) VALUES
('KAT-001', 'Elektronik'),
('KAT-002', 'Peralatan Kantor'),
('KAT-003', 'Bahan Baku'),
('KAT-004', 'Produk Jadi'),
('KAT-005', 'Perlengkapan Gudang');

-- Seed: units
INSERT INTO `units` (`name`, `abbreviation`) VALUES
('Pcs', 'pcs'),
('Karton', 'ktn'),
('Kilogram', 'kg'),
('Liter', 'ltr'),
('Meter', 'mtr'),
('Lusin', 'lsn'),
('Roll', 'rol');

-- ── Indexes ──────────────────────────────────────────────────────────────────
ALTER TABLE `mutations`
  ADD INDEX `idx_mutations_item_id`    (`item_id`),
  ADD INDEX `idx_mutations_location_id`(`location_id`),
  ADD INDEX `idx_mutations_type`       (`type`),
  ADD INDEX `idx_mutations_created_at` (`created_at`);

ALTER TABLE `stock_in`
  ADD INDEX `idx_stock_in_transaction_date` (`transaction_date`);

ALTER TABLE `stock_out`
  ADD INDEX `idx_stock_out_transaction_date` (`transaction_date`);

ALTER TABLE `items`
  ADD INDEX `idx_items_code` (`code`),
  ADD INDEX `idx_items_name` (`name`);

-- Seed: locations
INSERT INTO `locations` (`code`, `name`, `description`) VALUES
('RAK-A1', 'Rak A1', 'Rak utama bagian depan'),
('RAK-A2', 'Rak A2', 'Rak utama bagian tengah'),
('RAK-B1', 'Rak B1', 'Rak samping kiri'),
('RAK-B2', 'Rak B2', 'Rak samping kanan'),
('GUDANG-UTAMA', 'Gudang Utama', 'Area penyimpanan utama');
