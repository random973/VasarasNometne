<?php
/* ============================================================
   iziet.php — izlogošanās (logout)
   Iztukšo sesiju un met atpakaļ uz login lapu.
   ============================================================ */

session_start();
$_SESSION = [];        // notīra sesijas datus
session_destroy();     // iznīcina sesiju

header('Location: login_dross.php');
exit;
