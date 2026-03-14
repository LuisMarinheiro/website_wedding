<?php
/* ============================================================
   rsvp.php — RSVP Form Handler
   Stores confirmations in a CSV and sends an email notification
   Compatible with OVH shared hosting (PHP 8.x)
   ============================================================ */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

/* ── Configuration ── Edit these values ── */
define('NOTIFY_EMAIL',  'luis_marinheiro5@hotmail.com');   // <-- change to your email
define('FROM_EMAIL',    'noreply@bodaLilianaLuis.pt');
define('FROM_NAME',     'bodaLilianaLuis.pt');
define('CSV_FILE',      __DIR__ . '/data/rsvp.csv');
define('ALLOWED_ORIGIN', 'https://bodaLilianaLuis.pt');
/* ────────────────────────────────────────── */

/* ── CORS (same origin only) ── */
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (
  !empty($origin) &&
  parse_url($origin, PHP_URL_HOST) !== parse_url(ALLOWED_ORIGIN, PHP_URL_HOST) &&
  $_SERVER['HTTP_HOST'] !== 'localhost'
) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

/* ── Only accept POST ── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

/* ── Sanitise input ── */
function clean(string $value, int $maxLen = 255): string {
    return substr(trim(strip_tags($value)), 0, $maxLen);
}

$name      = clean($_POST['name']      ?? '');
$email     = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone     = clean($_POST['phone']     ?? '', 30);
$guests    = clean($_POST['guests']    ?? '');
$attending = clean($_POST['attending'] ?? '');
$dietary   = clean($_POST['dietary']   ?? '', 500);

/* ── Validate required fields ── */
$errors = [];
if (empty($name))      $errors[] = 'name';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'email';
if (!in_array($attending, ['yes', 'no'], true)) $errors[] = 'attending';
if (empty($guests))    $errors[] = 'guests';

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'validation', 'fields' => $errors]);
    exit;
}

/* ── Save to CSV ── */
$dataDir = dirname(CSV_FILE);
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0750, true);
}

$isNew    = !file_exists(CSV_FILE);
$fh       = fopen(CSV_FILE, 'a');
$datetime = date('Y-m-d H:i:s');

if ($fh) {
    if ($isNew) {
        fputcsv($fh, ['datetime', 'name', 'email', 'phone', 'guests', 'attending', 'dietary']);
    }
    fputcsv($fh, [$datetime, $name, $email, $phone, $guests, $attending, $dietary]);
    fclose($fh);
}

/* ── Send notification email ── */
$attendingLabel = $attending === 'yes' ? '✅ PRESENTE' : '❌ NÃO PRESENTE';
$subject = "RSVP Casamento Liliana & Luís — {$name} ({$attendingLabel})";

$body = "Nova confirmação recebida:\n\n"
      . "Nome:       {$name}\n"
      . "Email:      {$email}\n"
      . "Telemóvel:  {$phone}\n"
      . "Convidados: {$guests}\n"
      . "Presença:   {$attendingLabel}\n"
      . "Obs:        {$dietary}\n"
      . "Data/Hora:  {$datetime}\n\n"
      . "— bodaLilianaLuis.pt";

$headers  = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

@mail(NOTIFY_EMAIL, $subject, $body, $headers);

/* ── Also send confirmation to the guest ── */
$guestSubject = "Confirmação recebida — Casamento Liliana & Luís 💜";
$guestBody = "Olá {$name},\n\n"
           . "Recebemos a sua confirmação. Muito obrigado!\n\n"
           . "📅 09 de Agosto de 2026, às 11h00\n"
           . "⛪ Igreja Matriz De Vila Cã\n"
           . "🍽️  Quinta Do Ti Lucas\n\n"
           . "Qualquer dúvida, contacte-nos:\n"
           . "Liliana: 917 422 003\n"
           . "Luís: 919 778 765\n\n"
           . "Até breve!\nLiliana & Luís 💜";

$guestHeaders  = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
$guestHeaders .= "Content-Type: text/plain; charset=UTF-8\r\n";

@mail($email, $guestSubject, $guestBody, $guestHeaders);

/* ── Success ── */
echo json_encode(['ok' => true]);
