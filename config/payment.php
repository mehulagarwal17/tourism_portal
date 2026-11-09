<?php
/**
 * Payment & Official Tour Invoice Configuration
 * Project: Online Tourism Portal (BCA 5th Semester)
 * Supports Razorpay Sandbox / Direct Gateway Simulation / GST Invoice Generation
 */

// Tourism Company & Official GST Billing Details
define('COMPANY_NAME', 'Online Tourism Portal Pvt. Ltd.');
define('COMPANY_TAGLINE', 'Government Recognized Tour Operator & Travel Concierge');
define('COMPANY_ADDRESS', 'Bareilly, Uttar Pradesh, India');
define('COMPANY_STATE', 'Uttar Pradesh');
define('COMPANY_GSTIN', '09AAACT0192A1Z8');
define('COMPANY_PAN', 'AAACT0192A');
define('COMPANY_SAC_CODE', '998555'); // SAC Code for Tour Operator Services under GST
define('COMPANY_PHONE', '999-787-9985');
define('COMPANY_EMAIL', 'support@tourismportal.com / nbma142777@gmail.com');
define('COMPANY_WEBSITE', 'www.tourismportal.com');

// Load environment variables from .env if present
(function() {
    $envPath = __DIR__ . '/../.env';
    if (file_exists($envPath)) {
        $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines) {
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) continue;
                if (str_contains($line, '=')) {
                    [$key, $val] = explode('=', $line, 2);
                    $key = trim($key);
                    $val = trim($val, " \t\n\r\0\x0B\"'");
                    if (!getenv($key)) {
                        putenv("{$key}={$val}");
                        $_ENV[$key] = $val;
                        $_SERVER[$key] = $val;
                    }
                }
            }
        }
    }
})();

// Payment Gateway Configuration (Razorpay Sandbox & Live Gateway)
define('PAYMENT_GATEWAY_NAME', 'Razorpay Payments');
define('RAZORPAY_KEY_ID', getenv('RAZORPAY_KEY_ID') ?: 'rzp_test_BCA5SemTourism');
define('RAZORPAY_KEY_SECRET', getenv('RAZORPAY_KEY_SECRET') ?: 'rzp_test_secret_sample');
define('PAYMENT_CURRENCY', 'INR');
define('PAYMENT_CURRENCY_SYMBOL', '₹');

/**
 * Creates a Razorpay Order using the official Razorpay Orders API
 * POST https://api.razorpay.com/v1/orders
 *
 * @param float $amount Total amount in INR
 * @param string $receipt Unique receipt identifier (e.g. Booking Reference)
 * @param array $notes Optional key-value notes
 * @return array|null Razorpay order details or null
 */
function createRazorpayOrder(float $amount, string $receipt, array $notes = []): ?array {
    $keyId = RAZORPAY_KEY_ID;
    $keySecret = RAZORPAY_KEY_SECRET;

    $amountInPaise = (int)round($amount * 100);

    // If using sample placeholder key, simulate an order ID so checkout works seamlessly
    if (empty($keyId) || str_contains($keyId, 'YOUR_KEY_ID') || $keyId === 'rzp_test_BCA5SemTourism') {
        return [
            'id' => 'order_sim_' . substr(md5($receipt . time()), 0, 14),
            'entity' => 'order',
            'amount' => $amountInPaise,
            'amount_paid' => 0,
            'amount_due' => $amountInPaise,
            'currency' => PAYMENT_CURRENCY,
            'receipt' => $receipt,
            'status' => 'created',
            'notes' => $notes,
            'is_simulation' => true
        ];
    }

    $payload = json_encode([
        'amount'   => $amountInPaise,
        'currency' => PAYMENT_CURRENCY,
        'receipt'  => $receipt,
        'notes'    => $notes
    ]);

    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "{$keyId}:{$keySecret}");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err || $httpCode >= 400 || empty($response)) {
        error_log("Razorpay Order API Error (HTTP {$httpCode}): " . ($err ?: $response));
        // Fallback simulation so user can still test booking if API fails or network timeout
        return [
            'id' => 'order_sim_' . substr(md5($receipt . time()), 0, 14),
            'entity' => 'order',
            'amount' => $amountInPaise,
            'currency' => PAYMENT_CURRENCY,
            'receipt' => $receipt,
            'status' => 'created',
            'is_simulation' => true
        ];
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}

