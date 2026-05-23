<?php
require_once __DIR__ . '/functions.php';

// ─── Paystack ─────────────────────────────────────────────────────────────────

define('PAYSTACK_API', 'https://api.paystack.co');

function paystack_secret_key(): string {
    return get_setting('paystack_secret_key', '');
}

/**
 * Initialize a Paystack transaction.
 * Returns the full Paystack API response array.
 * On success: $result['data']['authorization_url'] and $result['data']['reference'].
 */
function paystack_initialize_transaction(array $booking, array $customer): array {
    $amount_kobo = (int) round((float)$booking['total_amount'] * 100); // KES → kobo/cents

    $payload = [
        'email'        => $customer['email'],
        'amount'       => $amount_kobo,
        'currency'     => 'KES',
        'reference'    => $booking['booking_ref'],
        'callback_url' => APP_URL . '/booking.php?payment=complete&ref=' . urlencode($booking['booking_ref']),
        'channels'     => ['card', 'mobile_money', 'bank'],
        'metadata'     => [
            'booking_ref'  => $booking['booking_ref'],
            'customer_name'=> $customer['full_name'] ?? '',
            'phone'        => $customer['phone']     ?? '',
            'custom_fields'=> [
                [
                    'display_name'  => 'Booking Reference',
                    'variable_name' => 'booking_ref',
                    'value'         => $booking['booking_ref'],
                ],
            ],
        ],
    ];

    $ch = curl_init(PAYSTACK_API . '/transaction/initialize');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . paystack_secret_key(),
            'Content-Type: application/json',
            'Cache-Control: no-cache',
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => APP_ENV === 'production',
    ]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) {
        throw new RuntimeException('Paystack cURL error: ' . $err);
    }

    return json_decode($response, true) ?? [];
}

/**
 * Verify a Paystack transaction by reference.
 * Returns the full Paystack API response array.
 * Check $result['data']['status'] === 'success' and $result['data']['amount'].
 */
function paystack_verify_transaction(string $reference): array {
    $ch = curl_init(PAYSTACK_API . '/transaction/verify/' . rawurlencode($reference));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . paystack_secret_key(),
            'Cache-Control: no-cache',
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => APP_ENV === 'production',
    ]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) {
        throw new RuntimeException('Paystack verify cURL error: ' . $err);
    }

    return json_decode($response, true) ?? [];
}

/**
 * Verify the X-Paystack-Signature header from a webhook request.
 */
function paystack_verify_webhook_signature(string $payload, string $signature): bool {
    $expected = hash_hmac('sha512', $payload, paystack_secret_key());
    return hash_equals($expected, $signature);
}

/**
 * Record a successful Paystack payment and confirm the booking in one transaction.
 */
function paystack_confirm_booking(PDO $db, int $booking_id, string $reference, array $data): void {
    $amount   = (float)($data['amount'] ?? 0) / 100; // convert kobo back to KES
    $channel  = $data['channel'] ?? 'card';
    $currency = $data['currency'] ?? 'KES';

    $db->beginTransaction();
    try {
        $db->prepare('
            INSERT INTO payments
              (booking_id, payment_method, transaction_ref, gateway_ref, amount, currency, status, gateway_response, paid_at)
            VALUES (?, ?, ?, ?, ?, ?, "completed", ?, NOW())
        ')->execute([
            $booking_id,
            $channel,
            $reference,
            $data['id'] ?? $reference,
            $amount,
            $currency,
            json_encode($data),
        ]);

        $db->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?")->execute([$booking_id]);

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}
