<?php
require_once __DIR__ . '/db.php';

// ─── Output escaping ─────────────────────────────────────────────────────────

function h(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ─── Currency ────────────────────────────────────────────────────────────────

function format_kes(float $amount): string {
    return '$ ' . number_format($amount, 2);
}

// ─── Date helpers ────────────────────────────────────────────────────────────

function display_date(?string $date): string {
    if (!$date) return '-';
    return date('d/m/Y', strtotime($date));
}

function display_datetime(?string $dt): string {
    if (!$dt) return '-';
    return date('d/m/Y H:i', strtotime($dt));
}

// ─── CSRF ────────────────────────────────────────────────────────────────────

function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// ─── JSON API responses ──────────────────────────────────────────────────────

function json_response(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ─── Settings ────────────────────────────────────────────────────────────────

function get_setting(string $key, string $default = ''): string {
    static $cache = [];
    if (!isset($cache[$key])) {
        try {
            $stmt = get_db()->prepare('SELECT setting_val FROM settings WHERE setting_key = ?');
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            $cache[$key] = ($val !== false) ? $val : $default;
        } catch (Throwable) {
            $cache[$key] = $default;
        }
    }
    return $cache[$key];
}

// ─── Booking reference ───────────────────────────────────────────────────────

function generate_booking_ref(): string {
    $year   = date('Y');
    $prefix = get_setting('invoice_prefix', 'TK');
    $stmt   = get_db()->query('SELECT COUNT(*) FROM bookings');
    $n      = (int)$stmt->fetchColumn() + 1;
    return sprintf('%s-%s-%05d', $prefix, $year, $n);
}

// ─── Slugify ─────────────────────────────────────────────────────────────────

function slugify(string $text): string {
    $text = preg_replace('/[^\w\s-]/u', '', mb_strtolower(trim($text)));
    return preg_replace('/[\s_-]+/', '-', $text);
}

// ─── Redirect ────────────────────────────────────────────────────────────────

function redirect(string $url): never {
    header('Location: ' . $url);
    exit;
}

// ─── Flash messages (session-based) ─────────────────────────────────────────

function flash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// ─── Status badge classes ────────────────────────────────────────────────────

function booking_status_class(string $status): string {
    return match ($status) {
        'confirmed' => 'bg-blue-1/5 text-blue-1',
        'active'    => 'bg-green-50 text-green-600',
        'completed' => 'bg-gray-100 text-gray-600',
        'cancelled' => 'bg-red-3 text-red-2',
        default     => 'bg-yellow-4 text-yellow-3',  // pending
    };
}

function payment_status_class(string $status): string {
    return match ($status) {
        'completed'  => 'bg-green-50 text-green-600',
        'processing' => 'bg-blue-1/5 text-blue-1',
        'failed'     => 'bg-red-3 text-red-2',
        'refunded'   => 'bg-gray-100 text-gray-600',
        default      => 'bg-yellow-4 text-yellow-3',  // pending
    };
}

// ─── Pagination helper ───────────────────────────────────────────────────────

function paginate(int $total, int $perPage, int $currentPage, string $urlPattern): array {
    $totalPages = max(1, (int) ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));

    // Build window of page numbers: always show first, last, and up to 2 around current
    $pages = [];
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i === 1 || $i === $totalPages || abs($i - $currentPage) <= 2) {
            $pages[] = $i;
        }
    }
    // Insert null (ellipsis) for gaps
    $with_gaps = [];
    $prev = null;
    foreach ($pages as $p) {
        if ($prev !== null && $p - $prev > 1) $with_gaps[] = null;
        $with_gaps[] = $p;
        $prev = $p;
    }

    return [
        'total'        => $total,
        'per_page'     => $perPage,
        'current_page' => $currentPage,
        'total_pages'  => $totalPages,
        'has_prev'     => $currentPage > 1,
        'has_next'     => $currentPage < $totalPages,
        'prev_url'     => $currentPage > 1 ? sprintf($urlPattern, $currentPage - 1) : null,
        'next_url'     => $currentPage < $totalPages ? sprintf($urlPattern, $currentPage + 1) : null,
        'pages'        => $with_gaps,   // array of int|null (null = ellipsis)
        'url_pattern'  => $urlPattern,
    ];
}
