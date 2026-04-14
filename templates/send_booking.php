<?php
/**
 * UPKWS Jungle Safari – Safari Booking Email Handler
 * Place this file on your PHP-enabled server in the same directory as index.html
 */

// ── Configuration ─────────────────────────────────────────────────────────────
define('ADMIN_EMAIL', 'info@upkwsjunglesafari.com');   // Change to your actual email
define('FROM_EMAIL',  'noreply@upkwsjunglesafari.com'); // Change to your domain
define('SITE_NAME',   'UPKWS Jungle Safari');

// ── Helpers ───────────────────────────────────────────────────────────────────
function sanitize(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

function sendMail(string $to, string $subject, string $body, string $fromName = SITE_NAME): bool {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$fromName} <" . FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . FROM_EMAIL . "\r\n";
    $headers .= "X-Mailer: PHP/" . PHP_VERSION . "\r\n";
    return mail($to, $subject, $body, $headers);
}

// ── CORS / Method check ───────────────────────────────────────────────────────
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// ── Collect & sanitize fields ─────────────────────────────────────────────────
$name    = sanitize($_POST['name']    ?? '');
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$contact = sanitize($_POST['contact'] ?? '');
$gate    = sanitize($_POST['gate']    ?? '');
$guests  = sanitize($_POST['guests']  ?? '');
$date    = sanitize($_POST['date']    ?? '');
$slot    = sanitize($_POST['slot']    ?? '');

// ── Validate ──────────────────────────────────────────────────────────────────
$errors = [];
if (empty($name))                          $errors[] = 'Name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';
if (empty($contact))                       $errors[] = 'Contact number is required.';
if (empty($gate))                          $errors[] = 'Safari gate is required.';
if (empty($guests))                        $errors[] = 'Number of guests is required.';
if (empty($date))                          $errors[] = 'Safari date is required.';
if (!in_array($slot, ['Morning','Evening'])) $errors[] = 'A valid slot (Morning/Evening) is required.';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Format date nicely
$dateFormatted = date('l, d F Y', strtotime($date));

