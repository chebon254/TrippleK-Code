-- Fix car_images: ensure first image per car is marked is_primary = 1
-- Safe to run multiple times (idempotent)

-- Step 1: Set is_primary=1 for the lowest-id image per car
UPDATE car_images ci
JOIN (
    SELECT MIN(id) AS first_id FROM car_images GROUP BY car_id
) first ON ci.id = first.first_id
SET ci.is_primary = 1
WHERE ci.is_primary = 0;

-- Step 2: Sync cars.thumbnail_path to match their primary image
UPDATE cars c
JOIN car_images ci ON ci.car_id = c.id AND ci.is_primary = 1
SET c.thumbnail_path = ci.file_path
WHERE c.thumbnail_path IS NULL OR c.thumbnail_path = '';
