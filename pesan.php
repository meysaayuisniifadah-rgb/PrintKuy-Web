<?php
require_once "koneksi.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = trim($_POST["nama"] ?? "");
    $no_hp = trim($_POST["no_hp"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $layanan_id = (int) ($_POST["layanan_id"] ?? 0);
    $ukuran_kertas = $_POST["ukuran_kertas"] ?? "A4";
    $jumlah_halaman = (int) ($_POST["jumlah_halaman"] ?? 1);
    $jumlah_copy = (int) ($_POST["jumlah_copy"] ?? 1);
    $catatan = trim($_POST["catatan"] ?? "");

    $ukuran_valid = ["A4", "F4", "A3"];

    if (
        $nama === "" ||
        $no_hp === "" ||
        $layanan_id < 1 ||
        $jumlah_halaman < 1 ||
        $jumlah_copy < 1 ||
        !in_array($ukuran_kertas, $ukuran_valid, true)
    ) {
        $error = "Mohon lengkapi data pemesanan dengan benar.";
    } elseif (
        !isset($_FILES["file_dokumen"]) ||
        $_FILES["file_dokumen"]["error"] !== UPLOAD_ERR_OK
    ) {
        $error = "Silakan pilih file PDF yang akan dicetak.";
    } elseif ($_FILES["file_dokumen"]["size"] > 5 * 1024 * 1024) {
        $error = "Ukuran file maksimal 5 MB.";
    } else {
        $nama_asli = $_FILES["file_dokumen"]["name"];
        $ekstensi = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES["file_dokumen"]["tmp_name"]);

        if ($ekstensi !== "pdf" || $mime !== "application/pdf") {
            $error = "File harus berupa PDF yang valid.";
        } else {
            $stmt = mysqli_prepare(
                $conn,
                "SELECT harga_per_halaman
                 FROM layanan
                 WHERE id = ? AND aktif = 1"
            );

            mysqli_stmt_bind_param($stmt, "i", $layanan_id);
            mysqli_stmt_execute($stmt);
            $hasil_layanan = mysqli_stmt_get_result($stmt);
            $data_layanan = mysqli_fetch_assoc($hasil_layanan);
            mysqli_stmt_close($stmt);

            if (!$data_layanan) {
                $error = "Layanan tidak tersedia.";
            } else {
                $harga = (float) $data_layanan["harga_per_halaman"];
                $total_harga = $harga * $jumlah_halaman * $jumlah_copy;

                $folder_upload = __DIR__ . "/uploads/";

                if (!is_dir($folder_upload)) {
                    mkdir($folder_upload, 0755, true);
                }

                $nama_file = bin2hex(random_bytes(16)) . ".pdf";
                $tujuan_file = $folder_upload . $nama_file;

                if (!move_uploaded_file(
                    $_FILES["file_dokumen"]["tmp_name"],
                    $tujuan_file
                )) {
                    $error = "File gagal diunggah. Coba lagi.";
                } else {
                    mysqli_begin_transaction($conn);

                    try {
                        $stmt = mysqli_prepare(
                            $conn,
                            "INSERT INTO pelanggan (nama, no_hp, email)
                             VALUES (?, ?, NULLIF(?, ''))"
                        );

                        mysqli_stmt_bind_param(
                            $stmt,
                            "sss",
                            $nama,
                            $no_hp,
                            $email
                        );

                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception("Gagal menyimpan pelanggan.");
                        }

                        $pelanggan_id = mysqli_insert_id($conn);
                        mysqli_stmt_close($stmt);

                        $kode_pesanan = "PKY" . date("YmdHis")
                            . random_int(100, 999);

                        $stmt = mysqli_prepare(
                            $conn,
                            "INSERT INTO pesanan (
                                kode_pesanan,
                                pelanggan_id,
                                layanan_id,
                                ukuran_kertas,
                                jumlah_halaman,
                                jumlah_copy,
                                file_dokumen,
                                catatan,
                                total_harga
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''), ?)"
                        );

                        mysqli_stmt_bind_param(
                            $stmt,
                            "siisiissd",
                            $kode_pesanan,
                            $pelanggan_id,
                            $layanan_id,
                            $ukuran_kertas,
                            $jumlah_halaman,
                            $jumlah_copy,
                            $nama_file,
                            $catatan,
                            $total_harga
                        );

                        if (!mysqli_stmt_execute($stmt)) {
                            throw new Exception("Gagal menyimpan pesanan.");
                        }

                        mysqli_stmt_close($stmt);
                        mysqli_commit($conn);

                        header(
                            "Location: pesan.php?sukses="
                            . urlencode($kode_pesanan)
                        );
                        exit;
                    } catch (Throwable $e) {
                        mysqli_rollback($conn);

                        if (is_file($tujuan_file)) {
                            unlink($tujuan_file);
                        }

                        error_log($e->getMessage());
                        $error = "Pesanan gagal disimpan. Silakan coba lagi.";
                    }
                }
            }
        }
    }
}

