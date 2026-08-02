<?php
declare(strict_types=1);

// contact.php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

/*
|--------------------------------------------------------------------------
| Honeypot ochrana proti robotům
|--------------------------------------------------------------------------
*/

if (!empty($_POST['website'] ?? '')) {
    header('Location: index.html?sent=1');
    exit;
}

/*
|--------------------------------------------------------------------------
| Načtení formulářových údajů
|--------------------------------------------------------------------------
*/

$name          = trim($_POST['name'] ?? '');
$company       = trim($_POST['company'] ?? '');
$email         = trim($_POST['email'] ?? '');
$phone         = trim($_POST['phone'] ?? '');
$warehouseSize = trim($_POST['warehouse_size'] ?? '');
$requestType   = trim($_POST['request_type'] ?? '');
$message       = trim($_POST['message'] ?? '');

/*
|--------------------------------------------------------------------------
| Kontrola povinných údajů
|--------------------------------------------------------------------------
*/

if ($name === '' || $email === '' || $message === '') {
    header('Location: index.html?error=1#kontakt');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: index.html?error=1#kontakt');
    exit;
}

/*
|--------------------------------------------------------------------------
| Ochrana hlavičky Reply-To
|--------------------------------------------------------------------------
*/

if (
    preg_match('/[\r\n]/', $email) ||
    preg_match('/[\r\n]/', $name)
) {
    header('Location: index.html?error=1#kontakt');
    exit;
}

/*
|--------------------------------------------------------------------------
| Převod interních hodnot na čitelné názvy
|--------------------------------------------------------------------------
*/

$warehouseSizes = [
    'do-500'    => 'Do 500 m²',
    '500-2000'  => '500–2 000 m²',
    'nad-2000'  => 'Více než 2 000 m²',
    'neznam'    => 'Zatím nevím',
];

$requestTypes = [
    'kompletni-reseni' => 'Kompletní řešení skladu',
    'navrh'             => 'Návrh a optimalizace',
    'regaly'            => 'Regálové systémy',
    'montaz'            => 'Montáž a instalace',
    'lokace'            => 'Lokace, štítky a polepy',
    'znaceni'           => 'Podlahové značení',
    'revize'            => 'Revize a servis',
    'lagermann'         => 'Lagermann WMS',
    'jine'              => 'Jiné řešení',
];

$warehouseSizeLabel = $warehouseSizes[$warehouseSize] ?? 'Neuvedeno';
$requestTypeLabel   = $requestTypes[$requestType] ?? 'Neuvedeno';

/*
|--------------------------------------------------------------------------
| Sestavení e-mailu
|--------------------------------------------------------------------------
*/

$to      = 'info@ammg.cz';
$subject = 'Nová poptávka z webu AmmG';

$body  = "NOVÁ POPTÁVKA Z WEBU AMMG\n";
$body .= "================================\n\n";

$body .= "Jméno: {$name}\n";
$body .= "Firma: " . ($company !== '' ? $company : 'Neuvedeno') . "\n";
$body .= "E-mail: {$email}\n";
$body .= "Telefon: " . ($phone !== '' ? $phone : 'Neuvedeno') . "\n\n";

$body .= "Velikost skladu: {$warehouseSizeLabel}\n";
$body .= "Typ požadavku: {$requestTypeLabel}\n\n";

$body .= "--------------------------------\n";
$body .= "Zpráva:\n";
$body .= "{$message}\n";

/*
|--------------------------------------------------------------------------
| Hlavičky e-mailu
|--------------------------------------------------------------------------
*/

$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'From: AmmG web <no-reply@ammg.cz>',
    'Reply-To: ' . $email,
    'X-Mailer: PHP/' . phpversion(),
];

/*
|--------------------------------------------------------------------------
| Odeslání
|--------------------------------------------------------------------------
*/

$sent = mail(
    $to,
    $subject,
    $body,
    implode("\r\n", $headers)
);

if (!$sent) {
    header('Location: index.html?error=mail#kontakt');
    exit;
}

header('Location: index.html?sent=1#kontakt');
exit;