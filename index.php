<?php
/* =========================================================
   Lex Krabbe b.v. — Horizontaal gestuurde boringen
   Single-file site: PHP + HTML + CSS + JavaScript
   ========================================================= */

// Where images/videos live. Point this to a local folder (e.g. 'assets/') once you've copied the files over.
$asset = 'https://lexkrabbe.nl/_assets/';

$site = [
    'name'     => 'Lex Krabbe b.v.',
    'tagline'  => 'Boor en grondwerk',
    'title'    => 'Horizontale grondboringen',
    'desc'     => 'Gespecialiseerd in horizontaal gestuurde boringen (HDD) voor het aanleggen van kabels, leidingen en andere ondergrondse infrastructuur',
    'phone'    => '06-55 85 54 94',
    'tel'      => 'tel:0031655855494',
    'email'    => 'administratie@lexkrabbe.nl',
    'street'   => 'Schopmansweg 6',
    'city'     => '7665 Albergen',
    'facebook' => 'https://www.facebook.com/profile.php?id=100090249636018',
    'terms'    => 'https://www.canva.com/design/DAGm2wPoG0o/IV_sPGUOAWtqPNPtDvAWIg/view',
];

$nav = [
    'home'            => 'Home',
    'hdd'             => 'HDD',
    'boormachines'    => 'Boormachines',
    'boormeester'     => 'Boormeester',
    'pipe-reel'       => 'Pipe Reel',
    'certificeringen' => 'Certificeringen',
    'social-blogs'    => 'Social blogs',
    'contact'         => 'Contact',
];

$machines = [
    [
        'name'  => 'JT 40',
        'img'   => 'media/596313461be286f303175c7cb07c723d.jpg',
        'specs' => ['Trekkracht: 178,0 kN', 'Rotatie: 7460 Nm', 'Motorvermogen: 160 pk', 'Lengte stangen: 4,5 meter'],
    ],
    [
        'name'  => 'JT 32',
        'img'   => 'media/4bb4525616a87127ec1673bd8d380a0d.jpg',
        'specs' => ['Druk- en trekkracht: 14,2 ton', 'Rotatie: 5694 Nm', 'Motorvermogen: 155 pk', 'Lengte stangen: 3,05 meter'],
    ],
    [
        'name'  => 'JT 10',
        'img'   => 'media/3d8f96760aba1a879da8a87320794319.jpg',
        'specs' => ['Trekkracht: 44,5 kN', 'Rotatie: 1490 Nm', 'Motorvermogen: 66 pk', 'Lengte stangen: 1,8 meter'],
    ],
];

$certs = [
    ['label' => 'KIWA',     'img' => 'media/45ce6c87e9bbe44a61c6567f81536bdf.png', 'type' => 'logo'],
    ['label' => 'CKB',      'img' => 'media/6a6bd49bdf59ff9fda834cdab40904df.png', 'type' => 'logo'],
    ['label' => 'VCA',      'img' => 'media/647f64b9c1fd5618cce26dcc9e6f49da.svg', 'type' => 'icon'],
    ['label' => 'ISO 9001', 'img' => 'media/4d45859b1f8505802177ecda55c1a349.svg', 'type' => 'icon'],
];