$layanan = mysqli_query(
    $conn,
    "SELECT id, nama_layanan, harga_per_halaman
     FROM layanan
     WHERE aktif = 1
     ORDER BY id ASC"
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemesanan - PrintKuy</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="navbar">
    <div class="logo">PrintKuy<span>.</span></div>

    <nav>
        <a href="index.php">Beranda</a>
        <a href="index.php#layanan">Layanan</a>
    </nav>
</header>

<main class="form-pemesanan">
    <div class="form-heading">
        <p class="label">PEMESANAN ONLINE</p>
        <h1>Pesan Layanan Print</h1>
        <p>Isi data berikut dan unggah dokumen PDF kamu.</p>
    </div>

    <?php if (isset($_GET["sukses"])): ?>
        <div class="pesan-sukses">
            <h3>Pesanan berhasil dikirim!</h3>
            <p>Kode pesanan kamu:</p>
            <strong>
                <?= htmlspecialchars($_GET["sukses"]) ?>
            </strong>
            <p>Simpan kode ini untuk mengecek pesananmu.</p>
            <a href="index.php" class="btn-utama">Kembali ke Beranda</a>
        </div>
    <?php else: ?>

        <?php if ($error !== ""): ?>
            <div class="pesan-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data"
              class="form-card">

            <label for="nama">Nama lengkap</label>
            <input type="text" id="nama" name="nama"
                   maxlength="100" required
                   value="<?= htmlspecialchars($_POST["nama"] ?? "") ?>">

            <label for="no_hp">Nomor HP</label>
            <input type="tel" id="no_hp" name="no_hp"
                   maxlength="20" required
                   value="<?= htmlspecialchars($_POST["no_hp"] ?? "") ?>">

            <label for="email">Email (opsional)</label>
            <input type="email" id="email" name="email"
                   maxlength="100"
                   value="<?= htmlspecialchars($_POST["email"] ?? "") ?>">

            <label for="layanan_id">Jenis layanan</label>
            <select id="layanan_id" name="layanan_id" required>
                <option value="">-- Pilih layanan --</option>

                <?php while ($item = mysqli_fetch_assoc($layanan)): ?>
                    <option
                        value="<?= (int) $item["id"] ?>"
                        <?= (int) ($_POST["layanan_id"] ?? 0)
                            === (int) $item["id"] ? "selected" : "" ?>>
                        <?= htmlspecialchars($item["nama_layanan"]) ?>
                        - Rp<?= number_format(
                            (float) $item["harga_per_halaman"],
                            0, ",", "."
                        ) ?>/halaman
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="ukuran_kertas">Ukuran kertas</label>
            <select id="ukuran_kertas" name="ukuran_kertas" required>
                <?php foreach (["A4", "F4", "A3"] as $ukuran): ?>
                    <option value="<?= $ukuran ?>"
                        <?= ($_POST["ukuran_kertas"] ?? "A4") === $ukuran
                            ? "selected" : "" ?>>
                        <?= $ukuran ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="form-dua-kolom">
                <div>
                    <label for="jumlah_halaman">Jumlah halaman</label>
                    <input type="number" id="jumlah_halaman"
                           name="jumlah_halaman" min="1" max="10000"
                           value="<?= max(1, (int) ($_POST["jumlah_halaman"] ?? 1)) ?>"
                           required>
                </div>

                <div>
                    <label for="jumlah_copy">Jumlah salinan</label>
                    <input type="number" id="jumlah_copy"
                           name="jumlah_copy" min="1" max="1000"
                           value="<?= max(1, (int) ($_POST["jumlah_copy"] ?? 1)) ?>"
                           required>
                </div>
            </div>

            <label for="file_dokumen">Dokumen PDF (maksimal 5 MB)</label>
            <input type="file" id="file_dokumen"
                   name="file_dokumen" accept=".pdf,application/pdf"
                   required>

            <label for="catatan">Catatan tambahan (opsional)</label>
            <textarea id="catatan" name="catatan" rows="4"
                      placeholder="Contoh: dokumen dijilid atau instruksi lainnya"><?= htmlspecialchars($_POST["catatan"] ?? "") ?></textarea>

            <button type="submit" class="btn-kirim">
                Kirim Pesanan
            </button>

            <p class="catatan-form">
                Total biaya dihitung berdasarkan harga per halaman,
                jumlah halaman, dan jumlah salinan.
            </p>
        </form>
    <?php endif; ?>
</main>

<footer>
    <h3>PrintKuy.</h3>
    <p>Solusi cetak dokumen yang praktis dan mudah.</p>
</footer>

</body>
</html>