<?php
session_start();
require_once "../koneksi.php";

// Pastikan hanya admin yang sudah login bisa masuk
if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

// Proses perubahan status pesanan
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $pesanan_id = (int) ($_POST["pesanan_id"] ?? 0);
    $status_baru = $_POST["status"] ?? "";

    $status_valid = [
        "Menunggu",
        "Diproses",
        "Selesai",
        "Dibatalkan"
    ];

    if ($pesanan_id > 0 && in_array($status_baru, $status_valid, true)) {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE pesanan SET status = ? WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "si",
            $status_baru,
            $pesanan_id
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Location: dashboard.php?berhasil=1");
        exit;
    } else {
        $error = "Data perubahan status tidak valid.";
    }
}

// Statistik pesanan
$query_statistik = mysqli_query(
    $conn,
    "SELECT
        COUNT(*) AS total,
        SUM(status = 'Menunggu') AS menunggu,
        SUM(status = 'Diproses') AS diproses,
        SUM(status = 'Selesai') AS selesai
     FROM pesanan"
);

$statistik = mysqli_fetch_assoc($query_statistik);

// Daftar pesanan dan informasi pelanggan
$query_pesanan = mysqli_query(
    $conn,
    "SELECT
        p.id,
        p.kode_pesanan,
        p.ukuran_kertas,
        p.jumlah_halaman,
        p.jumlah_copy,
        p.file_dokumen,
        p.catatan,
        p.total_harga,
        p.status,
        p.created_at,
        pl.nama AS nama_pelanggan,
        pl.no_hp,
        pl.email,
        l.nama_layanan
     FROM pesanan p
     JOIN pelanggan pl ON p.pelanggan_id = pl.id
     JOIN layanan l ON p.layanan_id = l.id
     ORDER BY p.created_at DESC"
);

