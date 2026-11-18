<?php
// Izvada tikai datubāzē pārkodētos JPEG attēlus.
ini_set('display_errors', '0');

function imageNotFound(): void {
    http_response_code(404);
    header('Cache-Control: no-store');
    header('Content-Type: text/plain; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    exit('Attēls nav pieejams.');
}

$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 4294967295]
]);
if ($id === false) imageNotFound();

$preview = ($_GET['preview'] ?? '') === '1';
if ($preview) {
    session_start();
    $allowed = !empty($_SESSION['lietotajs']);
    session_write_close();
    if (!$allowed) imageNotFound();
}

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    require __DIR__ . '/db.php';
    $sql = 'SELECT g.image_data FROM mm_gallery_images g
            LEFT JOIN mm_camps c ON c.id = g.camp_id
            WHERE g.id = ?';
    if (!$preview) {
        $sql .= ' AND g.is_published = 1
                  AND (g.camp_id IS NULL OR c.is_published = 1)';
    }
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$row) imageNotFound();
    $bytes = $row['image_data'];
    if (strlen($bytes) > 768 * 1024 || substr($bytes, 0, 2) !== "\xFF\xD8" ||
        substr($bytes, -2) !== "\xFF\xD9") imageNotFound();

    header('Content-Type: image/jpeg');
    header('Content-Disposition: inline; filename="nometnes-attels.jpg"');
    header('Content-Length: ' . strlen($bytes));
    header('X-Content-Type-Options: nosniff');
    header("Content-Security-Policy: default-src 'none'; sandbox");
    header('Cross-Origin-Resource-Policy: same-origin');
    // Paslēptie vai dzēstie attēli nākamajā pieprasījumā vairs nav pieejami.
    header('Cache-Control: private, no-store');
    echo $bytes;
} catch (Throwable $e) {
    error_log('Galerijas attēla nolasīšana neizdevās.');
    imageNotFound();
}
