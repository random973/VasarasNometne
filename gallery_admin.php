<?php
ini_set('display_errors', '0');
session_start();
header('Cache-Control: no-store');
if (empty($_SESSION['lietotajs'])) {
    header('Location: index.php');
    exit;
}
if (empty($_SESSION['mm_gallery_csrf'])) {
    $_SESSION['mm_gallery_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['mm_gallery_csrf'];
require __DIR__ . '/includes/gallery_upload.php';

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function galleryId($value): int {
    $id = filter_var($value, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 4294967295]
    ]);
    if ($id === false) throw new GalleryValidationException('Nederīgs ieraksta ID.');
    return $id;
}
function galleryCamp($conn, $value): ?int {
    if ($value === '') return null;
    $id = galleryId($value);
    $stmt = mysqli_prepare($conn, 'SELECT id FROM mm_camps WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$row) throw new GalleryValidationException('Izvēlētā nometne neeksistē.');
    return $id;
}
function galleryLock($conn): void {
    $row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT GET_LOCK(SHA2(CONCAT(DATABASE(), ':mm_gallery_upload'), 256), 5) AS acquired"));
    if ((int)($row['acquired'] ?? 0) !== 1) {
        throw new GalleryValidationException('Galerija pašlaik tiek papildināta. Mēģini vēlreiz.');
    }
}
function galleryUnlock($conn): void {
    mysqli_query($conn, "SELECT RELEASE_LOCK(SHA2(CONCAT(DATABASE(), ':mm_gallery_upload'), 256))");
}

$error = '';
$message = '';
$ready = false;
$camps = [];
$images = [];
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    require __DIR__ . '/db.php';
    // Pārbauda, vai jaunā tabula izveidota, pirms veic jebkādas izmaiņas.
    mysqli_query($conn, 'SELECT id FROM mm_gallery_images LIMIT 1');
    $ready = true;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (empty($_POST) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            throw new GalleryValidationException('Pieprasījums pārsniedz servera izmēra limitu vai nav derīgs.');
        }
        if (!is_string($_POST['csrf'] ?? null) || !hash_equals($csrf, $_POST['csrf'])) {
            http_response_code(403);
            exit('Nederīgs drošības tokens. Pārlādē lapu.');
        }
        $action = $_POST['action'] ?? '';
        if ($action === 'upload') {
            $alt = galleryAlt($_POST['alt_text'] ?? null);
            $campId = galleryCamp($conn, $_POST['camp_id'] ?? '');
            $image = galleryProcessUpload($_FILES['image'] ?? null);
            // Bloķējums novērš limita pārsniegšanu paralēlās augšupielādēs.
            galleryLock($conn);
            try {
                $count = mysqli_fetch_assoc(mysqli_query($conn, 'SELECT COUNT(*) AS total FROM mm_gallery_images'));
                if ((int)$count['total'] >= GALLERY_MAX_IMAGES) {
                    throw new GalleryValidationException('Sasniegts 60 attēlu limits. Vispirms dzēs nevajadzīgus attēlus.');
                }
                $stmt = mysqli_prepare($conn, 'INSERT INTO mm_gallery_images
                    (camp_id, alt_text, image_data, width, height) VALUES (?, ?, ?, ?, ?)');
                $binary = null;
                mysqli_stmt_bind_param($stmt, 'isbii', $campId, $alt, $binary, $image['width'], $image['height']);
                foreach (str_split($image['bytes'], 65536) as $chunk) {
                    mysqli_stmt_send_long_data($stmt, 2, $chunk);
                }
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            } finally {
                galleryUnlock($conn);
            }
            $_SESSION['mm_gallery_flash'] = 'Attēls augšupielādēts kā melnraksts.';
        } elseif ($action === 'save') {
            $id = galleryId($_POST['image_id'] ?? null);
            $alt = galleryAlt($_POST['alt_text'] ?? null);
            $campId = galleryCamp($conn, $_POST['camp_id'] ?? '');
            $published = ($_POST['is_published'] ?? '') === '1' ? 1 : 0;
            if ($published && ($_POST['rights_confirmed'] ?? '') !== '1') {
                throw new GalleryValidationException('Pirms publicēšanas apstiprini tiesības publicēt šo attēlu.');
            }
            $stmt = mysqli_prepare($conn, 'UPDATE mm_gallery_images
                SET alt_text = ?, camp_id = ?, is_published = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'siii', $alt, $campId, $published, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $_SESSION['mm_gallery_flash'] = 'Attēla informācija saglabāta.';
        } elseif ($action === 'delete') {
            $id = galleryId($_POST['image_id'] ?? null);
            $stmt = mysqli_prepare($conn, 'DELETE FROM mm_gallery_images WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $_SESSION['mm_gallery_flash'] = 'Attēls dzēsts.';
        } else {
            throw new GalleryValidationException('Nederīga darbība.');
        }
        header('Location: gallery_admin.php', true, 303);
        exit;
    }
} catch (GalleryValidationException $e) {
    $error = $e->getMessage();
} catch (Throwable $e) {
    error_log('Galerijas darbība neizdevās: ' . get_class($e));
    $error = $ready ? 'Darbību neizdevās pabeigt.' :
        'Galerijas datubāze nav gatava. Pārbaudi savienojumu un izpildi database/001_gallery.sql.';
}

if ($ready) {
    try {
        $result = mysqli_query($conn, 'SELECT id, title, is_published FROM mm_camps ORDER BY start_date, id');
        while ($row = mysqli_fetch_assoc($result)) $camps[] = $row;
        $result = mysqli_query($conn, 'SELECT id, camp_id, alt_text, width, height, is_published
            FROM mm_gallery_images ORDER BY id DESC');
        // Neielādējam BLOB laukus galerijas pārvaldības sarakstā.
        while ($row = mysqli_fetch_assoc($result)) $images[] = $row;
    } catch (Throwable $e) {
        $ready = false;
        $error = 'Galerijas sarakstu neizdevās ielādēt.';
        error_log('Galerijas saraksta kļūda.');
    }
}
$message = $_SESSION['mm_gallery_flash'] ?? '';
unset($_SESSION['mm_gallery_flash']);
$uploadAlt = ($_POST['action'] ?? '') === 'upload' && is_string($_POST['alt_text'] ?? null) ? $_POST['alt_text'] : '';
$uploadCamp = ($_POST['action'] ?? '') === 'upload' && is_string($_POST['camp_id'] ?? null)
    ? $_POST['camp_id'] : (is_string($_GET['camp'] ?? null) ? $_GET['camp'] : '');
?>
<!DOCTYPE html>
<html lang="lv">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Galerijas pārvaldība | Vasaras nometnes</title>
<style>
:root{--ink:#24242b;--muted:#626773;--line:#e9e5e1;--bg:#faf7f3;--red:#b91c1c;--orange:#f97316;--dark:#191b23}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.5 system-ui,"Segoe UI",sans-serif}
a{color:inherit}header{background:var(--dark);color:white;padding:18px 0}header a{color:white}header .wrap{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
.wrap{width:min(1080px,calc(100% - 32px));margin:auto}main{padding:32px 0}h1,h2,h3,p{margin-top:0}h1{font-size:clamp(1.8rem,4vw,2.5rem)}
.panel,.card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:20px;min-width:0}
.panel{margin:24px 0}.muted{color:var(--muted);font-size:.9rem}
label{display:block;font-size:.87rem;font-weight:700;margin:0 0 6px}
input,select,button{font:inherit}input:not([type=checkbox]),select{width:100%;min-width:0;border:1px solid #d6d7da;border-radius:10px;padding:10px;background:#fff;color:var(--ink)}
input[type=file]{max-width:100%}.field{margin-bottom:14px}.check{display:flex;align-items:flex-start;gap:8px;font-weight:500;margin:12px 0}.check input{margin-top:4px;flex:none;accent-color:var(--orange)}
button{border:0;border-radius:10px;padding:10px 16px;cursor:pointer;font-weight:750;background:linear-gradient(120deg,#b91c1c,#f97316);color:#fff}
.secondary{background:#f3eee9;color:#50342b}.danger{background:#fff0ee;color:#ad2c21}.delete{margin-top:12px}
button:focus-visible,a:focus-visible,input:focus-visible,select:focus-visible{outline:2px solid #b94b13;outline-offset:3px}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,270px),1fr));gap:16px}
.card img{width:100%;height:180px;object-fit:contain;background:#f5f1ec;border-radius:9px;display:block;margin-bottom:12px}
.state{font-weight:750;font-size:.85rem}.live{color:#1d7a42}.draft{color:#675e55}
.notice{padding:12px 15px;border-radius:10px;background:#f0faf3;color:#195a35;margin-bottom:16px}
.error{background:#fff1ef;color:#a22a20}.footer{color:var(--muted);font-size:.8rem;padding:20px 0}
</style>
</head>
<body>
<header><div class="wrap"><strong>☀ Nometņu administrācija</strong><nav aria-label="Administrācijas saites"><a href="VasarasNometneBackend.php">Pārskats</a> · <a href="index.php">Pieslēgšanās</a> · <a href="VasarasNometne.php#galerija">Publiskā galerija</a></nav></div></header>
<main class="wrap">
    <h1>Galerijas pārvaldība</h1>
    <p class="muted">Attēli nometņu kartītēm un publiskajai galerijai. Augšupielādēts attēls sākotnēji nav publisks.</p>
    <?php if ($error): ?><div class="notice error" role="alert"><?= h($error) ?></div><?php endif; ?>
    <?php if ($message): ?><div class="notice" role="status"><?= h($message) ?></div><?php endif; ?>

    <?php if ($ready): ?>
    <section class="panel">
        <h2>Augšupielādēt attēlu</h2>
        <p class="muted"><?= count($images) ?> / <?= GALLERY_MAX_IMAGES ?> attēli. JPG, PNG vai WebP; līdz 5 MB, 4096 px katrā malā un 12,5 megapikseļiem.</p>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
            <input type="hidden" name="action" value="upload">
            <input type="hidden" name="MAX_FILE_SIZE" value="<?= GALLERY_MAX_UPLOAD ?>">
            <div class="field">
                <label for="image">Attēla fails</label>
                <input id="image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
            </div>
            <div class="field">
                <label for="alt_text">Alternatīvais teksts (alt)</label>
                <input id="alt_text" name="alt_text" type="text" maxlength="250" value="<?= h($uploadAlt) ?>" required aria-describedby="alt-help">
                <p class="muted" id="alt-help">Īsi apraksti attēlā redzamo, piemēram: “Bērni meža takā pēta augus”. Neiekļauj personu vārdus vai citus liekus personas datus.</p>
            </div>
            <div class="field">
                <label for="camp_id">Saistītā nometne</label>
                <select id="camp_id" name="camp_id">
                    <option value="">Vispārīgā galerija</option>
                    <?php foreach ($camps as $camp): ?>
                        <option value="<?= (int)$camp['id'] ?>" <?= $uploadCamp === (string)$camp['id'] ? 'selected' : '' ?>><?= h($camp['title']) ?><?= (int)$camp['is_published'] ? '' : ' (melnraksts)' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Augšupielādēt kā melnrakstu</button>
        </form>
    </section>

    <h2>Augšupielādētie attēli</h2>
    <?php if (!$images): ?><p class="muted">Galerija ir tukša.</p><?php endif; ?>
    <div class="cards">
        <?php foreach ($images as $image): $id = (int)$image['id']; ?>
        <article class="card">
            <img src="attels.php?id=<?= $id ?>&amp;preview=1" alt="<?= h($image['alt_text']) ?>" loading="lazy" width="<?= (int)$image['width'] ?>" height="<?= (int)$image['height'] ?>">
            <p class="state <?= (int)$image['is_published'] ? 'live' : 'draft' ?>"><?= (int)$image['is_published'] ? 'Publicēts' : 'Melnraksts' ?> · <?= (int)$image['width'] ?> × <?= (int)$image['height'] ?> px</p>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="image_id" value="<?= $id ?>">
                <div class="field">
                    <label for="alt_<?= $id ?>">Alternatīvais teksts</label>
                    <input id="alt_<?= $id ?>" name="alt_text" maxlength="250" value="<?= h($image['alt_text']) ?>" required>
                </div>
                <div class="field">
                    <label for="camp_<?= $id ?>">Saistītā nometne</label>
                    <select id="camp_<?= $id ?>" name="camp_id">
                        <option value="">Vispārīgā galerija</option>
                        <?php foreach ($camps as $camp): ?>
                            <option value="<?= (int)$camp['id'] ?>" <?= (int)$image['camp_id'] === (int)$camp['id'] ? 'selected' : '' ?>><?= h($camp['title']) ?><?= (int)$camp['is_published'] ? '' : ' (melnraksts)' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <label class="check"><input type="checkbox" name="is_published" value="1" <?= (int)$image['is_published'] ? 'checked' : '' ?>> Publicēt attēlu</label>
                <label class="check"><input type="checkbox" name="rights_confirmed" value="1"> Apstiprinu, ka ir tiesisks pamats attēla publicēšanai un nepieciešamās atļaujas.</label>
                <button class="secondary" type="submit">Saglabāt izmaiņas</button>
            </form>
            <form class="delete" method="post" onsubmit="return confirm('Vai tiešām dzēst šo attēlu?');">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="image_id" value="<?= $id ?>">
                <button class="danger" type="submit">Dzēst</button>
            </form>
        </article>
        <?php endforeach; ?>
    </div>
    <p class="footer">Ar nepublicētu nometni saistītie attēli nav publiski pieejami, pat ja attēls atzīmēts kā publicēts.</p>
    <?php endif; ?>
</main>
</body>
</html>
