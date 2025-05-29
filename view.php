<?php
require_once 'db.php';

if (!isset($_GET['slug'])) {
    header("Location: index.php");
    exit();
}

$slug = $_GET['slug'];

// Dapatkan informasi link
$stmt = $pdo->prepare("SELECT id, original_url, created_at FROM tracked_links WHERE slug = ?");
$stmt->execute([$slug]);
$link = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$link) {
    die("Link tidak ditemukan!");
}

// Dapatkan semua klik untuk link ini
$stmt = $pdo->prepare("SELECT * FROM link_clicks WHERE link_id = ? ORDER BY clicked_at DESC");
$stmt->execute([$link['id']]);
$clicks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pelacakan</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY"></script>
</head>
<body>
    <div class="container">
        <h1>Data Pelacakan</h1>
        <p>URL Tujuan: <a href="<?php echo $link['original_url']; ?>" target="_blank"><?php echo $link['original_url']; ?></a></p>
        <p>Link Pelacakan: <?php echo (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/track.php?slug=$slug"; ?></p>
        <p>Dibuat pada: <?php echo date('d M Y H:i', strtotime($link['created_at'])); ?></p>
        
        <h2>Daftar Klik (<?php echo count($clicks); ?>)</h2>
        
        <div class="clicks-container">
            <div class="clicks-list">
                <table>
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>IP Address</th>
                            <th>Lokasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clicks as $click): ?>
                            <tr>
                                <td><?php echo date('d M Y H:i', strtotime($click['clicked_at'])); ?></td>
                                <td><?php echo $click['ip_address']; ?></td>
                                <td>
                                    <?php 
                                    echo ($click['city'] ? $click['city'] . ', ' : '') . 
                                         ($click['region'] ? $click['region'] . ', ' : '') . 
                                         ($click['country'] ?? '');
                                    ?>
                                </td>
                                <td><button onclick="showOnMap(<?php echo $click['latitude']; ?>, <?php echo $click['longitude']; ?>, '<?php echo date('d M Y H:i', strtotime($click['clicked_at'])); ?>')">Lihat di Peta</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="map-container">
                <div id="map"></div>
                <div id="map-info"></div>
            </div>
        </div>
        
        <a href="index.php" class="back-btn">Kembali</a>
    </div>

    <script src="assets/script.js"></script>
    <script>
        let map;
        let marker;
        
        // Inisialisasi peta
        function initMap() {
            map = new google.maps.Map(document.getElementById('map'), {
                center: {lat: 0, lng: 0},
                zoom: 2
            });
        }
        
        // Tampilkan lokasi pada peta
        function showOnMap(lat, lng, info) {
            if (map) {
                const position = {lat: parseFloat(lat), lng: parseFloat(lng)};
                
                // Hapus marker sebelumnya jika ada
                if (marker) {
                    marker.setMap(null);
                }
                
                // Buat marker baru
                marker = new google.maps.Marker({
                    position: position,
                    map: map,
                    title: 'Lokasi Pengunjung'
                });
                
                // Pindahkan pusat peta
                map.setCenter(position);
                map.setZoom(10);
                
                // Tampilkan info
                document.getElementById('map-info').innerHTML = `<p><strong>Waktu:</strong> ${info}</p><p><strong>Koordinat:</strong> ${lat}, ${lng}</p>`;
            }
        }
        
        // Jalankan initMap saat halaman dimuat
        window.onload = initMap;
    </script>
</body>
</html>