// ── Email to Admin ────────────────────────────────────────────────────────────
$adminSubject = "New Safari Booking – {$name} | {$gate} | {$dateFormatted}";
$adminBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"/>
<style>
  body{font-family:'Helvetica Neue',Arial,sans-serif;background:#f5f5f0;margin:0;padding:30px;}
  .card{background:#ffffff;max-width:560px;margin:0 auto;border-radius:4px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);}
  .header{background:#1a2e12;padding:32px 36px;color:#f5efe0;}
  .header h1{margin:0;font-size:22px;font-weight:700;letter-spacing:0.5px;}
  .header p{margin:8px 0 0;font-size:13px;color:#d4b896;letter-spacing:1px;text-transform:uppercase;}
  .body{padding:32px 36px;}
  .row{display:flex;padding:10px 0;border-bottom:1px solid #f0ece0;font-size:14px;}
  .row:last-child{border-bottom:none;}
  .label{width:140px;color:#5a8a3a;font-weight:600;font-size:12px;letter-spacing:1px;text-transform:uppercase;flex-shrink:0;padding-top:1px;}
  .value{color:#1a1a0e;font-weight:500;}
  .footer{background:#f5efe0;padding:20px 36px;text-align:center;font-size:12px;color:#888;border-top:1px solid #e8dfc8;}
</style>
</head>
<body>
<div class="card">
  <div class="header">
    <h1>New Safari Booking</h1>
    <p>UPKWS Jungle Safari</p>
  </div>
  <div class="body">
    <div class="row"><span class="label">Name</span><span class="value">{$name}</span></div>
    <div class="row"><span class="label">Email</span><span class="value">{$email}</span></div>
    <div class="row"><span class="label">Contact</span><span class="value">{$contact}</span></div>
    <div class="row"><span class="label">Safari Gate</span><span class="value">{$gate}</span></div>
    <div class="row"><span class="label">Guests</span><span class="value">{$guests} Guest(s)</span></div>
    <div class="row"><span class="label">Safari Date</span><span class="value">{$dateFormatted}</span></div>
    <div class="row"><span class="label">Slot</span><span class="value">{$slot} Safari</span></div>
    <div class="row"><span class="label">Submitted</span><span class="value">{$_SERVER['REQUEST_TIME']}</span></div>
  </div>
  <div class="footer">UPKWS Jungle Safari · www.upkwsjunglesafari.com</div>
</div>
</body>
</html>
HTML;

// ── Confirmation Email to Guest ───────────────────────────────────────────────
$guestSubject = "Your Safari Booking is Confirmed – UPKWS Jungle Safari";
$guestBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8"/>
<style>
  body{font-family:'Helvetica Neue',Arial,sans-serif;background:#f5f5f0;margin:0;padding:30px;}
  .card{background:#ffffff;max-width:560px;margin:0 auto;border-radius:4px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);}
  .header{background:#1a2e12;padding:32px 36px;color:#f5efe0;}
  .header h1{margin:0;font-size:22px;font-weight:700;}
  .header p{margin:8px 0 0;font-size:13px;color:#d4b896;}
  .greeting{padding:28px 36px 0;font-size:15px;color:#1a1a0e;line-height:1.7;}
  .summary{margin:24px 36px;border:1px solid #e8dfc8;border-radius:4px;overflow:hidden;}
  .summary-header{background:#3b5e28;color:#f5efe0;padding:12px 20px;font-size:12px;letter-spacing:2px;text-transform:uppercase;font-weight:600;}
  .row{display:flex;padding:10px 20px;border-bottom:1px solid #f0ece0;font-size:14px;}
  .row:last-child{border-bottom:none;}
  .label{width:130px;color:#5a8a3a;font-weight:600;font-size:12px;letter-spacing:1px;text-transform:uppercase;flex-shrink:0;}
  .value{color:#1a1a0e;font-weight:500;}
  .note{padding:0 36px 28px;font-size:13px;color:#555;line-height:1.7;}
  .cta{margin:0 36px 32px;display:block;background:#c8902a;color:#ffffff;text-align:center;padding:14px;border-radius:3px;font-size:13px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;text-decoration:none;}
  .footer{background:#f5efe0;padding:20px 36px;text-align:center;font-size:12px;color:#888;border-top:1px solid #e8dfc8;}
</style>
</head>
<body>
<div class="card">
  <div class="header">
    <h1>🌿 Booking Confirmed!</h1>
    <p>Umred Pauni Karhandla Wildlife Sanctuary</p>
  </div>
  <div class="greeting">
    Dear <strong>{$name}</strong>,<br/><br/>
    Thank you for booking your jungle safari with <strong>UPKWS Jungle Safari</strong>. We're thrilled to welcome you into the wild heart of Maharashtra!<br/><br/>
    Here's a summary of your booking:
  </div>
  <div class="summary">
    <div class="summary-header">Booking Summary</div>
    <div class="row"><span class="label">Name</span><span class="value">{$name}</span></div>
    <div class="row"><span class="label">Gate</span><span class="value">{$gate}</span></div>
    <div class="row"><span class="label">Guests</span><span class="value">{$guests} Guest(s)</span></div>
    <div class="row"><span class="label">Date</span><span class="value">{$dateFormatted}</span></div>
    <div class="row"><span class="label">Slot</span><span class="value">{$slot} Safari</span></div>
  </div>
  <div class="note">
    Our team will contact you on <strong>{$contact}</strong> within 24 hours to confirm your safari slot and share further details including permit information and meeting point instructions.<br/><br/>
    <strong>Important:</strong> Please arrive at the gate <strong>30 minutes before</strong> your safari time with a valid government-issued photo ID.
  </div>
  <a href="https://www.upkwsjunglesafari.com" class="cta">Visit Our Website</a>
  <div class="footer">
    UPKWS Jungle Safari &nbsp;|&nbsp; +91 7040460567 &nbsp;|&nbsp; info@upkwsjunglesafari.com<br/>
    www.upkwsjunglesafari.com
  </div>
</div>
</body>
</html>
HTML;

// ── Send Emails ───────────────────────────────────────────────────────────────
$adminSent = sendMail(ADMIN_EMAIL, $adminSubject, $adminBody);
$guestSent = sendMail($email, $guestSubject, $guestBody, SITE_NAME);

if ($adminSent && $guestSent) {
    echo json_encode(['success' => true, 'message' => 'Booking confirmed. Emails sent.']);
} elseif ($adminSent) {
    echo json_encode(['success' => true, 'message' => 'Booking received. Admin notified.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Booking received but email delivery failed. Please contact us directly.']);
}
