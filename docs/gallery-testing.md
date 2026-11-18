# Galerija — uzstādīšana un pārbaudes (B5, A2)
 
## Pirms izvietošanas
- Izveidot esošās projekta datubāzes rezerves kopiju, neiekļaujot to Git.
- Pārbaudīt SHOW CREATE TABLE mm_camps: pieņemtā id kolonna ir INT UNSIGNED, dzinis InnoDB. Ja atšķiras, pielāgot migrācijas ārējo atslēgu, nevis dzēst tabulu.
- Izpildīt database/001_gallery.sql esošajā datubāzē.
- PHP vajadzīgi mysqli/mysqlnd, GD ar JPEG/PNG (un WebP, ja to izmanto), Fileinfo. Ieteicams upload_max_filesize = 5M, post_max_size = 8M vai vairāk, memory_limit = 192M vai vairāk, max_allowed_packet > 1 MB. Hostings var noteikt zemākus limitus; nebloķēt servera aizsardzību.
- Izvietot jaunos failus, saglabājot mapes: includes/gallery_upload.php, gallery_admin.php, attels.php, kā arī atjaunot administrācijas paneli, sākumlapu un privatums.php. Pārbaudīt esošā administrācijas faila nosaukumu: repozitorijā VasarasNometneBackend.php, vecajā kodā ir arī citi nosaukumi.
- Attēlu krātuve ir datubāze (līdz 60 attēliem, 768 KiB pārkodētam attēlam), nevis publiska mape. Pārbaudīt hostinga datubāzes kvotu. Nelietot īstus bērnu attēlus skolas prototipā bez atbilstoša pamata un atļaujām.
- Uzturēt PHP, GD, libjpeg, libpng, libwebp un hostingu atjauninātus. Nav integrēts antivīruss vai izolēts attēlu apstrādes process. Pārkodēšana samazina risku, bet neaizstāj šos aizsardzības slāņus.
- PNG caurspīdīgums kļūst balts; EXIF orientācija netiek saglabāta; animācijas netiek saglabātas. Var būt nepieciešams iepriekš pagriezt foto.

## Manuāli testi — vēl jāizpilda uz skolas servera
| Tests | Sagaidāmais rezultāts |
| --- | --- |
| Derīgs JPG/PNG/WebP, alt teksts un nometne | Saglabāts JPEG melnraksts; priekšskatījums darbojas; publiski nav redzams |
| Publicēt bez tiesību apstiprināšanas | Noraidīts |
| Publicēt ar apstiprinājumu | Parādās galerijā un jaunākais nometnes attēls kartītē |
| Labot alt tekstu, arī ar pēdiņām vai HTML simboliem | Alt atribūts ir korekti aizsargāts, netiek izpildīts HTML |
| Tukšs alt teksts vai vairāk nekā 250 rakstzīmes | Noraidīts serverī |
| .php, .svg, .gif, pārdēvēts teksta fails .jpg | Noraidīts |
| Pārdēvēts PNG uz .jpg | Noraidīts formāta neatbilstības dēļ |
| 20 MB fails, >4096 px mala, >12,5 MP | Noraidīts; esošā galerija nemainās |
| Bojāts JPEG | Noraidīts, ja dekodētājs to nevar nolasīt |
| Derīgs JPEG ar papildus datiem pēc attēla | Ja dekodēts, tiek saglabāts tikai jaunais JPEG; oriģinālais fails nav pieejams |
| Izrakstīties un atvērt gallery_admin.php | Novirza uz index.php |
| POST bez CSRF, nepareizs ID, nepastāvoša nometne | Noraidīts, nav neatļautu izmaiņu |
| Tieša attels.php saite uz melnrakstu, arī ar preview=1 bez sesijas | 404 |
| Paslēpt nometni vai attēlu | Publiskais attēla pieprasījums atgriež 404 |
| Dzēst attēlu; dzēst testa nometni | Attēls vai saistītie attēli izdzēsti (FK CASCADE) |
| 61. attēla augšupielāde | Noraidīta |
| Pārbaudīt 360 px un ar ekrāna lasītāju | Nav horizontālas ritināšanas, attēliem ir saturīgs alternatīvais teksts |

Neizpildītos testus nedrīkst uzskatīt par veiksmīgiem. Rezultātus un atrastās kļūdas dokumentēt pēc faktiskās testēšanas.
