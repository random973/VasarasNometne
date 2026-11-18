
<?php
// Publiskā sākumlapa.
// Pieslēgšanās: index.php
// Pārvaldība: administracija.php

require __DIR__ . '/db.php';
mysqli_set_charset($conn, 'utf8mb4');

// Droša HTML attēlošana
function h($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

// Rezerves teksti, ja kāds ieraksts vēl nav datubāzē
$content = [
    'site_name' => 'Vasaras nometnes',
    'hero_title' => 'Piedzīvojumi sākas vasarā.',
    'hero_intro' => 'Šeit būs informācija par mūsu nometnēm, aktivitātēm un pieteikšanās iespējām.',
    'about_intro' => 'Kas organizē nometnes, kādas ir mūsu vērtības un pieredze?',
    'about_text' => 'Šeit vēlāk būs organizatora apraksts un cita informācija.',
    'contact_intro' => 'Vieta saziņas informācijai un vēlāk drošai kontaktu formai.',
    'contact_text' => 'E-pasts, tālrunis un kontaktforma — pievienosim vēlāk.',
    'faq_1_q' => 'Kā varēs pieteikties nometnei?',
    'faq_1_a' => 'Pieteikšanās sistēmu pievienosim nākamajos izstrādes posmos.',
    'faq_2_q' => 'Kur atradīšu nometņu datumus un cenas?',
    'faq_2_a' => 'Šī informācija parādīsies nometņu kartītēs.'
];

// Nolasa mājaslapas tekstus
$result = mysqli_query(
    $conn,
    'SELECT content_key, content_value FROM mm_site_content'
);

while ($row = mysqli_fetch_assoc($result)) {
    $content[$row['content_key']] = $row['content_value'];
}

// Nolasa tikai publicētās nometnes
$camps = [];

$result = mysqli_query(
    $conn,
    'SELECT * FROM mm_camps
     WHERE is_published = 1
     ORDER BY start_date ASC, id DESC'
);

while ($row = mysqli_fetch_assoc($result)) {
    $camps[] = $row;
}
?>

<!DOCTYPE html>
<html lang="lv">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Informācija par vasaras nometnēm un pieteikšanos.">
<title><?= h($content['site_name']) ?> | Sākums</title>

<style>
:root {
    --background:#fffaf6;
    --surface:#ffffff;
    --text:#22232a;
    --muted:#626773;
    --border:#e8e2dd;
    --accent:#d83b24;
    --accent-light:#ff8a3c;
    --radius:16px;
}

* { box-sizing:border-box; }
html { scroll-behavior:smooth; }

body {
    margin:0;
    font-family:system-ui,"Segoe UI",Arial,sans-serif;
    background:var(--background);
    color:var(--text);
    line-height:1.6;
}

a { color:inherit; text-decoration:none; }
button,input,textarea { font:inherit; }

.container {
    width:min(1120px,calc(100% - 40px));
    margin:0 auto;
}

section {
    scroll-margin-top:120px;
    padding:60px 0;
}

h1,h2,h3,p { margin-top:0; }

h2 {
    font-size:clamp(1.6rem,3vw,2.2rem);
    margin-bottom:8px;
}

.section-intro {
    color:var(--muted);
    margin-bottom:26px;
}

/* Galvene un navigācija */
.header {
    position:sticky;
    top:0;
    z-index:10;
    background:rgba(255,255,255,.96);
    border-bottom:1px solid var(--border);
    box-shadow:0 4px 20px rgba(50,30,20,.04);
}

.header-inner {
    min-height:76px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:22px;
}

.brand {
    font-size:1.15rem;
    font-weight:800;
    white-space:nowrap;
}

.brand-icon {
    color:var(--accent);
    margin-right:6px;
}

.nav {
    display:flex;
    align-items:center;
    gap:18px;
}

.nav a {
    font-size:.9rem;
    font-weight:600;
    color:#3e424a;
    white-space:nowrap;
}

.nav a:hover,
.nav a:focus-visible {
    color:var(--accent);
}

.login-link {
    padding:10px 16px;
    border:1px solid var(--border);
    border-radius:10px;
    font-size:.9rem;
    font-weight:700;
    white-space:nowrap;
}

.login-link:hover {
    border-color:var(--accent);
    color:var(--accent);
}

/* Sākuma sadaļa */
.hero {
    padding:88px 0;
    background:linear-gradient(
        120deg,
        #fff1e6 0%,
        #fffaf6 65%,
        #ffe1d5 100%
    );
    border-bottom:1px solid var(--border);
}

.hero-grid {
    display:grid;
    grid-template-columns:1.2fr .8fr;
    gap:46px;
    align-items:center;
}

.eyebrow {
    font-size:.83rem;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.12em;
    color:var(--accent);
}

h1 {
    font-size:clamp(2.25rem,5vw,4.1rem);
    line-height:1.12;
    letter-spacing:-.04em;
    margin-bottom:20px;
}

.hero p {
    color:var(--muted);
    font-size:1.07rem;
    max-width:560px;
}

.actions {
    display:flex;
    flex-wrap:wrap;
    gap:12px;
    margin-top:27px;
}

.btn {
    display:inline-block;
    border-radius:11px;
    padding:13px 20px;
    font-weight:750;
    transition:transform .15s,box-shadow .15s;
}

.btn:hover {
    transform:translateY(-2px);
}

.btn-primary {
    color:white;
    background:linear-gradient(
        110deg,
        var(--accent),
        var(--accent-light)
    );
    box-shadow:0 8px 24px rgba(216,59,36,.18);
}

.btn-secondary {
    background:white;
    border:1px solid var(--border);
}

.hero-placeholder {
    min-height:280px;
    border:2px dashed #e9ad91;
    border-radius:24px;
    background:rgba(255,255,255,.57);
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    text-align:center;
    padding:32px;
    color:var(--muted);
}

.hero-placeholder .symbol {
    font-size:4rem;
    margin-bottom:12px;
}

/* Nometņu kartītes */
.grid-3 {
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:20px;
}

.card {
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:var(--radius);
    overflow:hidden;
    box-shadow:0 8px 24px rgba(45,30,25,.035);
}

.card-body { padding:22px; }
.card h3 { margin-bottom:8px; }

.card p {
    color:var(--muted);
    font-size:.94rem;
}

.image-placeholder {
    height:165px;
    display:grid;
    place-items:center;
    background:linear-gradient(135deg,#fff0e5,#ffe2d5);
    border-bottom:1px solid var(--border);
    color:#a75e40;
    font-size:.92rem;
}

.tag {
    display:inline-block;
    padding:5px 9px;
    border-radius:7px;
    background:#fff1e7;
    color:#93442a;
    font-size:.75rem;
    font-weight:700;
    margin:0 5px 7px 0;
}

/* Pārējās sadaļas */
.soft-section {
    background:#fff;
    border-top:1px solid var(--border);
    border-bottom:1px solid var(--border);
}

.plain-box {
    background:var(--background);
    padding:28px;
    border:1px dashed #d7c8bc;
    border-radius:var(--radius);
    color:var(--muted);
}

.gallery-placeholder {
    min-height:155px;
    display:grid;
    place-items:center;
    border:1px dashed #d7c8bc;
    border-radius:var(--radius);
    background:#fff3eb;
    color:#a75e40;
}

details {
    border:1px solid var(--border);
    border-radius:11px;
    padding:16px 18px;
    background:#fff;
    margin-bottom:10px;
}

summary {
    cursor:pointer;
    font-weight:700;
}

details p {
    color:var(--muted);
    margin:10px 0 0;
}

.footer {
    background:#22232a;
    color:#fff;
    padding:34px 0;
}

.footer-inner {
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    justify-content:space-between;
    gap:12px;
}

.footer small { color:#c4c4cc; }
.footer a { color:#ffd0b6; text-decoration:underline; }
.footer a:focus-visible,
.cookie-notice a:focus-visible,
.cookie-notice button:focus-visible {
    outline:2px solid #ff9955;
    outline-offset:3px;
}
.cookie-notice {
    position:fixed;
    bottom:16px;
    left:16px;
    right:16px;
    z-index:100;
    max-width:820px;
    margin:auto;
    padding:16px 20px;
    background:#22232a;
    color:#fff;
    border:1px solid #55515a;
    border-radius:14px;
    box-shadow:0 12px 35px rgba(0,0,0,.2);
    display:flex;
    align-items:center;
    gap:16px;
}
.cookie-notice[hidden] { display:none; }
.cookie-notice p { flex:1; margin:0; font-size:.9rem; }
.cookie-notice a { color:#ffd0b6; text-decoration:underline; }
.cookie-notice button {
    flex-shrink:0;
    border:0;
    border-radius:9px;
    background:#ff9955;
    color:#22232a;
    padding:10px 16px;
    font-weight:750;
    cursor:pointer;
}
@media(max-width:600px) {
    .cookie-notice { flex-direction:column; align-items:stretch; gap:10px; }
}

/* Mobilā versija */
@media(max-width:850px) {
    .header-inner {
        flex-wrap:wrap;
        padding:15px 0;
        gap:12px;
    }

    .nav {
        order:3;
        width:100%;
        overflow-x:auto;
        gap:18px;
        padding:5px 0;
    }

    .hero-grid { grid-template-columns:1fr; }
    .hero { padding:54px 0; }
    .hero-placeholder { min-height:190px; }
    .grid-3 { grid-template-columns:1fr; }
    section { padding:44px 0; }
}

@media(prefers-reduced-motion:reduce) {
    html { scroll-behavior:auto; }
    .btn { transition:none; }
}
</style>
</head>

<body>

<header class="header">
    <div class="container header-inner">

        <a class="brand" href="#sakums">
            <span class="brand-icon">☀</span>
            <?= h($content['site_name']) ?>
        </a>

        <nav class="nav" aria-label="Galvenā navigācija">
            <a href="#nometnes">Nometnes</a>
            <a href="#par-mums">Par mums</a>
            <a href="#galerija">Galerija</a>
            <a href="#atsauksmes">Atsauksmes</a>
            <a href="#faq">FAQ</a>
            <a href="#kontakti">Kontakti</a>
        </nav>

        <a class="login-link" href="index.php">
            Administrācija
        </a>

    </div>
</header>

<main id="sakums">

    <!-- SĀKUMS -->
    <div class="hero">
        <div class="container hero-grid">

            <div>
                <div class="eyebrow">Laipni lūgti!</div>

                <h1><?= h($content['hero_title']) ?></h1>

                <p>
                    <?= nl2br(h($content['hero_intro'])) ?>
                </p>

                <div class="actions">
                    <a class="btn btn-primary" href="#nometnes">
                        Apskatīt nometnes
                    </a>

                    <a class="btn btn-secondary" href="#par-mums">
                        Uzzināt vairāk
                    </a>
                </div>
            </div>

            <div class="hero-placeholder">
                <div class="symbol">⛺</div>
                <strong>Vieta lielajam attēlam vai banerim</strong>
                <span>Vēlāk pievienosim nometnes foto.</span>
            </div>

        </div>
    </div>

    <!-- NOMETNES -->
    <section id="nometnes">
        <div class="container">

            <h2>Pieejamās nometnes</h2>
            <p class="section-intro">
                Izpēti nometņu piedāvājumu un izvēlies piemērotāko!
            </p>

            <?php if (!$camps): ?>

                <div class="plain-box">
                    Pašlaik nav publicētu nometņu.
                    Ieskaties vēlāk!
                </div>

            <?php else: ?>

                <div class="grid-3">

                    <?php foreach ($camps as $camp): ?>

                        <article class="card">

                            <div class="image-placeholder">
                                Nometnes attēls būs vēlāk
                            </div>

                            <div class="card-body">

                                <h3><?= h($camp['title']) ?></h3>

                                <p>
                                    <?= nl2br(h($camp['description'])) ?>
                                </p>

                                <span class="tag">
                                    <?= (int)$camp['age_min'] ?>–<?= (int)$camp['age_max'] ?> gadi
                                </span>

                                <span class="tag">
                                    <?= h(date('d.m.Y', strtotime($camp['start_date']))) ?>
                                    –
                                    <?= h(date('d.m.Y', strtotime($camp['end_date']))) ?>
                                </span>

                                <span class="tag">
                                    <?= h(number_format((float)$camp['price'], 2, ',', ' ')) ?> €
                                </span>

                                <span class="tag">
                                    <?= (int)$camp['capacity'] ?> vietas
                                </span>

                            </div>
                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div>
    </section>

    <!-- PAR MUMS -->
    <section id="par-mums" class="soft-section">
        <div class="container">

            <h2>Par mums</h2>

            <p class="section-intro">
                <?= nl2br(h($content['about_intro'])) ?>
            </p>

            <div class="plain-box">
                <?= nl2br(h($content['about_text'])) ?>
            </div>

        </div>
    </section>

    <!-- GALERIJA -->
    <section id="galerija">
        <div class="container">

            <h2>Galerija</h2>
            <p class="section-intro">
                Foto un video no nometņu piedzīvojumiem.
            </p>

            <div class="grid-3">
                <div class="gallery-placeholder">Foto / video 1</div>
                <div class="gallery-placeholder">Foto / video 2</div>
                <div class="gallery-placeholder">Foto / video 3</div>
            </div>

        </div>
    </section>

    <!-- ATSAUKSMES -->
    <section id="atsauksmes" class="soft-section">
        <div class="container">

            <h2>Atsauksmes</h2>

            <p class="section-intro">
                Vēlāk šeit būs apstiprinātās vecāku atsauksmes.
            </p>

            <div class="plain-box">
                Atsauksmju sadaļu pievienosim vēlāk.
            </div>

        </div>
    </section>

    <!-- FAQ -->
    <section id="faq">
        <div class="container">

            <h2>Biežāk uzdotie jautājumi</h2>

            <p class="section-intro">
                Atbildes uz biežāk uzdotajiem jautājumiem.
            </p>

            <details>
                <summary><?= h($content['faq_1_q']) ?></summary>
                <p><?= nl2br(h($content['faq_1_a'])) ?></p>
            </details>

            <details>
                <summary><?= h($content['faq_2_q']) ?></summary>
                <p><?= nl2br(h($content['faq_2_a'])) ?></p>
            </details>

        </div>
    </section>

    <!-- KONTAKTI -->
    <section id="kontakti" class="soft-section">
        <div class="container">

            <h2>Kontakti</h2>

            <p class="section-intro">
                <?= nl2br(h($content['contact_intro'])) ?>
            </p>

            <div class="plain-box">
                <?= nl2br(h($content['contact_text'])) ?>
            </div>

        </div>
    </section>

</main>

<footer class="footer">
    <div class="container footer-inner">

        <strong>
            ☀ <?= h($content['site_name']) ?>
        </strong>

        <small>
            <a href="privatums.php#privatuma-politika">Privātuma politika</a>
            ·
            <a href="privatums.php#sikdatnes">Sīkdatņu politika</a>
        </small>

    </div>
</footer>

<aside id="cookie-notice" class="cookie-notice" aria-label="Informācija par sīkdatnēm" hidden>
    <p><strong>Par sīkdatnēm</strong><br>
        Administrācijas pieslēgšanās izmanto tehniski nepieciešamās sesijas
        sīkdatnes. Vietnes kodā nav ieviesta reklāmas vai analītikas izsekošana.
        <a href="privatums.php#sikdatnes">Uzzināt vairāk</a>
    </p>
    <button id="cookie-dismiss" type="button">Sapratu</button>
</aside>
<script>
(() => {
    const notice = document.getElementById('cookie-notice');
    const dismiss = document.getElementById('cookie-dismiss');
    dismiss.addEventListener('click', () => {
        notice.hidden = true;
    });
    notice.hidden = false;
})();
</script>
</body>
</html>