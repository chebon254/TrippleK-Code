<?php
require_once __DIR__ . '/config.php';

function upload_car_image(int $car_id, array $file): array {
    $car_dir = UPLOAD_DIR . "cars/{$car_id}/";
    if (!is_dir($car_dir)) {
        mkdir($car_dir, 0755, true);
    }

    $finfo   = new finfo(FILEINFO_MIME_TYPE);
    $mime    = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($mime, $allowed, true)) {
        throw new RuntimeException('Invalid image type. Only JPG, PNG, WebP allowed.');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Image too large. Maximum 5MB.');
    }

    $ext      = match ($mime) {
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => 'jpg',
    };
    $filename  = uniqid('img_', true) . '.' . $ext;
    $dest_path = $car_dir . $filename;
    $thumb_name = 'thumb_' . $filename;
    $thumb_path = $car_dir . $thumb_name;

    if (!move_uploaded_file($file['tmp_name'], $dest_path)) {
        throw new RuntimeException('Failed to save image.');
    }

    create_thumbnail($dest_path, $thumb_path, 400, 300);

    return [
        'path'  => "cars/{$car_id}/{$filename}",
        'thumb' => "cars/{$car_id}/{$thumb_name}",
    ];
}

function create_thumbnail(string $src, string $dest, int $width, int $height): void {
    [$orig_w, $orig_h, $type] = getimagesize($src);

    $src_img = match ($type) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($src),
        IMAGETYPE_PNG  => imagecreatefrompng($src),
        IMAGETYPE_WEBP => imagecreatefromwebp($src),
        default        => throw new RuntimeException('Unsupported image type for thumbnail.'),
    };

    // Crop-resize to exact dimensions
    $src_ratio  = $orig_w / $orig_h;
    $dest_ratio = $width / $height;

    if ($src_ratio > $dest_ratio) {
        $crop_h = $orig_h;
        $crop_w = (int)($orig_h * $dest_ratio);
        $crop_x = (int)(($orig_w - $crop_w) / 2);
        $crop_y = 0;
    } else {
        $crop_w = $orig_w;
        $crop_h = (int)($orig_w / $dest_ratio);
        $crop_x = 0;
        $crop_y = (int)(($orig_h - $crop_h) / 2);
    }

    $thumb = imagecreatetruecolor($width, $height);

    // Preserve transparency for PNG
    if ($type === IMAGETYPE_PNG) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }

    imagecopyresampled($thumb, $src_img, 0, 0, $crop_x, $crop_y, $width, $height, $crop_w, $crop_h);

    match ($type) {
        IMAGETYPE_PNG  => imagepng($thumb, $dest),
        IMAGETYPE_WEBP => imagewebp($thumb, $dest, 85),
        default        => imagejpeg($thumb, $dest, 85),
    };

    imagedestroy($src_img);
    imagedestroy($thumb);
}

function delete_car_images(int $car_id): void {
    $dir = UPLOAD_DIR . "cars/{$car_id}/";
    if (!is_dir($dir)) return;
    foreach (glob($dir . '*') as $file) {
        if (is_file($file)) unlink($file);
    }
    rmdir($dir);
}

function delete_single_image(string $path): void {
    $full = UPLOAD_DIR . ltrim($path, '/');
    if (is_file($full)) unlink($full);
    // also delete thumb
    $dir   = dirname($full);
    $file  = basename($full);
    $thumb = $dir . '/thumb_' . $file;
    if (is_file($thumb)) unlink($thumb);
}

function car_thumbnail_url(?string $path): string {
    if (!$path) return APP_URL . '/assets/images/cars/placeholder.jpg';
    return UPLOAD_URL . $path;
}

function car_image_url(string $path): string {
    return UPLOAD_URL . $path;
}
