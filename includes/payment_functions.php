<?php
require_once __DIR__ . '/functions.php';

// ─── M-Pesa Daraja ───────────────────────────────────────────────────────────

function mpesa_base_url(): string {
    $env = get_setting('mpesa_env', 'sandbox');
    return $env === 'live'
        ? 'https://api.safaricom.co.ke'
        : 'https://sandbox.safaricom.co.ke';
}

function mpesa_get_token(): string {
    $key    = get_setting('mpesa_consumer_key');
    $secret = get_setting('mpesa_consumer_secret');

    $ch = curl_init(mpesa_base_url() . '/oauth/v1/generate?grant_type=client_credentials');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD        => "{$key}:{$secret}",
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => APP_ENV === 'production',
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['access_token'] ?? throw new RuntimeException('M-Pesa token request failed');
}

function mpesa_stk_push(string $phone, float $amount, string $booking_ref): array {
    $shortcode  = get_setting('mpesa_shortcode');
    $passkey    = get_setting('mpesa_passkey');
    $timestamp  = date('YmdHis');
    $password   = base64_encode($shortcode . $passkey . $timestamp);
    $callback   = APP_URL . '/api/mpesa-callback.php';

    // M-Pesa requires phone as 254XXXXXXXXX (no +)
    $phone = preg_replace('/^\+/', '', $phone);
    if (str_starts_with($phone, '0')) {
        $phone = '254' . substr($phone, 1);
    }

    $token = mpesa_get_token();

    $payload = [
        'BusinessShortCode' => $shortcode,
        'Password'          => $password,
        'Timestamp'         => $timestamp,
        'TransactionType'   => 'CustomerPayBillOnline',
        'Amount'            => (int) ceil($amount),
        'PartyA'            => $phone,
        'PartyB'            => $shortcode,
        'PhoneNumber'       => $phone,
        'CallBackURL'       => $callback,
        'AccountReference'  => $booking_ref,
        'TransactionDesc'   => 'Tripple K Booking ' . $booking_ref,
    ];

    $ch = curl_init(mpesa_base_url() . '/mpesa/stkpush/v1/processrequest');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => APP_ENV === 'production',
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true) ?? [];
}

function mpesa_query_stk(string $checkout_request_id): array {
    $shortcode = get_setting('mpesa_shortcode');
    $passkey   = get_setting('mpesa_passkey');
    $timestamp = date('YmdHis');
    $password  = base64_encode($shortcode . $passkey . $timestamp);

    $token = mpesa_get_token();

    $payload = [
        'BusinessShortCode' => $shortcode,
        'Password'          => $password,
        'Timestamp'         => $timestamp,
        'CheckoutRequestID' => $checkout_request_id,
    ];

    $ch = curl_init(mpesa_base_url() . '/mpesa/stkpushquery/v1/query');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => APP_ENV === 'production',
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true) ?? [];
}

// ─── Flutterwave ─────────────────────────────────────────────────────────────

function flutterwave_base_url(): string {
    return 'https://api.flutterwave.com/v3';
}

function flutterwave_initiate_payment(array $booking, array $customer): array {
    $secret = get_setting('flutterwave_secret_key');

    $payload = [
        'tx_ref'         => $booking['booking_ref'],
        'amount'         => $booking['total_amount'],
        'currency'       => 'KES',
        'redirect_url'   => APP_URL . '/booking.php?payment=complete&ref=' . urlencode($booking['booking_ref']),
        'customer'       => [
            'email'       => $customer['email'],
            'phonenumber' => $customer['phone'],
            'name'        => $customer['full_name'],
        ],
        'customizations' => [
            'title'       => APP_NAME . ' Payment',
            'description' => 'Booking ' . $booking['booking_ref'],
            'logo'        => APP_URL . '/assets/images/logo/logo-1.png',
        ],
        'meta'           => ['booking_ref' => $booking['booking_ref']],
    ];

    $ch = curl_init(flutterwave_base_url() . '/payments');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $secret,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => APP_ENV === 'production',
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true) ?? [];
}

function flutterwave_verify_transaction(string $transaction_id): array {
    $secret = get_setting('flutterwave_secret_key');

    $ch = curl_init(flutterwave_base_url() . '/transactions/' . $transaction_id . '/verify');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $secret],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => APP_ENV === 'production',
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true) ?? [];
}
