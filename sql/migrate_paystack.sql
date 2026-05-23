-- ============================================================
-- Migration: Replace M-Pesa / Flutterwave → Paystack
-- Run once against the live database.
-- Safe to re-run (uses INSERT IGNORE / IF NOT EXISTS guards).
-- ============================================================

-- 1. Widen the payment_method ENUM to cover Paystack channels
ALTER TABLE `payments`
  MODIFY COLUMN `payment_method`
    ENUM('card','mobile_money','bank_transfer','bank','cash')
    NOT NULL;

-- 2. Remove old gateway settings rows
DELETE FROM `settings`
WHERE `setting_key` IN (
  'mpesa_env','mpesa_shortcode','mpesa_consumer_key','mpesa_consumer_secret','mpesa_passkey',
  'flutterwave_env','flutterwave_public_key','flutterwave_secret_key','flutterwave_hash'
);

-- 3. Add Paystack settings (INSERT IGNORE = no-op if already added)
INSERT IGNORE INTO `settings` (`setting_key`, `setting_val`, `label`, `group_name`, `sort_order`) VALUES
  ('paystack_env',        'test',                                                    'Paystack Environment', 'payments', 1),
  ('paystack_public_key', 'pk_test_bb3228024e0509f44f75cf3355f69428966e645d',        'Paystack Public Key',  'payments', 2),
  ('paystack_secret_key', 'sk_test_08c50754a3e0326c362237dc1c39f68fb3803a7a',        'Paystack Secret Key',  'payments', 3);

-- If rows already exist (from a previous run), update the key values too
UPDATE `settings` SET `setting_val` = 'test'                                                    WHERE `setting_key` = 'paystack_env';
UPDATE `settings` SET `setting_val` = 'pk_test_bb3228024e0509f44f75cf3355f69428966e645d'        WHERE `setting_key` = 'paystack_public_key';
UPDATE `settings` SET `setting_val` = 'sk_test_08c50754a3e0326c362237dc1c39f68fb3803a7a'        WHERE `setting_key` = 'paystack_secret_key';
