<?php
require_once 'db.php';

if (!isset($_GET['slug'])) {
    header("Location: index.php");
    exit();
}

$slug = $_GET['slug'];

// Cari URL asli berdasarkan slug
$stmt = $pdo->prepare("SELECT id, original_url FROM tracked_links WHERE slug = ?");
$stmt->execute([$slug]);
$link = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$link) {
    die("Link tidak ditemukan!");
}

// Dapatkan data IP dan lokasi pengguna
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
$ip = explode(',', $ip)[0]; // Handle multiple IPs if behind proxy

// Gunakan IP-API untuk mendapatkan lokasi
$locationData = json_decode(file_get_contents("http://ip-api.com/json/{$ip}"), true);

// Simpan data klik ke database
try {
    $stmt = $pdo->prepare("INSERT INTO link_clicks (link_id, ip_address, country, region, city, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $link['id'],
        $ip,
        $locationData['country'] ?? null,
        $locationData['regionName'] ?? null,
        $locationData['city'] ?? null,
        $locationData['lat'] ?? null,
        $locationData['lon'] ?? null
    ]);
} catch (PDOException $e) {
    // Tetap lanjutkan redirect meskipun gagal menyimpan
}

// Redirect ke URL asli
header("Location: " . $link['original_url']);
exit();
?>