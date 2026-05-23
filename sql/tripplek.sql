-- ============================================================
-- Tripple K Car Rental System - Database Schema
-- Run this against your MySQL database to install the system.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── admin ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `admin` (
  `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(100)     NOT NULL,
  `email`         VARCHAR(191)     NOT NULL,
  `password_hash` VARCHAR(255)     NOT NULL,
  `created_at`    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin: email=admin@tripplek.co.ke  password=Admin@1234
-- Change password immediately after first login via admin/settings.php
INSERT IGNORE INTO `admin` (`name`, `email`, `password_hash`) VALUES
  ('Admin', 'admin@tripplek.co.ke', '$2y$12$iPB6Tc8MsJHqxpEkvxtbVuZBcqt0jsQmBcrxm1k1RkUIoi1jl9W/6');

-- ─── services ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `services` (
  `id`          TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`        VARCHAR(60)      NOT NULL,
  `name`        VARCHAR(100)     NOT NULL,
  `description` TEXT,
  `icon`        VARCHAR(100),
  `sort_order`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)       NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_service_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `services` (`slug`, `name`, `description`, `sort_order`) VALUES
  ('car-hire',          'Car Hire',           'Self-drive car rental by the day', 1),
  ('airport-transfer',  'Airport Transfer',   'Point-to-point airport pickup and drop-off', 2),
  ('chauffeur',         'Chauffeur Service',  'Professional driver for half-day or full-day', 3),
  ('wedding',           'Wedding Packages',   'Elegant vehicles for your special day', 4);

