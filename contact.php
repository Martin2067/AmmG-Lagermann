<?php
declare(strict_types=1);

$languages = [
    'cs' => ['path' => '/', 'label' => 'čeština (cs)'],
    'en' => ['path' => '/en/', 'label' => 'angličtina (en)'],
    'de' => ['path' => '/de/', 'label' => 'němčina (de)'],
];

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ' . $languages['cs']['path'], true, 303);
    exit;
}

$requestedLanguage = $_POST['lang'] ?? '';
$language = is_string($requestedLanguage) && isset($languages[$requestedLanguage])
    ? $languages[$requestedLanguage]
    : $languages['cs'];

function redirectToForm(string $path, string $result): void
{
    header('Location: ' . $path . $result, true, 303);
    exit;
}

$success = '?sent=1#contact-sent';
$invalid = '?error=1#contact-invalid';

if (!empty($_POST['website'] ?? '')) {
    redirectToForm($language['path'], $success);
}

$fields = [];
foreach (['name', 'company', 'email', 'phone', 'warehouse_size', 'request_type', 'message', 'gdpr'] as $field) {
    $value = $_POST[$field] ?? '';
    if (!is_string($value)) {
        redirectToForm($language['path'], $invalid);
    }
    $fields[$field] = trim($value);
}

$name = $fields['name'];
$company = $fields['company'];
$email = $fields['email'];
$phone = $fields['phone'];
$message = $fields['message'];

if (
    $name === '' || $message === '' || $fields['gdpr'] !== 'on'
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || preg_match('/[\r\n]/', $email) || preg_match('/[\r\n]/', $name)
) {
    redirectToForm($language['path'], $invalid);
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

$warehouseSize = $fields['warehouse_size'];
$requestType = $fields['request_type'];
if (
    ($warehouseSize !== '' && !isset($warehouseSizes[$warehouseSize]))
    || ($requestType !== '' && !isset($requestTypes[$requestType]))
) {
    redirectToForm($language['path'], $invalid);
}

$warehouseSizeLabel = $warehouseSizes[$warehouseSize] ?? 'Neuvedeno';
$requestTypeLabel   = $requestTypes[$requestType] ?? 'Neuvedeno';

/*
|--------------------------------------------------------------------------
| Sestavení e-mailu
|--------------------------------------------------------------------------
*/

$to      = 'info@ammg.cz';
$subject = '=?UTF-8?B?' . base64_encode('Nová poptávka z webu AmmG') . '?=';

$body  = "NOVÁ POPTÁVKA Z WEBU AMMG\n";
$body .= "================================\n\n";

$body .= "Jméno: {$name}\n";
$body .= "Firma: " . ($company !== '' ? $company : 'Neuvedeno') . "\n";
$body .= "E-mail: {$email}\n";
$body .= "Telefon: " . ($phone !== '' ? $phone : 'Neuvedeno') . "\n\n";

$body .= "Jazyk poptávky: {$language['label']}\n";
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

$sent = @mail(
    $to,
    $subject,
    $body,
    implode("\r\n", $headers)
);

if (!$sent) {
    error_log('AmmG contact: mail() failed');
    redirectToForm($language['path'], '?error=mail#contact-error');
}

redirectToForm($language['path'], $success);
