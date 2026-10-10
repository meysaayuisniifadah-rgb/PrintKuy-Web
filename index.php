<?php
require_once "koneksi.php";

$query = mysqli_query($conn, "SELECT * FROM layanan WHERE aktif = 1");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PrintKuy - Jasa Print Online</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="navbar">
    <div class="logo">PrintKuy<span>.</span></div>

    <nav>
        <a href="index.php">Beranda</a>
        <a href="#layanan">Layanan</a>
        <a href="#cara-pesan">Cara Pesan</a>
        <a href="admin/login.php" class="btn-admin">Login Admin</a>
    </nav>
</header>

<section class="hero">
    <div class="hero-text">
        <p class="label">LAYANAN PRINT ONLINE</p>

        <h1>Print Dokumen Jadi Lebih <span>Mudah!</span></h1>

        <p class="deskripsi">
            Cetak dokumen hitam putih maupun berwarna dengan mudah.
            Unggah file PDF dan lakukan pemesanan langsung melalui website.
        </p>

        <a href="pesan.php" class="btn-utama">Pesan Sekarang</a>
    </div>

    <div class="hero-card">
        <div class="icon-print">📄</div>
        <h3>Print Tanpa Ribet</h3>
        <p>Unggah dokumen, pilih layanan, dan kirim pesanan secara online.</p>
    </div>
</section>

<section class="layanan" id="layanan">
    <div class="section-title">
        <p class="label">LAYANAN KAMI</p>
        <h2>Pilih Layanan Print</h2>
        <p>Sesuaikan layanan dengan kebutuhan dokumenmu.</p>
    </div>

    <div class="layanan-container">
        <?php if ($query && mysqli_num_rows($query) > 0): ?>
            <?php while ($layanan = mysqli_fetch_assoc($query)): ?>
                <div class="layanan-card">
                    <div class="layanan-icon">🖨️</div>

                    <h3>
                        <?= htmlspecialchars($layanan['nama_layanan']) ?>
                    </h3>

                    <p>
                        <?= htmlspecialchars($layanan['deskripsi'] ?? '') ?>
                    </p>

                    <h4>
                        Rp<?= number_format((float)$layanan['harga_per_halaman'], 0, ',', '.') ?>
                        <small>/ halaman</small>
                    </h4>

                    <a href="pesan.php" class="btn-pesan">Pesan Layanan</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Layanan belum tersedia. Silakan tambahkan layanan di database.</p>
        <?php endif; ?>
    </div>
</section>

<section class="cara-pesan" id="cara-pesan">
    <div class="section-title">
        <p class="label">LANGKAH MUDAH</p>
        <h2>Cara Melakukan Pemesanan</h2>
    </div>

    <div class="langkah-container">
        <div class="langkah">
            <span>01</span>
            <h3>Pilih Layanan</h3>
            <p>Pilih jenis print yang kamu butuhkan.</p>
        </div>

        <div class="langkah">
            <span>02</span>
            <h3>Unggah Dokumen</h3>
            <p>Isi data pemesan dan unggah file PDF.</p>
        </div>

        <div class="langkah">
            <span>03</span>
            <h3>Kirim Pesanan</h3>
            <p>Pesanan akan tersimpan untuk diproses admin.</p>
        </div>
    </div>
</section>

<footer>
    <h3>PrintKuy.</h3>
    <p>Solusi cetak dokumen yang praktis dan mudah.</p>
    <p>&copy; <?= date('Y') ?> PrintKuy. Semua hak dilindungi.</p>
</footer>

</body>
</html>