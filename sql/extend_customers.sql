-- Extend customers table with full KYC fields
-- MySQL 8.0 compatible (no ADD COLUMN IF NOT EXISTS — that's MariaDB only)
-- Safe to re-run: each ALTER is wrapped in a procedure that checks INFORMATION_SCHEMA.

DROP PROCEDURE IF EXISTS _add_col;
DELIMITER $$

CREATE PROCEDURE _add_col(
    tbl VARCHAR(64),
    col VARCHAR(64),
    def TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = tbl
          AND COLUMN_NAME  = col
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN `', col, '` ', def);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- Run all additions
CALL _add_col('customers', 'customer_type',               "ENUM('individual','organization') NOT NULL DEFAULT 'individual' AFTER `id`");
CALL _add_col('customers', 'first_name',                  "VARCHAR(100) NULL AFTER `customer_type`");
CALL _add_col('customers', 'last_name',                   "VARCHAR(100) NULL AFTER `first_name`");
CALL _add_col('customers', 'date_of_birth',               "DATE NULL AFTER `last_name`");
CALL _add_col('customers', 'driver_license_front',        "VARCHAR(255) NULL AFTER `date_of_birth`");
CALL _add_col('customers', 'driver_license_number',       "VARCHAR(100) NULL AFTER `driver_license_front`");
CALL _add_col('customers', 'driver_license_expiry',       "DATE NULL AFTER `driver_license_number`");
CALL _add_col('customers', 'id_type',                     "VARCHAR(50) NULL AFTER `driver_license_expiry`");
CALL _add_col('customers', 'id_front',                    "VARCHAR(255) NULL AFTER `id_type`");
CALL _add_col('customers', 'photo',                       "VARCHAR(255) NULL AFTER `id_front`");
CALL _add_col('customers', 'residential_address',         "TEXT NULL AFTER `photo`");
CALL _add_col('customers', 'work_address',                "TEXT NULL AFTER `residential_address`");
CALL _add_col('customers', 'kra_pin',                     "VARCHAR(50) NULL AFTER `work_address`");
CALL _add_col('customers', 'corporate_name',              "VARCHAR(200) NULL AFTER `kra_pin`");
CALL _add_col('customers', 'contact_first_name',          "VARCHAR(100) NULL AFTER `corporate_name`");
CALL _add_col('customers', 'contact_last_name',           "VARCHAR(100) NULL AFTER `contact_first_name`");
CALL _add_col('customers', 'org_email',                   "VARCHAR(200) NULL AFTER `contact_last_name`");
CALL _add_col('customers', 'org_phone',                   "VARCHAR(30) NULL AFTER `org_email`");
CALL _add_col('customers', 'org_kra_pin',                 "VARCHAR(50) NULL AFTER `org_phone`");
CALL _add_col('customers', 'org_address',                 "TEXT NULL AFTER `org_kra_pin`");
CALL _add_col('customers', 'certificate_of_incorporation',"VARCHAR(255) NULL AFTER `org_address`");
CALL _add_col('customers', 'org_logo',                    "VARCHAR(255) NULL AFTER `certificate_of_incorporation`");
CALL _add_col('customers', 'status',                      "ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER `org_logo`");
CALL _add_col('customers', 'updated_at',                  "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`");

DROP PROCEDURE IF EXISTS _add_col;

-- Backfill first_name / last_name from full_name for existing rows
UPDATE customers
SET first_name = SUBSTRING_INDEX(full_name, ' ', 1),
    last_name  = IF(LOCATE(' ', full_name) > 0, SUBSTRING(full_name, LOCATE(' ', full_name) + 1), '')
WHERE first_name IS NULL AND full_name IS NOT NULL AND full_name != '';
