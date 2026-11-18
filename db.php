<?php
/* ============================================================
   db.php — savienojums ar datubāzi
   ------------------------------------------------------------
   Konfigurāciju ielasa no secrets.php, kas atrodas BLAKUS
   (__DIR__ = šī faila mape), tāpēc strādā neatkarīgi no tā,
   no kuras skripta db.php tiek iekļauts.

   Lietošana citos failos:
       require __DIR__ . '/db.php';
       // tālāk pieejams $conn
   ============================================================ */

$config = require __DIR__ . '/secrets.php';

$conn = mysqli_connect(
    $config['host'],
    $config['user'],
    $config['pass'],
    $config['db']
);

if (!$conn) {
    // Reālā projektā kļūdu labāk ierakstīt logā, nevis rādīt lietotājam.
    die('Savienojuma kļūda ar datubāzi.');
}

mysqli_set_charset($conn, 'utf8mb4');
