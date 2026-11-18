<?php
session_start();
require __DIR__ . '/db.php';

// Pamata iestatījumi
$maxAttempts = 5;
$cooldownSeconds = 15 * 60;
$showDebug = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Izrakstīšanās
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

// Ziņojums no iepriekšējā pieteikšanās mēģinājuma
$flash = $_SESSION['mm_login_flash'] ?? [];
unset($_SESSION['mm_login_flash']);

$error = $flash['error'] ?? '';
$login = $flash['login'] ?? '';
$debugUser = $flash['debug_user'] ?? '';
$debugUserFound = $flash['debug_found'] ?? '';
$debugResult = $flash['debug_result'] ?? '';
$user = $_SESSION['lietotajs'] ?? null;

// Pieteikšanās
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$user) {
    $login = trim((string)($_POST['login'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $now = time();

    $debugUser = $login;
    $debugUserFound = 'Nav pārbaudīts';
    $debugResult = 'Nav pārbaudīts';

    // Nolasa iepriekšējos neveiksmīgos mēģinājumus no datubāzes
    $stmt = mysqli_prepare(
        $conn,
        'SELECT attempts, last_attempt, blocked_until
         FROM mm_login_attempts WHERE ip = ?'
    );
    mysqli_stmt_bind_param($stmt, 's', $ip);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $attemptRow = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($attemptRow && (int)$attemptRow['blocked_until'] > $now) {
        // Bloķētam IP netiek veikta paroles pārbaude
        $minutes = (int)ceil(((int)$attemptRow['blocked_until'] - $now) / 60);
        $error = "Pārāk daudz neveiksmīgu mēģinājumu. Mēģini vēlreiz pēc $minutes min.";
        $debugResult = 'Bloķēts';
    } else {
        // Pārbauda lietotāju un paroles hash
        $stmt = mysqli_prepare(
            $conn,
            'SELECT id, name, pass FROM mm_users WHERE name = ?'
        );
        mysqli_stmt_bind_param($stmt, 's', $login);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $userRow = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        $debugUserFound = $userRow ? 'Jā' : 'Nē';

        if ($userRow) {
            $verified = password_verify($password, $userRow['pass']);
            $debugResult = $verified ? 'TRUE (sakrīt)' : 'FALSE (nesakrīt)';

            if ($verified) {
                // Pieslēgšanās izdevusies
                $stmt = mysqli_prepare(
                    $conn,
                    'DELETE FROM mm_login_attempts WHERE ip = ?'
                );
                mysqli_stmt_bind_param($stmt, 's', $ip);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                session_regenerate_id(true);
                $_SESSION['lietotajs'] = $userRow['name'];
                header('Location: index.php', true, 303);
                exit;
            }
        }

        // Aprēķina neveiksmīgos mēģinājumus
        $attempts = 0;
        if ($attemptRow && (int)$attemptRow['last_attempt'] > $now - $cooldownSeconds) {
            $attempts = (int)$attemptRow['attempts'];
        }
        $attempts++;
        $blockedUntil = ($attempts >= $maxAttempts) ? $now + $cooldownSeconds : 0;

        $stmt = mysqli_prepare(
            $conn,
            'INSERT INTO mm_login_attempts
             (ip, attempts, last_attempt, blocked_until)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
             attempts = ?, last_attempt = ?, blocked_until = ?'
        );
        mysqli_stmt_bind_param(
            $stmt,
            'siiiiii',
            $ip, $attempts, $now, $blockedUntil,
            $attempts, $now, $blockedUntil
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($blockedUntil > 0) {
            $error = 'Pārāk daudz neveiksmīgu mēģinājumu. Pieslēgšanās bloķēta uz 15 minūtēm.';
        } else {
            $remaining = $maxAttempts - $attempts;
            $error = "Nepareizs lietotājvārds vai parole. Atlikušie mēģinājumi: $remaining";
        }
    }

    // POST → Redirect → GET
    // Pārlādējot lapu, mēģinājums netiek skaitīts vēlreiz
    $_SESSION['mm_login_flash'] = [
        'error' => $error,
        'login' => $login,
        'debug_user' => $debugUser,
        'debug_found' => $debugUserFound,
        'debug_result' => $debugResult
    ];
    header('Location: index.php', true, 303);
    exit;
}
?>

<!DOCTYPE html>
<html lang="lv">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $user ? 'Administrācija' : 'Pieslēgties' ?> | Vasaras nometnes</title>
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

body {
    margin:0;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:24px 16px;
    font-family:system-ui,"Segoe UI",Arial,sans-serif;
    color:var(--ink);
    background:radial-gradient(
        circle at top right,
        #fff0e6,
        transparent 45%
    ),var(--bg);
}

.card {
    width:100%;
    max-width:420px;
    padding:28px;
    background:#fff;
    border:1px solid var(--line);
    border-radius:17px;
    box-shadow:0 18px 50px rgba(20,20,30,.07);
    animation:up .3s ease-out;
}

@keyframes up {
    from { opacity:0; transform:translateY(12px); }
    to { opacity:1; transform:translateY(0); }
}

/* Mājaslapas identitāte */
.brand {
    display:flex;
    align-items:center;
    gap:10px;
    padding-bottom:20px;
    margin-bottom:24px;
    border-bottom:1px solid var(--line);
}

.brand-icon {
    display:flex;
    align-items:center;
    justify-content:center;
    width:38px;
    height:38px;
    flex-shrink:0;
    border-radius:10px;
    background:var(--dark);
    color:#ff9955;
    font-size:1.4rem;
}

.brand-name {
    font-size:.91rem;
    font-weight:800;
}

.brand-tag {
    margin-left:auto;
    color:var(--red);
    font-size:.65rem;
    font-weight:800;
    letter-spacing:.08em;
}

/* Pieslēgšanās simbols */
.logo {
    width:54px;
    height:54px;
    margin:0 auto 16px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#fff0e6;
    border-radius:14px;
    color:var(--red);
    font-size:1.6rem;
}

h1 {
    margin:0 0 6px;
    font-size:1.55rem;
    text-align:center;
    color:var(--ink);
}

.sub {
    margin:0 0 28px;
    text-align:center;
    color:var(--muted);
    font-size:.9rem;
    line-height:1.6;
}

.field { margin-bottom:18px; }

label {
    display:block;
    font-size:.86rem;
    font-weight:700;
    color:var(--ink);
    margin-bottom:7px;
}

input[type=text],
input[type=password] {
    width:100%;
    padding:12px 13px;
    font-size:1rem;
    border:1px solid #d6d7da;
    border-radius:10px;
    background:#fff;
    transition:border-color .15s,box-shadow .15s;
}

input:focus {
    outline:none;
    border-color:var(--orange);
    box-shadow:0 0 0 3px rgba(249,115,22,.13);
}

.show {
    display:flex;
    align-items:center;
    gap:8px;
    margin-top:10px;
    font-size:.82rem;
    font-weight:500;
    color:var(--muted);
    cursor:pointer;
}

.show input {
    width:16px;
    height:16px;
    margin:0;
    accent-color:var(--orange);
}

/* Pogas */
.btn {
    display:block;
    width:100%;
    padding:12px 17px;
    border:0;
    border-radius:10px;
    background:linear-gradient(120deg,#b91c1c,#f97316);
    color:#fff;
    font-size:.94rem;
    font-weight:750;
    text-align:center;
    text-decoration:none;
    cursor:pointer;
    transition:filter .15s,transform .15s;
}

.btn:hover {
    filter:brightness(1.07);
    transform:translateY(-1px);
}

.btn:focus-visible {
    outline:3px solid var(--orange);
    outline-offset:3px;
}

.btn-muted {
    background:#f3eee9;
    color:#50342b;
    margin-top:12px;
}

.btn-muted:hover { filter:brightness(.97); }

/* Kļūdu paziņojumi */
.error {
    padding:12px 15px;
    margin-bottom:20px;
    background:#fff1ef;
    border:1px solid #f1bbb6;
    border-radius:11px;
    color:#a22a20;
    font-size:.87rem;
}

/* Lokālā diagnostika */
.debug {
    background:#fff7ed;
    border:1px solid #fdba74;
    border-radius:11px;
    padding:14px;
    margin-bottom:20px;
    color:#7c2d12;
    font-size:.82rem;
    line-height:1.8;
    overflow-wrap:anywhere;
}

.debug strong {
    display:block;
    margin-bottom:6px;
    color:#9a3412;
}

.footnote {
    margin:25px 0 0;
    padding-top:17px;
    border-top:1px solid var(--line);
    color:var(--muted);
    font-size:.75rem;
    text-align:center;
}

.privacy-links {
    margin:10px 0 0;
    display:flex;
    flex-wrap:wrap;
    justify-content:center;
    gap:6px 14px;
    font-size:.78rem;
}
.privacy-links a { color:#a22a20; text-decoration:underline; }
.privacy-links a:focus-visible {
    outline:2px solid var(--orange);
    outline-offset:3px;
}

@media(max-width:480px) {
    .card { padding:22px 18px; }
    .brand-tag { font-size:.58rem; }
}

@media(prefers-reduced-motion:reduce) {
    .card { animation:none; }
    .btn { transition:none; }
}
</style>
</head>

<body>
<div class="card">

    <div class="brand">
        <div class="brand-icon">☀</div>
        <span class="brand-name">Vasaras nometnes</span>
        <span class="brand-tag">ADMINISTRĀCIJA</span>
    </div>

    <?php if ($user): ?>

        <div class="logo">&#128075;</div>
        <h1>Sveiks, <?= h($user) ?>!</h1>
        <p class="sub">Tu esi veiksmīgi pieslēdzies.</p>

        <a class="btn" href="administracija.php">
            Atvērt administrāciju
        </a>

        <a class="btn btn-muted" href="VasarasNometne.php">
            Apskatīt mājaslapu
        </a>

        <a class="btn btn-muted" href="?logout=1">
            Iziet
        </a>

    <?php else: ?>

        <div class="logo">&#128274;</div>
        <h1>Pieslēgties</h1>
        <p class="sub">Ievadi savu lietotājvārdu un paroli.</p>

        <?php if ($error): ?>

            <div class="error">
                <?= h($error) ?>
            </div>

            <?php if ($showDebug): ?>
                <div class="debug">
                    <strong>🔧 Pieteikšanās diagnostika</strong>

                    Ievadītais lietotājvārds:
                    <?= h($debugUser) ?>
                    <br>

                    Lietotājs atrasts datubāzē:
                    <?= h($debugUserFound) ?>
                    <br>

                    Paroles pārbaude:
                    <?= h($debugResult ?: 'Nav veikta') ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>

        <form method="post" action="index.php" autocomplete="off">

            <div class="field">
                <label for="login">Lietotājvārds</label>
                <input
                    type="text"
                    id="login"
                    name="login"
                    value="<?= h($login) ?>"
                    autofocus
                    required>
            </div>

            <div class="field">
                <label for="password">Parole</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    maxlength="150"
                    required>

                <label class="show">
                    <input
                        type="checkbox"
                        onclick="document.getElementById('password').type = this.checked ? 'text' : 'password'">
                    Rādīt paroli
                </label>
            </div>

            <button class="btn" type="submit">
                Pieslēgties
            </button>
        </form>

    <?php endif; ?>

    <p class="footnote">Nometņu pārvaldības sistēma</p>
    <nav class="privacy-links" aria-label="Privātuma informācija">
        <a href="privatums.php#privatuma-politika">Privātuma politika</a>
        <a href="privatums.php#sikdatnes">Sīkdatņu politika</a>
    </nav>

</div>
</body>
</html>