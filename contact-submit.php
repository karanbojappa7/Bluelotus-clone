<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/bootstrap.php';

function enquiry_wants_json(): bool
{
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
    $requested = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    return str_contains($accept, 'application/json') || $requested === 'fetch' || $requested === 'xmlhttprequest';
}

function enquiry_reply(bool $ok, string $message, int $status = 200): void
{
    if (enquiry_wants_json()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($ok) {
        redirect(url_for('contact') . '?sent=1');
    }
    http_response_code($status);
    redirect(url_for('contact') . '?error=1');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && isset($_GET['captcha'])) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode(quote_captcha_issue(), JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    enquiry_reply(false, 'Please submit the contact form.', 405);
}

$csrf = $_POST['csrf'] ?? '';
if (!is_string($csrf) || $csrf === '' || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
    enquiry_reply(false, 'Your session expired. Refresh the page and try again.', 403);
}

if (trim(post('website')) !== '') {
    enquiry_reply(true, 'Thank you. Our team will get back to you within one business day.');
}

if (!db_ready()) {
    enquiry_reply(false, 'The enquiry desk is temporarily unavailable. Please call or email us.', 503);
}

$ip = cms_client_ip();
try {
    migrate();
    if (enquiry_ip_limited($ip)) {
        enquiry_reply(false, 'Too many messages from this connection. Please try again later.', 429);
    }
} catch (Throwable $e) {
    enquiry_reply(false, 'Could not save your message. Please try again.', 500);
}

$name = post('fullName');
$company = post('company');
$email = post('email');
$phone = post('phone');
$category = post('category');
$message = post('message');
$source = post('source');
$isQuotePopup = $source === 'quote-popup';

if ($isQuotePopup && !quote_captcha_ok(post('captcha'))) {
    enquiry_reply(false, 'Please solve the security check and try again.', 422);
}

if ($name === '' || $email === '' || $phone === '') {
    enquiry_reply(false, 'Please fill in your name, email, and phone.', 422);
}
if ($isQuotePopup && ($company === '' || $category === '')) {
    enquiry_reply(false, 'Please add your company and primary safety need.', 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    enquiry_reply(false, 'Enter a valid email address.', 422);
}
if (strlen(preg_replace('/[^\d]/', '', $phone) ?? '') < 10) {
    enquiry_reply(false, 'Enter a phone number with at least 10 digits.', 422);
}
if (!$isQuotePopup && $message === '') {
    enquiry_reply(false, 'Please fill in your name, email, phone, and message.', 422);
}
if (!$isQuotePopup && strlen($message) < 10) {
    enquiry_reply(false, 'Tell us a little more — at least 10 characters.', 422);
}
if ($isQuotePopup) {
    $parts = ['Bulk quote / project estimate request.'];
    if ($category !== '') {
        $parts[] = 'Primary safety need: ' . $category;
    }
    if ($message !== '') {
        $parts[] = $message;
    }
    $message = implode("\n\n", $parts);
}

try {
    save_enquiry([
        'name' => substr($name, 0, 191),
        'company' => substr($company, 0, 191),
        'email' => substr($email, 0, 191),
        'phone' => substr($phone, 0, 80),
        'category' => substr($category, 0, 191),
        'message' => substr($message, 0, 8000),
        'ip' => substr($ip, 0, 45),
    ]);
} catch (Throwable $e) {
    enquiry_reply(false, 'Could not save your message. Please try again.', 500);
}

enquiry_reply(true, 'Thank you. Our team will get back to you within one business day.');
