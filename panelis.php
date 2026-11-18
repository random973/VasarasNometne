<?php
/* ============================================================
   panelis.php — AIZSARGĀTĀ lapa
   Šeit drīkst tikai ielogojies lietotājs. Ja sesijā nav
   lietotāja -> MET ATPAKAĻ uz login lapu.
   ============================================================ */

session_start();

// Sargs: ja neesi ielogojies, atpakaļ uz login.
if (empty($_SESSION['lietotajs'])) {
    header('Location: login_dross.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="utf-8">
    <title>Panelis</title>
    <style>
        body { font-family: sans-serif; max-width: 480px; margin: 40px auto; }
    </style>
</head>
<body>
    <h2>✅ Aizsargātā lapa</h2>
    <p>Sveiks, <b><?= htmlspecialchars($_SESSION['lietotajs']) ?></b>!</p>
    <p>Šo lapu redz tikai ielogojies lietotājs.</p>
    <p><a href="iziet.php">Iziet</a></p>
</body>
</html>