function e($nilai) {
    return htmlspecialchars((string) ($nilai ?? ""), ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - PrintKuy</title>
    <link rel="stylesheet" href="../style.css">

    <style>
        body {
            background: #f8f5f1;
        }

        .admin-navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 20px 6%;
            background: #6b4a35;
            color: white;
        }

        .admin-navbar h2 {
            font-size: 24px;
        }

        .admin-navbar a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .admin-content {
            max-width: 1400px;
            margin: 35px auto;
            padding: 0 25px;
        }

        .admin-heading {
            margin-bottom: 25px;
        }

        .admin-heading p {
            color: #756b63;
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 4px 18px rgba(70, 45, 25, 0.05);
        }

        .stat-card p {
            color: #756b63;
            font-size: 14px;
        }

        .stat-card h3 {
            margin-top: 8px;
            font-size: 28px;
            color: #6b4a35;
        }

        .table-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(70, 45, 25, 0.05);
        }

        .table-card h2 {
            margin-bottom: 18px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .table-pesanan {
            width: 100%;
            border-collapse: collapse;
            min-width: 1050px;
        }

        .table-pesanan th,
        .table-pesanan td {
            text-align: left;
            padding: 13px 12px;
            border-bottom: 1px solid #eee5dd;
            font-size: 13px;
            vertical-align: top;
        }

        .table-pesanan th {
            background: #f8f5f1;
            color: #6b4a35;
        }

        .table-pesanan select {
            padding: 8px;
            border: 1px solid #ded5cc;
            border-radius: 6px;
            margin-bottom: 7px;
        }

        .btn-update {
            display: inline-block;
            border: none;
            border-radius: 6px;
            padding: 8px 11px;
            color: white;
            background: #6b4a35;
            cursor: pointer;
        }

        .btn-update:hover {
            background: #8a5c3b;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            background: #f2e7d8;
            color: #6b4a35;
            font-size: 12px;
            font-weight: bold;
        }

        .notifikasi {
            background: #e5f4e7;
            color: #246b35;
            border-radius: 8px;
            padding: 13px;
            margin-bottom: 20px;
        }

        .notifikasi-error {
            background: #fce8e8;
            color: #a32929;
            border-radius: 8px;
            padding: 13px;
            margin-bottom: 20px;
        }

        .kosong {
            padding: 25px;
            text-align: center;
            color: #756b63;
        }

        .admin-footer {
            padding: 25px;
            text-align: center;
            color: #756b63;
            font-size: 13px;
        }

        @media (max-width: 850px) {
            .stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 500px) {
            .stat-grid {
                grid-template-columns: 1fr;
            }

            .admin-content {
                padding: 0 14px;
            }

            .table-card {
                padding: 15px;
            }
        }
    </style>
</head>
<body>

<header class="admin-navbar">
    <h2>PrintKuy Admin</h2>
    <div>
        <span>Halo, <?= e($_SESSION["admin_nama"] ?? "Admin") ?></span>
        &nbsp; | &nbsp;
        <a href="logout.php">Logout</a>
    </div>
</header>

<main class="admin-content">

    <div class="admin-heading">
        <h1>Dashboard</h1>
        <p>Kelola pesanan cetak dokumen pelanggan.</p>
    </div>

    <?php if (isset($_GET["berhasil"])): ?>
        <div class="notifikasi">
            Status pesanan berhasil diperbarui.
        </div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="notifikasi-error">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <section class="stat-grid">
        <div class="stat-card">
            <p>Total Pesanan</p>
            <h3><?= (int) ($statistik["total"] ?? 0) ?></h3>
        </div>

        <div class="stat-card">
            <p>Menunggu</p>
            <h3><?= (int) ($statistik["menunggu"] ?? 0) ?></h3>
        </div>

        <div class="stat-card">
            <p>Sedang Diproses</p>
            <h3><?= (int) ($statistik["diproses"] ?? 0) ?></h3>
        </div>

        <div class="stat-card">
            <p>Selesai</p>
            <h3><?= (int) ($statistik["selesai"] ?? 0) ?></h3>
        </div>
    </section>

    <section class="table-card">
        <h2>Daftar Pesanan Pelanggan</h2>

        <div class="table-wrapper">
            <table class="table-pesanan">
                <thead>
                    <tr>
                        <th>Kode Pesanan</th>
                        <th>Pelanggan</th>
                        <th>Layanan</th>
                        <th>Detail Cetak</th>
                        <th>Total Harga</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Ubah Status</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($query_pesanan && mysqli_num_rows($query_pesanan) > 0): ?>
                    <?php while ($p = mysqli_fetch_assoc($query_pesanan)): ?>
                        <tr>
                            <td>
                                <strong><?= e($p["kode_pesanan"]) ?></strong>
                                <br>
                                <small><?= e($p["file_dokumen"]) ?></small>
                            </td>

                            <td>
                                <?= e($p["nama_pelanggan"]) ?>
                                <br><?= e($p["no_hp"]) ?>
                                <?php if (!empty($p["email"])): ?>
                                    <br><?= e($p["email"]) ?>
                                <?php endif; ?>
                            </td>

                            <td><?= e($p["nama_layanan"]) ?></td>

                            <td>
                                Kertas: <?= e($p["ukuran_kertas"]) ?>
                                <br>Halaman: <?= (int) $p["jumlah_halaman"] ?>
                                <br>Salinan: <?= (int) $p["jumlah_copy"] ?>
                                <?php if (!empty($p["catatan"])): ?>
                                    <br>Catatan: <?= e($p["catatan"]) ?>
                                <?php endif; ?>
                            </td>

                            <td>
                                Rp<?= number_format(
                                    (float) $p["total_harga"], 0, ",", "."
                                ) ?>
                            </td>

                            <td><?= e($p["created_at"]) ?></td>

                            <td>
                                <span class="status">
                                    <?= e($p["status"]) ?>
                                </span>
                            </td>

                            <td>
                                <form method="POST">
                                    <input type="hidden" name="pesanan_id"
                                           value="<?= (int) $p["id"] ?>">

                                    <select name="status" required>
                                        <?php foreach (
                                            ["Menunggu", "Diproses", "Selesai", "Dibatalkan"]
                                            as $status
                                        ): ?>
                                            <option value="<?= e($status) ?>"
                                                <?= $p["status"] === $status
                                                    ? "selected" : "" ?>>
                                                <?= e($status) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button type="submit" class="btn-update">
                                        Simpan
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="kosong">
                            Belum ada pesanan dari pelanggan.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

<footer class="admin-footer">
    &copy; <?= date("Y") ?> PrintKuy Admin
</footer>

</body>
</html>
