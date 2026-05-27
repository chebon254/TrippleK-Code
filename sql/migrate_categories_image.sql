-- Add image_path column to car_categories (safe: no-op if already exists)
ALTER TABLE car_categories
  ADD COLUMN IF NOT EXISTS image_path VARCHAR(255) NULL DEFAULT NULL;

-- Create uploads/categories directory placeholder (handled by PHP mkdir)
SELECT 'Migration complete: car_categories.image_path added' AS status;
