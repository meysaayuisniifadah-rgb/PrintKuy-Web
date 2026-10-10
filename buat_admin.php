<?php
require_once "koneksi.php";

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nama = trim($_POST["nama"] ?? "");
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($nama === "" || $username === "" || strlen($password) < 8) {
        $pesan = "Lengkapi data. Password minimal 8 karakter.";
    } else {
        $cek = mysqli_query($conn, "SELECT id FROM admin LIMIT 1");

        if (mysqli_num_rows($cek) > 0) {
            $pesan = "Akun admin sudah ada. Halaman ini tidak bisa digunakan lagi.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO admin (nama, username, password)
                 VALUES (?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sss",
                $nama,
                $username,
                $hash
            );

            if (mysqli_stmt_execute($stmt)) {
                $pesan = "Admin berhasil dibuat. Hapus file buat_admin.php sekarang.";
            } else {
                $pesan = "Gagal membuat admin. Username mungkin sudah digunakan.";
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Admin - PrintKuy</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<main class="form-pemesanan">
    <div class="form-heading">
        <h1>Buat Admin PrintKuy</h1>
        <p>Buat akun administrator pertama.</p>
    </div>

    <?php if ($pesan !== ""): ?>
        <p class="pesan-sukses">
            <?= htmlspecialchars($pesan) ?>
        </p>
    <?php endif; ?>

    <form method="POST" class="form-card">
        <label for="nama">Nama admin</label>
        <input type="text" id="nama" name="nama" required maxlength="100">

        <label for="username">Username</label>
        <input type="text" id="username" name="username"
               required maxlength="50" autocomplete="username">

        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               required minlength="8" autocomplete="new-password">

        <button type="submit" class="btn-kirim">
            Buat Akun Admin
        </button>
    </form>
</main>

</body>
</html>