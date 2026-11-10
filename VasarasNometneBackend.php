<?php
session_start();

if (empty($_SESSION['lietotajs'])) {
    header('Location: index.php');
    exit;
}

// Esošais pieslēgšanās lietotājs
$user = $_SESSION['lietotajs'];

require __DIR__ . '/db.php';
mysqli_set_charset($conn, 'utf8mb4');

// Droša teksta attēlošana HTML
function h($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

function textLength(string $value): int {
    return function_exists('mb_strlen')
        ? mb_strlen($value, 'UTF-8')
        : (int)preg_match_all('/./us', $value);
}

function validDate(string $date): bool {
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $d !== false && $d->format('Y-m-d') === $date;
}

// CSRF aizsardzība
if (empty($_SESSION['mm_admin_csrf'])) {
    $_SESSION['mm_admin_csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['mm_admin_csrf'];

// Rediģējamie mājaslapas lauki
$fields = [
    'site_name' => ['Mājaslapas nosaukums', 'text', 100],
    'hero_title' => ['Sākumlapas virsraksts', 'text', 150],
    'hero_intro' => ['Sākumlapas apraksts', 'textarea', 900],
    'about_intro' => ['Par mums — ievads', 'textarea', 500],
    'about_text' => ['Par mums — galvenais teksts', 'textarea', 3000],
    'contact_intro' => ['Kontaktu sadaļas ievads', 'textarea', 500],
    'contact_text' => ['Kontaktu informācija', 'textarea', 1500],
    'faq_1_q' => ['FAQ — 1. jautājums', 'text', 250],
    'faq_1_a' => ['FAQ — 1. atbilde', 'textarea', 1500],
    'faq_2_q' => ['FAQ — 2. jautājums', 'text', 250],
    'faq_2_a' => ['FAQ — 2. atbilde', 'textarea', 1500]
];

$errors = [];
$draftContent = null;
$draftCamp = null;

// POST pieprasījumu apstrāde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('Nederīgs drošības tokens. Pārlādē lapu.');
    }

    $action = $_POST['action'] ?? '';

    // Saglabā mājaslapas saturu
    if ($action === 'save_content') {

        $draftContent = [];

        foreach ($fields as $key => [$label, $type, $maxLength]) {
            $value = trim((string)($_POST[$key] ?? ''));
            $draftContent[$key] = $value;

            if ($value === '') {
                $errors[] = "Lauks “$label” nedrīkst būt tukšs.";
            } elseif (textLength($value) > $maxLength) {
                $errors[] = "Lauks “$label” ir pārāk garš.";
            }
        }

        if (!$errors) {
            $stmt = mysqli_prepare(
                $conn,
                'INSERT INTO mm_site_content
                 (content_key, content_value)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE
                 content_value = VALUES(content_value)'
            );

            foreach ($draftContent as $key => $value) {
                mysqli_stmt_bind_param($stmt, 'ss', $key, $value);
                mysqli_stmt_execute($stmt);
            }

            mysqli_stmt_close($stmt);

            header('Location: slepeni.php?msg=content#saturs');
            exit;
        }
    }

    // Izveido vai rediģē nometni
    if ($action === 'save_camp') {

        $campId = filter_var(
            $_POST['camp_id'] ?? '0',
            FILTER_VALIDATE_INT
        );
        $campId = ($campId && $campId > 0) ? $campId : 0;

        $title = trim((string)($_POST['title'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));

        $ageMin = filter_var(
            $_POST['age_min'] ?? '',
            FILTER_VALIDATE_INT
        );
        $ageMax = filter_var(
            $_POST['age_max'] ?? '',
            FILTER_VALIDATE_INT
        );
        
        $startDate = sprintf(
        '%04d-%02d-%02d',
        (int)($_POST['start_year'] ?? 0),
        (int)($_POST['start_month'] ?? 0),
        (int)($_POST['start_day'] ?? 0)
        );

        $endDate = sprintf(
        '%04d-%02d-%02d',
        (int)($_POST['end_year'] ?? 0),
        (int)($_POST['end_month'] ?? 0),
        (int)($_POST['end_day'] ?? 0)
        );

        $price = str_replace(
            ',',
            '.',
            trim((string)($_POST['price'] ?? ''))
        );

        $capacity = filter_var(
            $_POST['capacity'] ?? '',
            FILTER_VALIDATE_INT
        );
        $published = isset($_POST['is_published']) ? 1 : 0;

        $draftCamp = [
            'id' => $campId,
            'title' => $title,
            'description' => $description,
            'age_min' => $ageMin,
            'age_max' => $ageMax,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'price' => $price,
            'capacity' => $capacity,
            'is_published' => $published
        ];

        // Datu pārbaude
        if ($title === '' || textLength($title) > 150) {
            $errors[] = 'Ievadi nometnes nosaukumu (līdz 150 rakstzīmēm).';
        }

        if ($description === '' || textLength($description) > 3000) {
            $errors[] = 'Ievadi nometnes aprakstu (līdz 3000 rakstzīmēm).';
        }

        if (
            $ageMin === false ||
            $ageMax === false ||
            $ageMin < 1 ||
            $ageMax > 120 ||
            $ageMin > $ageMax
        ) {
            $errors[] = 'Pārbaudi vecuma robežas.';
        }

        if (
            !validDate($startDate) ||
            !validDate($endDate) ||
            $startDate > $endDate
        ) {
            $errors[] = 'Pārbaudi nometnes sākuma un beigu datumu.';
        }

        if (
            validDate($startDate) &&
            $startDate < date('Y-m-d')
        ) {
            $errors[] = 'Nometnes sākuma datums nevar būt pagātnē.';
        }

        if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $price)) {
            $errors[] = 'Norādi derīgu cenu, piemēram, 125.00.';
        }

        if (
            $capacity === false ||
            $capacity < 1 ||
            $capacity > 65535
        ) {
            $errors[] = 'Vietu skaitam jābūt no 1 līdz 65535.';
        }

        if (!$errors) {

            if ($campId > 0) {
                // Rediģē esošu nometni
                $stmt = mysqli_prepare(
                    $conn,
                    'UPDATE mm_camps SET
                     title=?, description=?,
                     age_min=?, age_max=?,
                     start_date=?, end_date=?,
                     price=?, capacity=?, is_published=?
                     WHERE id=?'
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    'ssiisssiii',
                    $title,
                    $description,
                    $ageMin,
                    $ageMax,
                    $startDate,
                    $endDate,
                    $price,
                    $capacity,
                    $published,
                    $campId
                );

            } else {
                // Pievieno jaunu nometni
                $stmt = mysqli_prepare(
                    $conn,
                    'INSERT INTO mm_camps
                     (title, description, age_min, age_max,
                      start_date, end_date, price,
                      capacity, is_published)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    'ssiisssii',
                    $title,
                    $description,
                    $ageMin,
                    $ageMax,
                    $startDate,
                    $endDate,
                    $price,
                    $capacity,
                    $published
                );
            }

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            header('Location: slepeni.php?msg=camp#nometnes');
            exit;
        }
    }

    // Publicē, paslēpj vai dzēš nometni
    if ($action === 'toggle_camp' || $action === 'delete_camp') {

        $campId = filter_var(
            $_POST['camp_id'] ?? '',
            FILTER_VALIDATE_INT
        );

        if ($campId === false || $campId < 1) {
            $errors[] = 'Nederīgs nometnes ID.';
        } else {

            if ($action === 'toggle_camp') {
                $stmt = mysqli_prepare(
                    $conn,
                    'UPDATE mm_camps
                     SET is_published = 1 - is_published
                     WHERE id = ?'
                );
                $msg = 'status';
            } else {
                $stmt = mysqli_prepare(
                    $conn,
                    'DELETE FROM mm_camps WHERE id = ?'
                );
                $msg = 'deleted';
            }

            mysqli_stmt_bind_param($stmt, 'i', $campId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            header('Location: slepeni.php?msg=' . $msg . '#nometnes');
            exit;
        }
    }
}

// Nolasa mājaslapas saturu
$content = [];

$result = mysqli_query(
    $conn,
    'SELECT content_key, content_value FROM mm_site_content'
);

while ($row = mysqli_fetch_assoc($result)) {
    $content[$row['content_key']] = $row['content_value'];
}

if ($draftContent !== null) {
    $content = array_merge($content, $draftContent);
}

// Nolasa visas nometnes
$camps = [];

$result = mysqli_query(
    $conn,
    'SELECT * FROM mm_camps ORDER BY start_date ASC, id DESC'
);

while ($row = mysqli_fetch_assoc($result)) {
    $camps[] = $row;
}

$publishedCount = count(array_filter(
    $camps,
    fn($camp) => (int)$camp['is_published'] === 1
));

// Nosaka, vai rediģējam kādu nometni
$editCamp = null;

if ($draftCamp !== null) {
    $editCamp = $draftCamp;
} elseif (
    isset($_GET['edit']) &&
    ctype_digit((string)$_GET['edit'])
) {
    foreach ($camps as $camp) {
        if ((int)$camp['id'] === (int)$_GET['edit']) {
            $editCamp = $camp;
            break;
        }
    }
}

$messages = [
    'content' => 'Mājaslapas teksts saglabāts!',
    'camp' => 'Nometne saglabāta!',
    'status' => 'Nometnes publicēšanas statuss mainīts!',
    'deleted' => 'Nometne dzēsta!'
];

$message = $messages[$_GET['msg'] ?? ''] ?? '';
?>

<!DOCTYPE html>
<html lang="lv">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Administrācija | Vasaras nometnes</title>

<style>
:root {
    --ink:#24242b;
    --muted:#767783;
    --orange:#f97316;
    --red:#c92b26;
    --line:#e9e5e1;
    --bg:#faf7f3;
    --dark:#191b23;
}

* { box-sizing:border-box; }
html { scroll-behavior:smooth; }

body {
    margin:0;
    font-family:system-ui,"Segoe UI",Arial,sans-serif;
    color:var(--ink);
    background:var(--bg);
    line-height:1.5;
}

a { color:inherit; text-decoration:none; }
button,input,textarea { font:inherit; }
button { cursor:pointer; }
h1,h2,h3,p { margin-top:0; }

.layout {
    display:grid;
    grid-template-columns:240px minmax(0,1fr);
    min-height:100vh;
}

aside {
    background:var(--dark);
    color:#fff;
    padding:25px 17px;
    position:sticky;
    top:0;
    height:100vh;
    display:flex;
    flex-direction:column;
    gap:20px;
}

.brand {
    font-weight:850;
    font-size:1.15rem;
    padding:8px 10px;
}
.brand span { color:#ff9955; }

.side-nav { display:grid; gap:6px; }

.side-nav a {
    padding:11px 13px;
    border-radius:11px;
    color:#d0d0d9;
    font-size:.91rem;
    font-weight:650;
}
.side-nav a:hover {
    background:#33303a;
    color:#fff;
}

.side-note {
    margin-top:auto;
    border-top:1px solid #3c3c47;
    padding:17px 10px 0;
    color:#b9b9c3;
    font-size:.8rem;
}

main {
    width:min(1120px,100%);
    margin:0 auto;
    padding:36px clamp(20px,4vw,54px) 72px;
}

.top {
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:16px;
    flex-wrap:wrap;
    margin-bottom:28px;
}

.eyebrow {
    font-size:.75rem;
    letter-spacing:.13em;
    text-transform:uppercase;
    font-weight:800;
    color:var(--red);
}

h1 {
    font-size:clamp(1.8rem,4vw,2.5rem);
    margin-bottom:3px;
}
h2 { font-size:1.35rem; margin-bottom:5px; }

.muted { color:var(--muted); font-size:.9rem; }

.back-link {
    padding:10px 16px;
    border:1px solid #dcd7d1;
    border-radius:10px;
    background:#fff;
    font-weight:700;
    font-size:.87rem;
}

.stats {
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:15px;
    margin-bottom:29px;
}

.stat {
    background:#fff;
    border:1px solid var(--line);
    border-radius:14px;
    padding:21px;
}

.stat strong {
    display:block;
    font-size:2rem;
    margin-top:8px;
}
.stat span { font-size:.84rem; color:var(--muted); }

.panel {
    background:#fff;
    border:1px solid var(--line);
    border-radius:17px;
    padding:24px;
    margin-bottom:25px;
    box-shadow:0 12px 32px rgba(20,20,30,.035);
    scroll-margin-top:24px;
}

.panel-header { margin-bottom:22px; }
.panel-header p { margin:0; }

.field { margin-bottom:16px; }

label {
    display:block;
    font-weight:700;
    font-size:.87rem;
    margin-bottom:6px;
}

input[type=text],
input[type=date],
input[type=number],
select,
textarea {
    display:block;
    width:100%;
    border:1px solid #d6d7da;
    border-radius:10px;
    background:#fff;
    padding:11px 12px;
    color:var(--ink);
}

.date-parts {
    display:grid;
    grid-template-columns:0.8fr 1.5fr 1fr;
    gap:8px;
}

.date-parts select,
.date-parts input {
    min-width:0;
}

textarea { resize:vertical; min-height:96px; }

input:focus,textarea:focus {
    outline:none;
    border-color:var(--orange);
    box-shadow:0 0 0 3px rgba(249,115,22,.13);
}

.fields-2 {
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:0 16px;
}

.fields-3 {
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:0 16px;
}

.section-head {
    font-weight:800;
    color:#9b3523;
    padding-top:12px;
    margin:19px 0 14px;
    border-top:1px solid var(--line);
}

.btn {
    border:0;
    border-radius:10px;
    padding:11px 17px;
    font-weight:750;
    font-size:.9rem;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
}

.primary {
    color:#fff;
    background:linear-gradient(120deg,#b91c1c,#f97316);
}
.primary:hover { filter:brightness(1.07); }
.secondary { background:#f3eee9; color:#50342b; }
.danger { background:#fff0ee; color:#ad2c21; }
.small { padding:8px 11px; font-size:.8rem; }

.actions {
    display:flex;
    gap:10px;
    align-items:center;
    flex-wrap:wrap;
    margin-top:18px;
}

.notice {
    border:1px solid #b5dbc4;
    background:#f0faf3;
    color:#195a35;
    padding:12px 15px;
    border-radius:11px;
    margin-bottom:20px;
}

.notice.error {
    border-color:#f1bbb6;
    background:#fff1ef;
    color:#a22a20;
}

.notice ul { margin:4px 0 0; padding-left:22px; }

.camp-row {
    display:grid;
    grid-template-columns:minmax(0,1fr) auto;
    gap:14px;
    padding:17px 0;
    border-top:1px solid var(--line);
}

.camp-row h3 { font-size:1rem; margin:0 0 4px; }
.camp-row p { font-size:.85rem; color:var(--muted); margin:0; }

.camp-controls {
    display:flex;
    align-items:center;
    justify-content:flex-end;
    gap:8px;
    flex-wrap:wrap;
}

.pill {
    display:inline-block;
    font-size:.73rem;
    font-weight:750;
    border-radius:30px;
    padding:4px 9px;
    margin-left:8px;
}

.live { background:#e5f7e9; color:#1d7a42; }
.draft { background:#f2f0ee; color:#756b63; }

.checkline {
    display:flex;
    align-items:center;
    gap:9px;
    margin:7px 0;
    font-weight:650;
}

.checkline input {
    width:17px;
    height:17px;
    accent-color:var(--orange);
}

.empty {
    background:#fcf8f3;
    border:1px dashed #e6d7c9;
    border-radius:11px;
    padding:20px;
    color:var(--muted);
}

.footer-note {
    font-size:.8rem;
    color:var(--muted);
    padding:3px 5px;
}

@media(max-width:900px) {
    .layout { grid-template-columns:1fr; }
    aside { height:auto; position:static; padding:13px 18px; }
    .side-nav { display:flex; flex-wrap:wrap; }
    .side-note { display:none; }
    main { padding-top:25px; }
}

@media(max-width:650px) {
    .stats,.fields-2,.fields-3 { grid-template-columns:1fr; }
    .panel { padding:18px; }
    .camp-row { grid-template-columns:1fr; }
    .camp-controls { justify-content:flex-start; }
}
</style>
</head>

<body>
<div class="layout">

<aside>
    <a class="brand" href="VasarasNometne.php">
        <span>☀</span> Nometņu administrācija
    </a>

    <nav class="side-nav">
        <a href="#sakums">⌂ Pārskats</a>
        <a href="#saturs">✎ Lapas saturs</a>
        <a href="#nometnes">⛺ Nometnes</a>
        <a href="#camp-form">＋ Pievienot nometni</a>
        <a href="VasarasNometne.php">↗ Publiskā lapa</a>
    </nav>

    <div class="side-note">
        Pieslēdzies: <strong><?= h($user) ?></strong>
        <br>
        Testa administrācijas panelis
    </div>
</aside>

<main id="sakums">
    <div class="top">
        <div>
            <div class="eyebrow">Pārvaldības panelis</div>
            <h1>Sveiks, <?= h($user) ?>!</h1>
            <div class="muted">
                Pārvaldi mājaslapas tekstus un nometnes vienuviet.
            </div>
        </div>

        <a class="back-link" href="VasarasNometne.php" target="_blank" rel="noopener">
            ↗ Apskatīt lapu
        </a>
    </div>

    <?php if ($message): ?>
        <div class="notice">✓ <?= h($message) ?></div>
    <?php endif; ?>

    <div class="stats">
        <div class="stat">
            <span>Kopā nometnes</span>
            <strong><?= count($camps) ?></strong>
        </div>

        <div class="stat">
            <span>Publicētas nometnes</span>
            <strong><?= $publishedCount ?></strong>
        </div>

        <div class="stat">
            <span>Melnraksti</span>
            <strong><?= count($camps)-$publishedCount ?></strong>
        </div>
    </div>

    <!-- MĀJASLAPAS SATURS -->
    <section class="panel" id="saturs">
        <div class="panel-header">
            <h2>✎ Mājaslapas tekstu redaktors</h2>
            <p class="muted">
                Maini mājaslapas tekstus bez PHP failu rediģēšanas.
            </p>
        </div>

        <form method="post" action="slepeni.php#saturs">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
            <input type="hidden" name="action" value="save_content">

            <?php foreach ($fields as $key => [$label, $type, $maxLength]): ?>

                <?php if (in_array($key, ['about_intro','contact_intro','faq_1_q'])): ?>
                    <div class="section-head">
                        <?= $key === 'about_intro' ? 'Par mums' :
                            ($key === 'contact_intro' ? 'Kontakti' : 'FAQ') ?>
                    </div>
                <?php endif; ?>

                <div class="field">
                    <label for="<?= h($key) ?>"><?= h($label) ?></label>

                    <?php if ($type === 'textarea'): ?>
                        <textarea
                            id="<?= h($key) ?>"
                            name="<?= h($key) ?>"
                            maxlength="<?= $maxLength ?>"
                            required><?= h($content[$key] ?? '') ?></textarea>
                    <?php else: ?>
                        <input
                            type="text"
                            id="<?= h($key) ?>"
                            name="<?= h($key) ?>"
                            maxlength="<?= $maxLength ?>"
                            value="<?= h($content[$key] ?? '') ?>"
                            required>
                    <?php endif; ?>
                </div>

            <?php endforeach; ?>

            <button class="btn primary" type="submit">
                Saglabāt mājaslapas saturu
            </button>
        </form>
    </section>

    <!-- NOMETŅU SARAKSTS -->
    <section class="panel" id="nometnes">
        <div class="panel-header">
            <h2>⛺ Nometņu pārvaldība</h2>
            <p class="muted">
                Publicētās nometnes būs redzamas publiskajā mājaslapā.
            </p>
        </div>

        <?php if (!$camps): ?>
            <div class="empty">
                Vēl nav pievienota neviena nometne.
                Izveido pirmo zemāk!
            </div>
        <?php else: ?>

            <?php foreach ($camps as $camp): ?>
                <div class="camp-row">
                    <div>
                        <h3>
                            <?= h($camp['title']) ?>
                            <span class="pill <?= (int)$camp['is_published'] ? 'live' : 'draft' ?>">
                                <?= (int)$camp['is_published'] ? 'Publicēta' : 'Melnraksts' ?>
                            </span>
                        </h3>

                        <p>
                            <?= h($camp['start_date']) ?> –
                            <?= h($camp['end_date']) ?> ·
                            <?= h($camp['price']) ?> € ·
                            <?= (int)$camp['capacity'] ?> vietas
                        </p>
                    </div>

                    <div class="camp-controls">
                        <a class="btn secondary small"
                           href="?edit=<?= (int)$camp['id'] ?>#camp-form">
                            Labot
                        </a>

                        <form method="post" action="slepeni.php#nometnes">
                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                            <input type="hidden" name="action" value="toggle_camp">
                            <input type="hidden" name="camp_id" value="<?= (int)$camp['id'] ?>">

                            <button class="btn secondary small" type="submit">
                                <?= (int)$camp['is_published'] ? 'Paslēpt' : 'Publicēt' ?>
                            </button>
                        </form>

                        <form method="post" action="slepeni.php#nometnes"
                              onsubmit="return confirm('Vai tiešām dzēst šo nometni?');">
                            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                            <input type="hidden" name="action" value="delete_camp">
                            <input type="hidden" name="camp_id" value="<?= (int)$camp['id'] ?>">

                            <button class="btn danger small" type="submit">
                                Dzēst
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </section>

    <!-- NOMETNES IZVEIDE / LABOŠANA -->
    <section class="panel" id="camp-form">
        <div class="panel-header">
            <h2>
                <?= $editCamp && !empty($editCamp['id'])
                    ? '✎ Labot nometni'
                    : '＋ Pievienot nometni' ?>
            </h2>
            <p class="muted">
                Ievadi nometnes pamatinformāciju.
            </p>
        </div>

        <?php if ($errors && ($_POST['action'] ?? '') === 'save_camp'): ?>
        <div class="notice error">
            <strong>Neizdevās saglabāt:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= h($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form method="post" action="slepeni.php#camp-form">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
            <input type="hidden" name="action" value="save_camp">
            <input type="hidden" name="camp_id" value="<?= (int)($editCamp['id'] ?? 0) ?>">

            <div class="field">
                <label for="title">Nometnes nosaukums</label>
                <input type="text" id="title" name="title"
                       maxlength="150" required
                       value="<?= h($editCamp['title'] ?? '') ?>">
            </div>

            <div class="field">
                <label for="description">Apraksts</label>
                <textarea id="description" name="description"
                          maxlength="3000" required><?= h($editCamp['description'] ?? '') ?></textarea>
            </div>

            
            <div class="fields-2">
                <div class="field">
                    <label for="age_min">Vecums no</label>
                    <input type="number" id="age_min" name="age_min"
                        min="1" max="120" required
                        value="<?= h($editCamp['age_min'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="age_max">Vecums līdz</label>
                    <input type="number" id="age_max" name="age_max"
                        min="1" max="120" required
                        value="<?= h($editCamp['age_max'] ?? '') ?>">
                </div>
            </div>

            <?php
            $months = [
                1 => 'Janvāris', 2 => 'Februāris',
                3 => 'Marts', 4 => 'Aprīlis',
                5 => 'Maijs', 6 => 'Jūnijs',
                7 => 'Jūlijs', 8 => 'Augusts',
                9 => 'Septembris', 10 => 'Oktobris',
                11 => 'Novembris', 12 => 'Decembris'
            ];

            $dateFields = [
                'start' => 'Sākuma datums',
                'end' => 'Beigu datums'
            ];
            ?>

            <div class="fields-2">
                <?php foreach ($dateFields as $prefix => $label): ?>

                    <?php
                    $savedDate = $editCamp[$prefix . '_date'] ?? '';
                    $parts = $savedDate
                        ? explode('-', $savedDate)
                        : ['', '', ''];

                    $savedYear = $parts[0];
                    $savedMonth = (int)$parts[1];
                    $savedDay = (int)$parts[2];
                    ?>

                    <div class="field">
                        <label><?= h($label) ?></label>

                        <div class="date-parts">

                            <!-- Diena -->
                            <select name="<?= $prefix ?>_day"
                                    aria-label="<?= h($label) ?> - diena"
                                    required>
                                <option value="">Diena</option>

                                <?php for ($d = 1; $d <= 31; $d++): ?>
                                    <option value="<?= $d ?>"
                                        <?= $savedDay === $d ? 'selected' : '' ?>>
                                        <?= $d ?>
                                    </option>
                                <?php endfor; ?>
                            </select>

                            <!-- Mēnesis -->
                            <select name="<?= $prefix ?>_month"
                                    aria-label="<?= h($label) ?> - mēnesis"
                                    required>
                                <option value="">Mēnesis</option>

                                <?php foreach ($months as $number => $month): ?>
                                    <option value="<?= $number ?>"
                                        <?= $savedMonth === $number ? 'selected' : '' ?>>
                                        <?= h($month) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <!-- Gads -->
                                <input
                                type="number"
                                name="<?= $prefix ?>_year"
                                aria-label="<?= h($label) ?> - gads"
                                min="<?= date('Y') ?>"
                                max="<?= date('Y') + 15 ?>"
                                placeholder="Gads"
                                value="<?= h($savedYear) ?>"
                                required>
                            </div>
                        </div>

                <?php endforeach; ?>
            </div>


            <div class="fields-3">
                <div class="field">
                    <label for="price">Cena (€)</label>
                    <input type="number" id="price" name="price"
                           step="0.01" min="0" max="999999.99" required
                           value="<?= h($editCamp['price'] ?? '') ?>">
                </div>

                <div class="field">
                    <label for="capacity">Vietu skaits</label>
                    <input type="number" id="capacity" name="capacity"
                           min="1" max="65535" required
                           value="<?= h($editCamp['capacity'] ?? '') ?>">
                </div>

                <div class="field">
                    <label>Publicēšana</label>
                    <label class="checkline">
                        <input type="checkbox" name="is_published" value="1"
                            <?= !empty($editCamp['is_published']) ? 'checked' : '' ?>>
                        Publicēta
                    </label>
                </div>
            </div>

            <div class="actions">
                <button class="btn primary" type="submit">
                    <?= $editCamp && !empty($editCamp['id'])
                        ? 'Saglabāt izmaiņas'
                        : 'Izveidot nometni' ?>
                </button>

                <?php if ($editCamp && !empty($editCamp['id'])): ?>
                    <a class="btn secondary" href="slepeni.php#camp-form">
                        Atcelt labošanu
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <div class="footer-note">
        Nākamie posmi: pieteikumi, atsauksmes,
        galerija, e-pasti un rēķini.
    </div>
</main>
</div>
</body>
</html>