/**
 * Validates Razorpay Payment Signature
 * HMAC SHA256 of order_id + "|" + razorpay_payment_id with RAZORPAY_KEY_SECRET
 */
function verifyRazorpaySignature(string $orderId, string $paymentId, string $signature): bool {
    $keySecret = RAZORPAY_KEY_SECRET;
    if (empty($keySecret) || $keySecret === 'rzp_test_secret_sample' || str_contains($keySecret, 'YOUR_KEY_SECRET')) {
        // In test simulation, signature is approved
        return true;
    }

    $expectedSignature = hash_hmac('sha256', "{$orderId}|{$paymentId}", $keySecret);
    return hash_equals($expectedSignature, $signature);
}

// GST Rates for Indian Tour Operator Services (5% composite GST: 2.5% CGST + 2.5% SGST)
define('GST_CGST_RATE', 2.5);
define('GST_SGST_RATE', 2.5);
define('GST_TOTAL_RATE', 5.0);

/**
 * Calculates GST breakdown for a total package price (Inclusive of GST).
 * 
 * @param float $totalAmount Total amount payable by traveler
 * @return array{taxable_value: float, cgst: float, sgst: float, total: float}
 */
function calculateInvoiceGst(float $totalAmount): array {
    $taxable = round($totalAmount / (1 + (GST_TOTAL_RATE / 100)), 2);
    $totalTax = round($totalAmount - $taxable, 2);
    $cgst = round($totalTax / 2, 2);
    $sgst = round($totalTax - $cgst, 2); // balance to ensure precision equality

    return [
        'taxable_value' => $taxable,
        'cgst'          => $cgst,
        'sgst'          => $sgst,
        'total'         => round($totalAmount, 2),
    ];
}

/**
 * Generates an official tax invoice sequence number.
 * 
 * @param int $bookingId
 * @return string e.g. "INV-2026-00105"
 */
function generateInvoiceNumber(int $bookingId): string {
    return 'INV-' . date('Y') . '-' . str_pad((string)$bookingId, 5, '0', STR_PAD_LEFT);
}

/**
 * Converts Indian Rupee number to words (Indian numbering system: Lakh, Thousand, Hundred).
 * 
 * @param float|int $number
 * @return string
 */
function convertRupeesToWords(float $number): string {
    $no = floor($number);
    $point = round($number - $no, 2) * 100;
    $words = [
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
        5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
        14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
        18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty',
        40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy',
        80 => 'Eighty', 90 => 'Ninety'
    ];

    if ($no == 0) {
        return 'Zero Rupees Only';
    }

    $str = [];
    $units = ['', 'Thousand', 'Lakh', 'Crore'];
    $digits = [0, 0, 0, 0];
    
    // Split into hundreds, thousands, lakhs, crores
    $digits[0] = $no % 1000;
    $no = floor($no / 1000);
    $digits[1] = $no % 100;
    $no = floor($no / 100);
    $digits[2] = $no % 100;
    $no = floor($no / 100);
    $digits[3] = $no % 100;

    for ($i = 3; $i >= 0; $i--) {
        if ($digits[$i] > 0) {
            $part = '';
            $val = $digits[$i];
            if ($i == 0) {
                // Handle hundreds
                $h = floor($val / 100);
                $rem = $val % 100;
                if ($h > 0) {
                    $part .= $words[$h] . ' Hundred ';
                }
                if ($rem > 0) {
                    if ($rem < 20) {
                        $part .= $words[$rem] . ' ';
                    } else {
                        $tens = floor($rem / 10) * 10;
                        $ones = $rem % 10;
                        $part .= $words[$tens] . ' ' . $words[$ones] . ' ';
                    }
                }
            } else {
                if ($val < 20) {
                    $part .= $words[$val] . ' ';
                } else {
                    $tens = floor($val / 10) * 10;
                    $ones = $val % 10;
                    $part .= $words[$tens] . ' ' . $words[$ones] . ' ';
                }
                $part .= $units[$i] . ' ';
            }
            $str[] = trim($part);
        }
    }

    $result = trim(implode(' ', $str)) . ' Rupees';
    if ($point > 0) {
        $pWords = ($point < 20) ? $words[$point] : ($words[floor($point / 10) * 10] . ' ' . $words[$point % 10]);
        $result .= ' and ' . trim($pWords) . ' Paise';
    }

    return $result . ' Only';
}