-- ─── car_categories ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `car_categories` (
  `id`          TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`        VARCHAR(60)      NOT NULL,
  `name`        VARCHAR(100)     NOT NULL,
  `description` TEXT,
  `sort_order`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cat_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `car_categories` (`slug`, `name`, `description`, `sort_order`) VALUES
  ('saloon',    'Saloon / Sedan',   'Toyota Axio, Fielder, Corolla — city & everyday use', 1),
  ('suv',       'SUV / 4x4',        'Toyota Prado, Land Cruiser — safaris & rough terrain', 2),
  ('executive', 'Executive',         'Mercedes S-Class, BMW 7 Series — VIP & weddings', 3),
  ('van',       'Van / Minibus',     'Toyota Hiace — group travel & airport transfers', 4);

-- ─── cars ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `cars` (
  `id`                  INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  `category_id`         TINYINT UNSIGNED  NOT NULL,
  `make`                VARCHAR(80)       NOT NULL,
  `model`               VARCHAR(80)       NOT NULL,
  `year`                SMALLINT UNSIGNED NOT NULL,
  `registration_number` VARCHAR(20)       NOT NULL,
  `color`               VARCHAR(50)       NOT NULL DEFAULT 'White',
  `transmission`        ENUM('automatic','manual') NOT NULL DEFAULT 'automatic',
  `fuel_type`           ENUM('petrol','diesel','hybrid','electric') NOT NULL DEFAULT 'petrol',
  `passenger_capacity`  TINYINT UNSIGNED  NOT NULL DEFAULT 5,
  `luggage_capacity`    TINYINT UNSIGNED  NOT NULL DEFAULT 2,
  `engine_cc`           SMALLINT UNSIGNED,
  `has_ac`              TINYINT(1)        NOT NULL DEFAULT 1,
  `has_wifi`            TINYINT(1)        NOT NULL DEFAULT 0,
  `has_gps`             TINYINT(1)        NOT NULL DEFAULT 0,
  `has_child_seat`      TINYINT(1)        NOT NULL DEFAULT 0,
  `price_per_day`       DECIMAL(10,2)     NOT NULL,
  `price_airport`       DECIMAL(10,2),
  `price_wedding`       DECIMAL(10,2),
  `status`              ENUM('available','hired','maintenance','inactive') NOT NULL DEFAULT 'available',
  `thumbnail_path`      VARCHAR(255),
  `description`         TEXT,
  `sort_order`          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `is_featured`         TINYINT(1)        NOT NULL DEFAULT 0,
  `created_at`          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_car_reg` (`registration_number`),
  KEY `idx_category`    (`category_id`),
  KEY `idx_status`      (`status`),
  KEY `idx_featured`    (`is_featured`),
  KEY `idx_price`       (`price_per_day`),
  KEY `idx_passengers`  (`passenger_capacity`),
  KEY `idx_transmission`(`transmission`),
  CONSTRAINT `fk_car_category` FOREIGN KEY (`category_id`) REFERENCES `car_categories` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── dummy cars ──────────────────────────────────────────────
INSERT IGNORE INTO `cars`
  (`category_id`,`make`,`model`,`year`,`registration_number`,`color`,`transmission`,`fuel_type`,`passenger_capacity`,`luggage_capacity`,`price_per_day`,`deposit_amount`,`has_ac`,`is_featured`,`status`,`description`)
VALUES
  -- Saloon
  ((SELECT id FROM car_categories WHERE slug='saloon'),'Toyota','Axio',2021,'KDA 123A','Silver','automatic','petrol',4,2,3500,15000,1,1,'available','Well-maintained Toyota Axio, ideal for business and city travel. Fuel-efficient and comfortable.'),
  ((SELECT id FROM car_categories WHERE slug='saloon'),'Toyota','Premio',2020,'KDB 456B','White','automatic','petrol',4,2,4000,15000,1,1,'available','Spacious and reliable Toyota Premio. Perfect for long-distance drives and corporate use.'),
  ((SELECT id FROM car_categories WHERE slug='saloon'),'Nissan','Tiida',2019,'KDC 789C','Blue','manual','petrol',4,2,3000,15000,1,0,'available','Affordable Nissan Tiida — great fuel economy, easy to drive in city traffic.'),
  ((SELECT id FROM car_categories WHERE slug='saloon'),'Toyota','Fielder',2022,'KDK 001K','White','automatic','petrol',5,3,3800,15000,1,0,'available','Reliable Toyota Fielder station wagon — roomy boot, smooth on long drives.'),
  -- SUV
  ((SELECT id FROM car_categories WHERE slug='suv'),'Toyota','Land Cruiser Prado',2022,'KDD 321D','Black','automatic','diesel',7,4,12000,50000,1,1,'available','Premium Land Cruiser Prado — the ultimate safari and off-road vehicle. 4WD, leather seats, sunroof.'),
  ((SELECT id FROM car_categories WHERE slug='suv'),'Toyota','RAV4',2021,'KDE 654E','White','automatic','petrol',5,3,7500,30000,1,1,'available','Versatile Toyota RAV4 — perfect for both city drives and weekend getaways.'),
  ((SELECT id FROM car_categories WHERE slug='suv'),'Mitsubishi','Pajero',2020,'KDF 987F','Grey','automatic','diesel',7,4,9000,40000,1,0,'available','Powerful Mitsubishi Pajero with full 4WD. Handles Kenya''s toughest roads with ease.'),
  ((SELECT id FROM car_categories WHERE slug='suv'),'Toyota','Fortuner',2022,'KDL 002L','Black','automatic','diesel',7,4,10000,45000,1,1,'available','Rugged Toyota Fortuner 4x4 — popular for safaris and upcountry travel.'),
  -- Executive
  ((SELECT id FROM car_categories WHERE slug='executive'),'Mercedes-Benz','E-Class',2022,'KDG 111G','Black','automatic','petrol',4,2,18000,50000,1,1,'available','Luxurious Mercedes-Benz E-Class — the perfect vehicle for corporate executives and VIP guests.'),
  ((SELECT id FROM car_categories WHERE slug='executive'),'BMW','5 Series',2021,'KDH 222H','White','automatic','petrol',4,2,16000,50000,1,0,'available','Elegant BMW 5 Series with premium interior. Ideal for business meetings and special occasions.'),
  -- Van
  ((SELECT id FROM car_categories WHERE slug='van'),'Toyota','Hiace',2021,'KDI 333I','White','manual','diesel',14,6,8000,30000,1,1,'available','Spacious Toyota Hiace — perfect for group airport transfers, tours, and corporate shuttles.'),
  ((SELECT id FROM car_categories WHERE slug='van'),'Toyota','Noah',2020,'KDJ 444J','Silver','automatic','petrol',8,3,6000,25000,1,0,'available','Comfortable Toyota Noah minivan — ideal for family trips and small group transfers.');

-- ─── car_images ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `car_images` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `car_id`     INT UNSIGNED NOT NULL,
  `file_path`  VARCHAR(255) NOT NULL,
  `alt_text`   VARCHAR(255),
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `is_primary` TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_car_images_car` (`car_id`),
  CONSTRAINT `fk_image_car` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── car_features ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `car_features` (
  `id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `car_id`  INT UNSIGNED NOT NULL,
  `feature` VARCHAR(100) NOT NULL,
  `value`   VARCHAR(100),
  PRIMARY KEY (`id`),
  KEY `idx_car_features` (`car_id`),
  CONSTRAINT `fk_feature_car` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── customers ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `customers` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name`  VARCHAR(150) NOT NULL,
  `email`      VARCHAR(191) NOT NULL,
  `phone`      VARCHAR(20)  NOT NULL,
  `id_number`  VARCHAR(30),
  `address`    VARCHAR(255),
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cust_email` (`email`),
  KEY `idx_cust_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── bookings ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `bookings` (
  `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `booking_ref`      VARCHAR(20)      NOT NULL,
  `car_id`           INT UNSIGNED     NOT NULL,
  `customer_id`      INT UNSIGNED     NOT NULL,
  `service_id`       TINYINT UNSIGNED NOT NULL,
  `pickup_location`  VARCHAR(255)     NOT NULL,
  `dropoff_location` VARCHAR(255),
  `pickup_date`      DATE             NOT NULL,
  `pickup_time`      TIME             NOT NULL DEFAULT '08:00:00',
  `return_date`      DATE,
  `return_time`      TIME,
  `num_days`         TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `num_passengers`   TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `flight_number`    VARCHAR(20),
  `special_requests` TEXT,
  `base_amount`      DECIMAL(10,2)    NOT NULL,
  `extras_amount`    DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
  `discount_amount`  DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
  `total_amount`     DECIMAL(10,2)    NOT NULL,
  `status`           ENUM('pending','confirmed','active','completed','cancelled') NOT NULL DEFAULT 'pending',
  `admin_notes`      TEXT,
  `created_at`       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_booking_ref` (`booking_ref`),
  KEY `idx_booking_car`       (`car_id`),
  KEY `idx_booking_customer`  (`customer_id`),
  KEY `idx_booking_status`    (`status`),
  KEY `idx_booking_date`      (`pickup_date`),
  KEY `idx_availability`      (`car_id`, `pickup_date`, `return_date`, `status`),
  CONSTRAINT `fk_booking_car`      FOREIGN KEY (`car_id`)      REFERENCES `cars`      (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_booking_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_booking_service`  FOREIGN KEY (`service_id`)  REFERENCES `services`  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── payments ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `payments` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `booking_id`       INT UNSIGNED  NOT NULL,
  `payment_method`   ENUM('card','mobile_money','bank_transfer','bank','cash') NOT NULL,
  `transaction_ref`  VARCHAR(100),
  `gateway_ref`      VARCHAR(100),
  `phone_number`     VARCHAR(20),
  `amount`           DECIMAL(10,2) NOT NULL,
  `currency`         CHAR(3)       NOT NULL DEFAULT 'KES',
  `status`           ENUM('pending','processing','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `gateway_response` JSON,
  `paid_at`          TIMESTAMP     NULL,
  `created_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pay_booking` (`booking_id`),
  KEY `idx_pay_method`  (`payment_method`),
  KEY `idx_pay_status`  (`status`),
  KEY `idx_pay_tx_ref`  (`transaction_ref`),
  CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── settings ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `settings` (
  `id`          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100)      NOT NULL,
  `setting_val` TEXT,
  `label`       VARCHAR(150),
  `group_name`  VARCHAR(60)       NOT NULL DEFAULT 'general',
  `sort_order`  TINYINT UNSIGNED  NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_setting_key` (`setting_key`),
  KEY `idx_setting_group` (`group_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `settings` (`setting_key`, `setting_val`, `label`, `group_name`, `sort_order`) VALUES
  -- General
  ('company_name',           'Tripple K Car Hire & Transfers', 'Company Name',           'general', 1),
  ('company_tagline',        'Your road, our wheels',          'Tagline',                'general', 2),
  ('company_phone',          '+254 700 000 000',               'Primary Phone',          'general', 3),
  ('company_whatsapp',       '+254700000000',                  'WhatsApp Number',        'general', 4),
  ('company_email',          'info@tripplek.co.ke',            'Email Address',          'general', 5),
  ('company_address',        'Nairobi, Kenya',                 'Physical Address',       'general', 6),
  ('company_hours',          'Mon–Sat 7am–8pm, Sun 8am–6pm',  'Business Hours',         'general', 7),
  ('company_website',        '',                               'Website URL',            'general', 8),
  ('maps_embed_url',         'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d63820.78675869162!2d36.798130034814456!3d-1.2950571305451974!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x182f1172d84d49a7%3A0xf7cf0254b297924c!2sNairobi!5e0!3m2!1sen!2ske!4v1778769785399!5m2!1sen!2ske', 'Google Maps Embed URL', 'general', 9),
  -- Legal
  ('kra_pin',                '',                               'KRA PIN',                'legal', 1),
  ('tra_license',            '',                               'TRA License Number',     'legal', 2),
  ('security_deposit_min',   '15000',                          'Min Security Deposit (KES)', 'legal', 3),
  ('security_deposit_max',   '50000',                          'Max Security Deposit (KES)', 'legal', 4),
  -- Payments (Paystack)
  ('paystack_env',           'test',                           'Paystack Environment',   'payments', 1),
  ('paystack_public_key',    '',                               'Paystack Public Key',    'payments', 2),
  ('paystack_secret_key',    '',                               'Paystack Secret Key',    'payments', 3),
  -- Invoices
  ('invoice_prefix',         'TK',                             'Invoice Prefix',         'invoices', 1),
  ('invoice_footer',         'Thank you for choosing Tripple K!', 'Invoice Footer Text', 'invoices', 2),
  ('invoice_bank_name',      '',                               'Bank Name',              'invoices', 3),
  ('invoice_bank_account',   '',                               'Bank Account Number',    'invoices', 4),
  ('invoice_bank_branch',    '',                               'Bank Branch',            'invoices', 5);

SET FOREIGN_KEY_CHECKS = 1;
