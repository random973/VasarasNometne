<!DOCTYPE html>
<html lang="lv">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Privātums un sīkdatnes | Vasaras nometnes</title>
<style>
:root {
    --bg:#fffaf6;
    --surface:#fff;
    --text:#22232a;
    --muted:#626773;
    --border:#e8e2dd;
    --accent:#b93423;
}
* { box-sizing:border-box; }
body {
    margin:0;
    background:var(--bg);
    color:var(--text);
    font-family:system-ui,"Segoe UI",Arial,sans-serif;
    line-height:1.65;
}
a { color:var(--accent); overflow-wrap:anywhere; }
a:focus-visible { outline:2px solid var(--accent); outline-offset:4px; }
.wrap { width:min(900px,calc(100% - 36px)); margin:0 auto; }
header { background:#fff; border-bottom:1px solid var(--border); }
header .wrap {
    display:flex; flex-wrap:wrap; justify-content:space-between;
    align-items:center; gap:12px; padding:18px 0;
}
.brand { font-weight:800; text-decoration:none; color:var(--text); }
nav { display:flex; flex-wrap:wrap; gap:16px; font-size:.9rem; }
main { padding:40px 0 56px; }
h1 { font-size:clamp(2rem,5vw,3rem); line-height:1.2; margin:0 0 8px; }
h2 { font-size:1.5rem; margin:0 0 18px; }
h3 { font-size:1.08rem; margin:24px 0 8px; }
p { margin:0 0 13px; }
.subtitle { color:var(--muted); }
section {
    background:var(--surface); border:1px solid var(--border);
    border-radius:16px; padding:clamp(18px,4vw,35px); margin:24px 0;
    scroll-margin-top:20px; overflow-wrap:anywhere;
}
.draft {
    border:1px solid #e8bb8a; border-radius:12px;
    background:#fff3e5; color:#70411f; padding:16px 18px;
    margin:24px 0;
}
.cookie-info { display:grid; gap:10px; margin:16px 0; }
.cookie-item { background:var(--bg); border:1px solid var(--border); border-radius:12px; padding:16px; }
.cookie-item h3 { margin:0 0 10px; overflow-wrap:anywhere; }
.cookie-item p:last-child { margin-bottom:0; }
footer { background:#22232a; color:#fff; padding:24px 0; }
footer a { color:#ffc19c; }
</style>
</head>
<body>
<header>
    <div class="wrap">
        <a class="brand" href="VasarasNometne.php">☀ Vasaras nometnes</a>
        <nav aria-label="Privātuma sadaļas">
            <a href="#privatuma-politika">Privātuma politika</a>
            <a href="#sikdatnes">Sīkdatņu politika</a>
        </nav>
    </div>
</header>
<main class="wrap">
    <h1>Privātums un sīkdatnes</h1>
    <p class="subtitle">Informācija par personas datu apstrādi un vietnes tehnisko darbību.</p>

    <div class="draft">
        <strong>Skolas projekta izstrādes versija.</strong>
        Politika ir sagatavota pašreizējai prototipa funkcionalitātei.
        Pirms reālu pieteikumu pieņemšanas tā jāpapildina ar pārbaudītu
        informāciju par datu pārzini un faktisko datu apstrādi.
    </div>

    <section id="privatuma-politika">
        <h2>Privātuma politika</h2>

        <h3>1. Datu pārzinis un saziņa</h3>
        <p>Datu pārzinis: [jāprecizē].</p>
        <p>Kontaktinformācija datu aizsardzības jautājumiem: [jāprecizē].</p>

        <h3>2. Kādi dati tiek apstrādāti un kāpēc?</h3>
        <p>Publiskajā sākumlapā pašlaik var apskatīt nometņu informāciju.
        Pieteikšanās, atsauksmju iesniegšanas un kontaktformas vēl nav aktīvas.</p>
        <p>Administrācijas autentifikācijai tiek izmantots lietotājvārds,
        ievadītā parole pārbaudes laikā, datubāzē glabāta paroles
        jaucējvērtība un sesijas dati. Nolūks ir nodrošināt piekļuvi
        administrācijai tikai autorizētiem lietotājiem.</p>
        <p>Lai ierobežotu atkārtotus neveiksmīgus pieslēgšanās mēģinājumus,
        datubāzē tiek glabāta IP adrese, mēģinājumu skaits, pēdējā
        mēģinājuma laiks un bloķēšanas termiņš. Serveris var apstrādāt arī
        pieprasījumu tehniskos datus, piemēram, IP adresi un pieprasījuma laiku.</p>

        <p>Galerijā administrators var augšupielādēt attēlus un to alternatīvos
        aprakstus. Attēli tiek pārkodēti un saglabāti datubāzē; sākotnēji tie
        nav publiski. Pirms publicēšanas jāpārbauda attēla izmantošanas
        tiesiskais pamats un nepieciešamās atļaujas, īpaši bērnu attēliem.
        Oriģinālais fails un tā metadati netiek saglabāti. Publicētu attēlu
        iespējams paslēpt vai dzēst, taču apmeklētāju jau saglabātās kopijas
        tas neatsauc. Glabāšanas termiņi un rezerves kopiju politika jāprecizē.</p>

        <h3>3. Tiesiskie pamati, glabāšana un saņēmēji</h3>
        <p>Autentifikācijas un drošības datu apstrādes tiesiskais pamats
        atbilstoši Vispārīgās datu aizsardzības regulas 6. pantam
        jāprecizē faktiskajam datu pārzinim.</p>
        <p>Datu glabāšanas termiņi, saņēmēji (tostarp hostinga nodrošinātājs)
        un informācija par iespējamu datu nodošanu ārpus Eiropas Ekonomikas
        zonas vēl jāapstiprina. Pieslēgšanās bloķēšanas ilgums nav tas pats,
        kas drošības ierakstu glabāšanas termiņš.</p>

        <h3>4. Personas tiesības</h3>
        <p>Atbilstoši regulā noteiktajiem nosacījumiem personai ir tiesības
        piekļūt saviem datiem, lūgt tos labot vai dzēst, ierobežot apstrādi,
        iebilst pret apstrādi un izmantot datu pārnesamību. Ja apstrāde
        balstīta uz piekrišanu, to var atsaukt, neietekmējot iepriekšējās
        apstrādes likumību.</p>
        <p>Sūdzību iespējams iesniegt
        <a href="https://www.dvi.gov.lv/">Datu valsts inspekcijā</a>.</p>

        <h3>5. Turpmākās funkcijas un atjauninājumi</h3>
        <p>Pirms pieteikumu, bērnu un vecāku datu, e-pastu, maksājumu
        vai citu jaunu datu apstrādes funkciju ieviešanas politika tiks
        papildināta. Šobrīd tā neapraksta vēl neizveidotas sistēmas kā aktīvas.</p>
        <p><strong>Prototipa redakcija:</strong> 1.0.</p>
    </section>

    <section id="sikdatnes">
        <h2>Sīkdatņu politika</h2>
        <p>Sīkdatnes ir nelieli dati, ko vietne var saglabāt lietotāja
        pārlūkā. Pašreizējā lietotnes kodā nav ieviesti analītikas,
        reklāmas vai trešo pušu izsekošanas rīki.</p>
        <div class="cookie-info">
            <article class="cookie-item">
                <h3>PHP sesijas sīkdatne (parasti PHPSESSID)</h3>
                <p><strong>Veids:</strong> tehniski nepieciešama.</p>
                <p><strong>Nolūks:</strong> saglabāt administrācijas
                autentifikācijas sesiju un nodrošināt piekļuves aizsardzību.</p>
                <p><strong>Saņēmējs:</strong> vietnes serveris (pirmās puses sīkdatne).</p>
                <p><strong>Ilgums:</strong> atkarīgs no PHP sesijas un
                hostinga konfigurācijas; precīzs nosaukums un ilgums jāpārbauda serverī.</p>
            </article>
        </div>
        <h3>Piekrišana un sīkdatņu pārvaldība</h3>
        <p>Tehniski nepieciešamajām sīkdatnēm, kas nodrošina pieprasīto
        pakalpojumu, piekrišana nav jāprasa. Informatīvā paziņojuma
        aizvēršana nav piekrišana izsekošanai, un tā netiek saglabāta
        pārlūka krātuvē.</p>
        <p>Sīkdatnes var dzēst pārlūka iestatījumos; tas var pārtraukt
        administrācijas sesiju. Ja nākotnē ieviesīs neobligātas sīkdatnes,
        pirms to izmantošanas tiks ieviesta piekrišanas izvēle un atsaukšana.</p>
        <p>Vietnes faktiskā sīkdatņu izmantošana jāpārbauda arī hostinga vidē.</p>
        <p>Papildu informācija:
        <a href="https://www.dvi.gov.lv/lv/sikdatnes-un-privatuma-politika">Datu valsts inspekcijas skaidrojumi</a>.</p>
    </section>
</main>
<footer>
    <div class="wrap"><a href="VasarasNometne.php">← Atpakaļ uz sākumlapu</a></div>
</footer>
</body>
</html>
