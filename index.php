<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
    $originalUrl = filter_var($_POST['url'], FILTER_SANITIZE_URL);
    
    if (!filter_var($originalUrl, FILTER_VALIDATE_URL)) {
        $error = "URL tidak valid!";
    } else {
        // Generate random slug
        $slug = substr(md5(uniqid()), 0, 8);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO tracked_links (original_url, slug) VALUES (?, ?)");
            $stmt->execute([$originalUrl, $slug]);
            
            $trackingUrl = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            $trackingUrl = dirname($trackingUrl) . '/track.php?slug=' . $slug;
            $success = "Link pelacakan berhasil dibuat!";
        } catch (PDOException $e) {
            $error = "Gagal membuat link pelacakan: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IP Location Tracker</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <h1>IP Location Tracker</h1>
        <p>Buat link untuk melacak lokasi pengguna yang mengkliknya</p>
        
        <?php if (isset($error)): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php elseif (isset($success)): ?>
            <div class="alert success"><?php echo $success; ?></div>
            <div class="tracking-link">
                <input type="text" value="<?php echo $trackingUrl; ?>" readonly>
                <button onclick="copyToClipboard()">Salin Link</button>
            </div>
            <a href="view.php?slug=<?php echo $slug; ?>" class="view-btn">Lihat Data Pelacakan</a>
        <?php endif; ?>
        
        <form method="POST">
            <input type="url" name="url" placeholder="Masukkan URL tujuan" required>
            <button type="submit">Buat Link Pelacakan</button>
        </form>
    </div>

    <script>
        function copyToClipboard() {
            const copyText = document.querySelector(".tracking-link input");
            copyText.select();
            document.execCommand("copy");
            alert("Link berhasil disalin!");
        }
    </script>
</body>
</html>