function e($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($site['title']) ?></title>
<meta name="description" content="<?= e($site['desc']) ?>">
<meta property="og:title" content="<?= e($site['title']) ?>">
<meta property="og:description" content="<?= e($site['desc']) ?>">
<meta property="og:type" content="website">
<link rel="icon" href="Boorinvulformulier/favicon.ico" sizes="any">
<link rel="icon" type="image/png" href="Boorinvulformulier/favicon-32x32.png" sizes="32x32">
<link rel="apple-touch-icon" href="Boorinvulformulier/apple-touch-icon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;700&family=Rubik+Mono+One&display=swap" rel="stylesheet">

<style>
:root{
    --navy:#050a30;
    --blue-light:#bad3e8;
    --blue-grey:#c8d5e2;
    --red:#b52c1c;
    --royal:#1b3b98;
    --grey:#6d685f;
    --text-light:#dfe7ed;
    --nav-h:46px;
    --wrap:1180px;
    --font:'Public Sans',-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;scroll-padding-top:var(--nav-h)}
body{font-family:var(--font);font-size:17px;line-height:1.5;color:var(--navy);background:var(--navy)}
img,video{display:block;max-width:100%}
a{color:inherit}
p + p{margin-top:1.5em}
.wrap{width:100%;max-width:var(--wrap);margin:0 auto;padding:0 32px}
.wide{letter-spacing:.08em}

/* ---------- NAV ---------- */
.nav{position:fixed;inset:0 0 auto 0;height:var(--nav-h);background:var(--navy);z-index:100;display:flex;align-items:center;justify-content:flex-end;padding:0 16px}
.nav ul{list-style:none;display:flex;gap:4px}
.nav a{display:block;padding:10px 14px;color:#fff;font-weight:700;font-size:15px;text-decoration:none;border-bottom:2px solid transparent;transition:border-color .2s,opacity .2s}
.nav a:hover{opacity:.8}
.nav a.active{border-bottom-color:#fff}
.burger{display:none;background:none;border:0;color:#fff;cursor:pointer;padding:8px}
.burger svg{width:26px;height:26px}

/* ---------- SECTIONS ---------- */
section{position:relative;padding:96px 0}
.navy{background:var(--navy);color:var(--text-light)}
.light{background:var(--blue-light)}
.blue-grey{background:var(--blue-grey)}
.red{background:var(--red);color:var(--text-light)}
h2{font-size:clamp(34px,4.5vw,50px);line-height:1.3;margin-bottom:28px}
.split{display:grid;grid-template-columns:1fr 1fr;gap:64px;align-items:center}
.center{text-align:center}

/* ---------- HERO ---------- */
.hero{min-height:100vh;padding:calc(var(--nav-h) + 40px) 0 60px;background:#fff url('<?= $asset ?>media/e93c56bc0d4c4388769416b53822bed7.jpg') center/cover no-repeat;display:flex;flex-direction:column}
.logo{display:inline-flex;align-items:center;gap:8px;text-decoration:none;text-shadow:0 2px 8px rgba(0,0,0,.33)}
.logo-text{display:flex;flex-direction:column;align-items:flex-end;line-height:1}
.logo-name{font-size:clamp(30px,3.6vw,48px);font-weight:700;color:var(--grey);letter-spacing:-.063em}
.logo-sub{font-size:clamp(17px,2vw,26px);font-weight:700;color:var(--royal);letter-spacing:-.063em;margin-top:6px}
.logo-k{font-family:'Rubik Mono One',var(--font);font-size:clamp(70px,8.5vw,116px);line-height:.9;color:var(--royal);letter-spacing:-.063em}
.hero h1{flex:1;display:flex;align-items:center;justify-content:center;text-align:center;font-weight:400;font-size:clamp(44px,6.2vw,85px);letter-spacing:-.054em;line-height:1.05;color:var(--navy);margin:40px 0}
.hero .cta-row{display:flex;justify-content:flex-end}
.btn{display:inline-block;background:var(--navy);color:#fff;font-weight:700;font-size:25px;text-transform:uppercase;text-decoration:underline;padding:14px 38px;transition:transform .2s,background .2s}
.btn:hover{transform:translateY(-2px);background:#0d1650}

/* ---------- HDD / BOORMEESTER ---------- */
.photo{width:100%;height:100%;max-height:640px;object-fit:cover;opacity:.9}
.lead{font-size:20px;font-weight:700;margin-top:1.5em}
.lead a{text-decoration:underline}
.medium{font-weight:500}

/* ---------- VIDEO ---------- */
.video{position:relative;margin:0 auto;overflow:hidden;background:#000;cursor:pointer}
.video video{width:100%;height:100%;object-fit:cover}
.video .play{position:absolute;inset:0;margin:auto;width:72px;height:72px;border-radius:50%;border:0;background:rgba(17,23,29,.6);color:#fff;display:grid;place-items:center;cursor:pointer;transition:opacity .25s,transform .2s}
.video .play svg{width:30px;height:30px;margin-left:4px}
.video.playing .play{opacity:0;pointer-events:none}
.video:hover .play{transform:scale(1.06)}
.video .mute{position:absolute;right:12px;bottom:12px;width:38px;height:38px;border-radius:50%;border:0;background:rgba(17,23,29,.6);color:#fff;display:grid;place-items:center;cursor:pointer}
.video .mute svg{width:20px;height:20px}
.video .bar{position:absolute;left:0;bottom:0;height:3px;background:#fff;width:0}
.video-lex{max-width:360px;aspect-ratio:9/13}
.video-social{max-width:470px;aspect-ratio:7/10}

/* ---------- BOORMACHINES ---------- */
.intro{max-width:720px;margin:0 auto 64px}
.machines{display:grid;grid-template-columns:repeat(3,1fr);gap:40px}
.machine h3{font-size:23px;margin:0 0 6px 24px}
.machine ul{padding-left:24px;margin-bottom:22px}
.machine li{font-size:17px}
.machine img{width:100%;aspect-ratio:3/2;object-fit:cover;transition:transform .35s}
.machine:hover img{transform:scale(1.03)}
.machine .imgbox{overflow:hidden}

/* ---------- COMPLEET ---------- */
.compleet .text{max-width:860px;margin:0 auto 48px}
.compleet .banner{width:100%;max-width:1040px;margin:0 auto;aspect-ratio:2.2/1;object-fit:cover}

/* ---------- PIPE REEL ---------- */
.pipe h2{font-weight:400}
.pipe .stack{display:grid;gap:24px}
.pipe .stack img{width:100%;object-fit:cover}
.pipe .logo-img{aspect-ratio:2.9/1}

/* ---------- CERTIFICERINGEN ---------- */
.red h2{font-weight:400;line-height:1.1}
.red h2 span{display:block;letter-spacing:.08em}
.red h3{font-weight:400;font-size:clamp(28px,3.2vw,40px);letter-spacing:.08em;margin:56px 0 12px}
.justify{text-align:justify}
.certs{display:grid;grid-template-columns:repeat(2,1fr);gap:48px 32px;justify-items:center}
.cert{text-align:center;text-transform:uppercase;font-size:19px}
.cert .icon-box{height:70px;display:flex;align-items:center;justify-content:center;margin-bottom:14px}
.cert .logo img{max-height:70px;max-width:180px}
.cert .icon img{height:44px;filter:brightness(0) invert(1)}

/* ---------- SOCIAL ---------- */
.social{padding:110px 0}

/* ---------- CONTACT / FOOTER ---------- */
.contact{min-height:80vh;background:var(--navy) url('<?= $asset ?>media/596313461be286f303175c7cb07c723d.jpg') center/cover no-repeat;display:flex;flex-direction:column;justify-content:space-between;padding:40px 0 24px;color:#000}
.card{margin:0 auto;text-align:center;background:rgba(255,255,255,.55);backdrop-filter:blur(4px);padding:22px 36px;letter-spacing:.08em}
.card h2{font-size:25px;margin:0;line-height:1.4}
.card p{margin:0;line-height:1.55}
.icons{display:flex;justify-content:center;gap:26px;margin-top:14px}
.icons a{display:grid;place-items:center;width:42px;height:42px;transition:transform .2s}
.icons a:hover{transform:scale(1.12)}
.icons svg{width:36px;height:36px}
.legal{margin:0 auto;text-align:center;background:rgba(255,255,255,.55);padding:8px 20px;font-weight:500;font-size:19px}

/* ---------- REVEAL ---------- */
.reveal{opacity:0;transform:translateY(30px);transition:opacity .8s ease,transform .8s ease}
.reveal.in{opacity:1;transform:none}

/* ---------- RESPONSIVE ---------- */
@media (max-width:1000px){
    .machines{grid-template-columns:1fr;max-width:460px;margin:0 auto}
}
@media (max-width:900px){
    .burger{display:block}
    .nav ul{position:absolute;top:var(--nav-h);left:0;right:0;flex-direction:column;gap:0;background:var(--navy);max-height:0;overflow:hidden;transition:max-height .3s}
    .nav.open ul{max-height:480px}
    .nav a{padding:14px 24px;border-bottom:1px solid rgba(255,255,255,.08)}
    .nav a.active{border-bottom-color:rgba(255,255,255,.08);background:rgba(255,255,255,.08)}
    .split{grid-template-columns:1fr;gap:40px}
    section{padding:72px 0}
}
@media (max-width:560px){
    .wrap{padding:0 18px}
    body{font-size:16px}
    .hero .cta-row{justify-content:center}
    .btn{font-size:20px}
    .certs{gap:36px 16px}
}
@media (prefers-reduced-motion:reduce){
    html{scroll-behavior:auto}
    .reveal{opacity:1;transform:none;transition:none}
}
</style>
</head>
<body>

<!-- NAV -->
<nav class="nav" id="nav">
    <button class="burger" id="burger" aria-label="Menu" aria-expanded="false">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 6a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H4.75A.75.75 0 0 1 4 6Zm0 6a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H4.75A.75.75 0 0 1 4 12Zm.75 5.25a.75.75 0 0 0 0 1.5h14.5a.75.75 0 0 0 0-1.5H4.75Z"/></svg>
    </button>
    <ul>
        <?php foreach ($nav as $id => $label): ?>
            <li><a href="#<?= $id ?>" data-target="<?= $id ?>"><?= e($label) ?></a></li>
        <?php endforeach; ?>
    </ul>
</nav>

<!-- HOME -->
<section class="hero" id="home">
    <div class="wrap">
        <a class="logo" href="#home" aria-label="<?= e($site['name']) ?>">
            <span class="logo-text">
                <span class="logo-name"><?= e($site['name']) ?></span>
                <span class="logo-sub"><?= e($site['tagline']) ?></span>
            </span>
            <span class="logo-k">K</span>
        </a>
    </div>
    <h1 class="wrap">Horizontaal gestuurde boringen</h1>
    <div class="wrap cta-row">
        <a class="btn" href="<?= $site['tel'] ?>">Contact</a>
    </div>
</section>

<!-- HDD -->
<section class="navy" id="hdd">
    <div class="wrap split">
        <div class="reveal">
            <h2>HDD</h2>
            <p>Geen fan van opengebroken straten, omleidingen en modderige bende? Wij ook niet. Daarom doen we aan horizontaal gestuurde boringen – of kortgezegd: HDD. Daarmee leggen we kabels en leidingen onder de grond aan zónder alles open te gooien.</p>
            <p>We boren onder obstakels door, zoals wegen, sloten of spoorlijnen, met een boorkop die we strak kunnen aansturen. Zo komt alles precies uit waar het moet. Handig bij lastige trajecten, en je ziet er boven de grond nauwelijks iets van.</p>
            <p>Het is snel, netjes, en vaak ook nog goedkoper dan graven. Dus of het nou gaat om stroom, water, gas of glasvezel: wij regelen het ondergronds, zonder gedoe.</p>
            <p class="lead">Meer weten? <a href="<?= $site['tel'] ?>">Bel</a> gerust met één van onze dames op kantoor.</p>
        </div>
        <img class="photo reveal" src="<?= $asset ?>media/c1198b0fc2b1bee854f749ac48560e4f.jpg" alt="Ditch Witch boormachine voor de loods" loading="lazy">
    </div>
</section>

<!-- BOORMACHINES -->
<section class="light" id="boormachines">
    <div class="wrap">
        <h2 class="center wide">Boormachines</h2>
        <p class="center wide intro">Wij werken graag met goed gereedschap en goede machines. Daarom vertrouwen wij op de boormachines van Ditch Witch. Betrouwbare, robuuste machines met kracht, precisie en gebruiksgemak die doen wat ze moeten doen.</p>
        <div class="machines">
            <?php foreach ($machines as $m): ?>
                <article class="machine wide reveal">
                    <h3><?= e($m['name']) ?></h3>
                    <ul>
                        <?php foreach ($m['specs'] as $s): ?><li><?= e($s) ?></li><?php endforeach; ?>
                    </ul>
                    <div class="imgbox"><img src="<?= $asset . $m['img'] ?>" alt="Ditch Witch <?= e($m['name']) ?>" loading="lazy"></div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- BOORMEESTER -->
<section class="navy" id="boormeester">
    <div class="wrap split">
        <div class="reveal">
            <h2>Lex Krabbe</h2>
            <p class="medium">Als je Lex Krabbe op een boorinstallatie ziet, dan weet je het meteen: hier is iemand aan het werk die weet wat ’ie doet. Geen poespas, geen grootspraak – gewoon rechttoe rechtaan vakmanschap. Lex draait al meer dan 35 jaar mee in de wereld van gestuurde boringen en heeft ondertussen zo’n beetje onder elk weiland, kanaal en bedrijventerrein van Nederland wel eens geboord.</p>
            <p class="medium">Wat Lex typeert? Een nuchtere kop, droge humor en een ijzersterk gevoel voor hoe een boring moet lopen. Hij leest de grond alsof het de krant is, en als er onderweg iets tegenzit, blijft hij gewoon rustig doorboren – letterlijk.</p>
            <p>Of het nou een simpele kruising is of een technisch ingewikkelde klus: Lex fixt het. Zonder gedoe, zonder grote verhalen. Gewoon zoals het hoort.</p>
        </div>
        <div class="video video-lex reveal">
            <video src="<?= $asset ?>video/5a3588be41d324e7d28571d71ae99951.mp4" poster="<?= $asset ?>video/1c2f96a1673357341b227da74cabff8e.jpg" playsinline muted loop preload="metadata"></video>
        </div>
    </div>
</section>

<!-- COMPLEET -->
<section class="blue-grey compleet">
    <div class="wrap">
        <h2 class="center wide">Compleet</h2>
        <div class="text wide reveal">
            <p>Lex Krabbe verzorgt het volledige traject rondom de boring zodat het werk efficiënt en volgens de juiste specificaties wordt uitgevoerd. Graafwerk, zoals het realiseren van een in- of uittrede kan door Lex zelf worden uitgevoerd. Hij kan de buis voorzien van een intrektouw, bedoeld voor het inbrengen van kabels of leidingen. Waar nodig wordt de buis gevuld met water, wat de wrijving verlaagt en de kabels tijdens het intrekken koelt – een belangrijk detail bij gevoelige of warmtegevoelige tracés. De gaten worden netjes leeggezogen met eigen zuigwagen én wij zorgen voor de afvoer van de bentoniet.</p>
            <p>Voor langere tracés waar buizen gekoppeld moeten worden, is er een gecertificeerde spiegellasser, ook geschikt voor PE-gasbuizen conform de geldende normen. Daarnaast kan Lex ook persingen uitvoeren en, indien nodig, zorgen voor een officiële verkeersafzetting of -omleiding voor een veilige werkomgeving conform CROW-richtlijnen.</p>
            <p>De boring wordt direct GPS ingemeten en na de boring ontvangt u binnen één week de revisie tekening van ons.</p>
        </div>
        <img class="banner reveal" src="<?= $asset ?>media/8b7c2f208463c573aa151f5d8d319cae.jpg" alt="Zuigwagen en boormachine op locatie" loading="lazy">
    </div>
</section>

<!-- PIPE REEL -->
<section class="navy pipe" id="pipe-reel">
    <div class="wrap split">
        <div class="wide reveal">
            <h2>Buizenhaspels van<br>Pipe Reel</h2>
            <p>Lex Krabbe staat bekend om zijn praktische aanpak en kiest bewust voor kwaliteit en gebruiksgemak in zijn werk. De buizenhaspels van Pipe Reel sluiten daar perfect op aan. Dankzij hun degelijke constructie en slimme ontwerp maken ze het uitrollen en opbergen van buizen een stuk efficiënter. Lex waardeert vooral de robuustheid en betrouwbaarheid van de haspels, die hem tijd én moeite besparen op de werkvloer.</p>
            <h2 style="margin:40px 0 6px">Interesse?</h2>
            <p style="margin:0">Raadpleeg <a href="http://www.pipereel.nl" target="_blank" rel="noopener">www.pipereel.nl</a> voor meer informatie.</p>
        </div>
        <div class="stack reveal">
            <img class="logo-img" src="<?= $asset ?>media/c6e61b2eebb04b187daef612d64d42f7.png" alt="Pipe Reel logo" loading="lazy">
            <img src="<?= $asset ?>media/914b633f6c4508c7f2b655b8f8e20aa5.png" alt="Pipe Reel buizenhaspel" loading="lazy">
        </div>
    </div>
</section>

<!-- CERTIFICERINGEN -->
<section class="red" id="certificeringen">
    <div class="wrap split">
        <div class="reveal">
            <h2>Certificeringen? <span>Natuurlijk.</span></h2>
            <p>Lex mag dan wel een nuchtere kop hebben, maar als het om veiligheid en vakbekwaamheid gaat, is alles gewoon netjes geregeld. Hij beschikt over alle benodigde certificeringen om het werk goed én volgens de regels te doen.</p>
            <p>Van KIWA en CKB tot ISO 9001 en VCA-VOL, Lex heeft het allemaal op zak. Ook al doet hij het al jaren, hij weet: je graaft niet zomaar ergens zonder te checken wat eronder ligt.</p>

            <h3>Erkend leerbedrijf S-BB</h3>
            <p class="wide justify">Onder begeleiding van ervaren vakmensen zoals Lex, leer je de fijne kneepjes van het vak. Met een beetje inzet en een gezonde dosis nieuwsgierigheid, kom je bij ons een heel eind. Op zoek naar een leerplek waar je niet wordt behandeld als stagiair, maar als collega? <a href="<?= $site['tel'] ?>">Bel ons gerust!</a></p>
        </div>
        <div class="certs reveal">
            <?php foreach ($certs as $c): ?>
                <div class="cert">
                    <div class="icon-box <?= $c['type'] ?>"><img src="<?= $asset . $c['img'] ?>" alt="<?= e($c['label']) ?>" loading="lazy"></div>
                    <?= e($c['label']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- SOCIAL BLOGS -->
<section class="navy social" id="social-blogs">
    <div class="wrap">
        <div class="video video-social reveal">
            <video src="<?= $asset ?>video/9988408aef1f0af1670fc379192d65e3.mp4" poster="<?= $asset ?>video/c32f1ff877a0194c626b2e9827f8e885.jpg" playsinline muted loop preload="metadata"></video>
        </div>
    </div>
</section>

<!-- CONTACT -->
<footer class="contact" id="contact">
    <div class="card">
        <h2>KANTOOR</h2>
        <p><?= e($site['street']) ?></p>
        <p><?= e($site['city']) ?></p>
        <p><a href="<?= $site['tel'] ?>" style="text-decoration:none"><?= e($site['phone']) ?></a></p>
        <div class="icons">
            <a href="<?= $site['tel'] ?>" aria-label="Bellen">
                <svg viewBox="0 0 24 24" fill="#000"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1l-2.2 2.2Z"/></svg>
            </a>
            <a href="mailto:<?= e($site['email']) ?>" aria-label="E-mail">
                <svg viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.8"><rect x="2.5" y="5" width="19" height="14" rx="1"/><path d="m3 6 9 7 9-7M3 18l6.5-6M21 18l-6.5-6"/></svg>
            </a>
            <a href="<?= e($site['facebook']) ?>" target="_blank" rel="noopener" aria-label="Facebook">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="12" fill="#000"/><path fill="#fff" d="M13.4 24v-8.4h2.8l.4-3.3h-3.2v-2.1c0-.95.26-1.6 1.63-1.6h1.74V5.66a23 23 0 0 0-2.54-.13c-2.51 0-4.23 1.53-4.23 4.35v2.43H7.2v3.3H10V24Z"/></svg>
            </a>
        </div>
    </div>
    <div class="legal">
        <a href="<?= e($site['terms']) ?>" target="_blank" rel="noopener">Algemene voorwaarden</a><br>
        &copy; <?= date('Y') ?> Lex Krabbe BV
    </div>
</footer>

<script>
(() => {
    const nav = document.getElementById('nav');
    const burger = document.getElementById('burger');
    const links = [...nav.querySelectorAll('a[data-target]')];

    // Mobile menu
    burger.addEventListener('click', () => {
        const open = nav.classList.toggle('open');
        burger.setAttribute('aria-expanded', open);
    });
    links.forEach(a => a.addEventListener('click', () => {
        nav.classList.remove('open');
        burger.setAttribute('aria-expanded', 'false');
    }));

    // Active link while scrolling
    const sections = links.map(a => document.getElementById(a.dataset.target)).filter(Boolean);
    const setActive = () => {
        const y = window.scrollY + window.innerHeight * 0.35;
        let current = sections[0];
        sections.forEach(s => { if (s.offsetTop <= y) current = s; });
        if (window.innerHeight + window.scrollY >= document.body.scrollHeight - 4) current = sections[sections.length - 1];
        links.forEach(a => a.classList.toggle('active', a.dataset.target === current.id));
    };
    window.addEventListener('scroll', setActive, { passive: true });
    setActive();

    // Fade-in on scroll
    const io = new IntersectionObserver(entries => {
        entries.forEach(en => { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
    }, { threshold: 0.15 });
    document.querySelectorAll('.reveal').forEach(el => io.observe(el));

    // Video players: click to play/pause, mute toggle, progress bar, pause when out of view
    const icons = {
        play: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8.25 4.2 19.3 10.8c.7.4.9 1.3.5 2-.13.18-.3.34-.5.46L8.25 19.8A1.47 1.47 0 0 1 6 18.57V5.43C6 4.64 6.66 4 7.48 4c.27 0 .54.07.77.2Z"/></svg>',
        muted: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M11 5 6 9H3v6h3l5 4V5Z"/><path d="m16 9 5 6m0-6-5 6"/></svg>',
        sound: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M11 5 6 9H3v6h3l5 4V5Z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13"/></svg>'
    };
    document.querySelectorAll('.video').forEach(box => {
        const v = box.querySelector('video');
        const play = Object.assign(document.createElement('button'), { className: 'play', innerHTML: icons.play });
        play.setAttribute('aria-label', 'Afspelen');
        const mute = Object.assign(document.createElement('button'), { className: 'mute', innerHTML: icons.muted });
        mute.setAttribute('aria-label', 'Geluid aan/uit');
        const bar = Object.assign(document.createElement('div'), { className: 'bar' });
        box.append(play, mute, bar);

        const toggle = () => v.paused ? v.play() : v.pause();
        box.addEventListener('click', e => { if (!e.target.closest('.mute')) toggle(); });
        mute.addEventListener('click', () => {
            v.muted = !v.muted;
            mute.innerHTML = v.muted ? icons.muted : icons.sound;
        });
        v.addEventListener('play', () => box.classList.add('playing'));
        v.addEventListener('pause', () => box.classList.remove('playing'));
        v.addEventListener('timeupdate', () => { bar.style.width = (v.currentTime / v.duration * 100 || 0) + '%'; });

        new IntersectionObserver(([en]) => { if (!en.isIntersecting && !v.paused) v.pause(); }, { threshold: 0.2 }).observe(box);
    });
})();
</script>
</body>
</html>