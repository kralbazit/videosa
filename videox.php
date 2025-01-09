<?php
// Hata raporlamayı etkinleştir
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// hash parametresini kontrol et
if (!isset($_GET['hash']) || empty($_GET['hash'])) {
    die('Hatalı veya eksik hash parametresi.');
}

// Gelen hash'i çöz
$hashedVideoLink = $_GET['hash'];
$videoLink = base64_decode($hashedVideoLink);

// Geçerli bir URL olup olmadığını kontrol et
if (filter_var($videoLink, FILTER_VALIDATE_URL) === false) {
    die('Geçersiz video bağlantısı.');
}

// Tarayıcıdan gelen Range isteğini al
$range = isset($_SERVER['HTTP_RANGE']) ? $_SERVER['HTTP_RANGE'] : null;

// Video boyutunu belirlemek için HEAD isteği yap
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $videoLink);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOBODY, true); // Yalnızca başlıkları al
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_exec($ch);
$contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
curl_close($ch);

// Başlıkları ayarla
header('Content-Type: video/mp4');
header('Accept-Ranges: bytes');

// Eğer bir Range başlığı varsa, aralıkları işle
if ($range) {
    // Range başlığını ayrıştır
    list(, $range) = explode('=', $range, 2);
    list($start, $end) = explode('-', $range, 2);

    $start = intval($start);
    $end = $end === '' ? $contentLength - 1 : intval($end);

    // Tarayıcıya Content-Range başlığını gönder
    header("HTTP/1.1 206 Partial Content");
    header("Content-Range: bytes $start-$end/$contentLength");
    header("Content-Length: " . ($end - $start + 1));

    // Videonun istenen kısmını cURL ile çek
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $videoLink);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // Doğrudan çıktı
    curl_setopt($ch, CURLOPT_RANGE, "$start-$end");
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $fp = fopen('php://output', 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
} else {
    // Range yoksa, tüm videoyu gönder
    header("Content-Length: $contentLength");

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $videoLink);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // Doğrudan çıktı
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $fp = fopen('php://output', 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
}

exit;
