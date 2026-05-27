<?php
require_once __DIR__ . '/config.php';

/**
 * Save an uploaded file for a customer.
 * Returns the relative path (from UPLOAD_DIR) or throws RuntimeException.
 *
 * @param array  $file      Single entry from $_FILES
 * @param string $subdir    e.g. 'documents' or 'photos'
 * @param int    $customer_id
 */
function save_customer_file(array $file, string $subdir, int $customer_id): string {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed (code ' . $file['error'] . ').');
    }
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new RuntimeException('File too large. Maximum 10 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    $allowed_docs   = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    $allowed_photos = ['image/jpeg', 'image/png', 'image/webp'];
    $allowed = ($subdir === 'photos') ? $allowed_photos : $allowed_docs;

    if (!in_array($mime, $allowed, true)) {
        throw new RuntimeException("Invalid file type \"{$mime}\". Allowed: " . implode(', ', $allowed));
    }

    $ext = match ($mime) {
        'image/png'      => 'png',
        'image/webp'     => 'webp',
        'application/pdf'=> 'pdf',
        default          => 'jpg',
    };

    $dir = UPLOAD_DIR . "customers/{$customer_id}/{$subdir}/";
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        // Block direct PHP execution in customer uploads
        file_put_contents($dir . '.htaccess', "Options -Indexes\n<Files *.php>\n  Deny from all\n</Files>\n");
    }

    $filename = uniqid("{$subdir}_", true) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
        throw new RuntimeException('Failed to save file.');
    }

    return "customers/{$customer_id}/{$subdir}/{$filename}";
}

/**
 * Delete a customer file from disk.
 */
function delete_customer_file(?string $rel_path): void {
    if (!$rel_path) return;
    $full = UPLOAD_DIR . ltrim($rel_path, '/');
    if (is_file($full)) unlink($full);
}

/**
 * Delete all files for a customer (entire customer directory).
 */
function delete_customer_files(int $customer_id): void {
    $dir = UPLOAD_DIR . "customers/{$customer_id}/";
    if (!is_dir($dir)) return;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $path) {
        $path->isDir() ? rmdir($path->getRealPath()) : unlink($path->getRealPath());
    }
    rmdir($dir);
}

/**
 * Return absolute URL for a customer file, or '' if empty.
 */
function customer_file_url(?string $rel_path): string {
    if (!$rel_path) return '';
    return UPLOAD_URL . $rel_path;
}

/**
 * Build the display name of a customer row.
 */
function customer_display_name(array $row): string {
    if ($row['customer_type'] === 'organization') {
        return $row['corporate_name'] ?? $row['full_name'] ?? '–';
    }
    $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    return $name ?: ($row['full_name'] ?? '–');
}

/**
 * Return badge HTML for customer status.
 */
function customer_status_badge(string $status): string {
    $cls = $status === 'active'
        ? 'bg-green-500/10 text-green-400 border border-green-500/20'
        : 'bg-red-500/10 text-red-400 border border-red-500/20';
    return '<span class="inline-block rounded px-2 py-0.5 text-xs font-medium ' . $cls . '">'
        . htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8') . '</span>';
}

/**
 * Return badge HTML for customer type.
 */
function customer_type_badge(string $type): string {
    $cls = $type === 'organization'
        ? 'bg-blue-1/10 text-blue-1 border border-blue-1/20'
        : 'bg-yellow-4 text-yellow-3 border border-yellow-3/20';
    $label = $type === 'organization' ? 'Organisation' : 'Individual';
    return '<span class="inline-block rounded px-2 py-0.5 text-xs font-medium ' . $cls . '">'
        . $label . '</span>';
}
