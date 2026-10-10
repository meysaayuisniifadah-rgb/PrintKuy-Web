<?php
session_start();
require_once "../koneksi.php";

if (isset($_SESSION["admin_id"])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, nama, username, password
         FROM admin
         WHERE username = ?
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);
    $admin = mysqli_fetch_assoc($hasil);

    mysqli_stmt_close($stmt);

    if ($admin && password_verify($password, $admin["password"])) {
        session_regenerate_id(true);

        $_SESSION["admin_id"] = $admin["id"];
        $_SESSION["admin_nama"] = $admin["nama"];

        header("Location: dashboard.php");
        exit;
    }

    $error = "Username atau password salah.";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - PrintKuy</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

<main class="form-pemesanan">
    <div class="form-heading">
        <p class="label">ADMINISTRATOR</p>
        <h1>Login PrintKuy</h1>
        <p>Masuk untuk mengelola pesanan pelanggan.</p>
    </div>

    <?php if ($error !== ""): ?>
        <div class="pesan-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="form-card">
        <label for="username">Username</label>
        <input type="text" id="username" name="username"
               required autocomplete="username">

        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               required autocomplete="current-password">

        <button type="submit" class="btn-kirim">
            Login
        </button>

        <p style="margin-top: 20px; text-align: center;">
            <a href="../index.php">Kembali ke halaman utama</a>
        </p>
    </form>
</main>

</body>
</html>