<?php
// index.php - Boringsopdracht invoerformulier (alles in een bestand)
$success = false;
$errors  = [];

function post_val(string $key, string $default = ''): string {
    return trim($_POST[$key] ?? $default);
}

// Eenvoudige .env-lezer: KEY=VALUE per regel, # voor commentaar, quotes optioneel
function laad_env(string $pad): array {
    $env = [];
    foreach (file($pad, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $regel) {
        $regel = trim($regel);
        if ($regel === '' || $regel[0] === '#' || !str_contains($regel, '=')) continue;
        [$key, $val] = explode('=', $regel, 2);
        $val = trim($val);
        if (strlen($val) >= 2 && ($val[0] === '"' || $val[0] === "'") && $val[0] === substr($val, -1)) {
            $val = substr($val, 1, -1);
        }
        $env[trim($key)] = $val;
    }
    return $env;
}

/* ===== XLSX generatie (zonder externe libraries, via ZipArchive) ===== */

function xlsx_esc(string $s): string {
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

/* ===== XLSX generatie op basis van template.xlsx ===== */

// Vervangt de inhoud van een cel in sheet-XML door tekst (stijl/borders blijven behouden)
function xlsx_set_cell(string $xml, string $ref, string $tekst): string {
    $esc = xlsx_esc($tekst);
    $patroon = '/<c r="' . $ref . '"([^>]*?)(?:\/>|>.*?<\/c>)/s';
    return preg_replace_callback($patroon, function ($m) use ($ref, $esc) {
        preg_match('/s="(\d+)"/', $m[1], $sm);
        $s = isset($sm[1]) ? ' s="' . $sm[1] . '"' : '';
        return '<c r="' . $ref . '"' . $s . ' t="inlineStr"><is><t xml:space="preserve">' . $esc . '</t></is></c>';
    }, $xml, 1);
}

function make_xlsx(array $data, string $path): bool {
    $sjabloon = __DIR__ . '/template_v3.xlsx';
    if (!is_file($sjabloon) || !copy($sjabloon, $path)) return false;

    $jn = fn($v) => $v ? 'JA' : 'NEE';
    $klicTekst = [
        'ja'        => 'JA',
        'nee'       => 'NEE',
        'volgt_nog' => 'VOLGT NOG',
    ][$data['klic_melding']] ?? 'NEE';

    // Straat en plaats splitsen op de laatste komma ("Dorpsstraat, Schiedam")
    $straat = $data['straatnaam'];
    $plaats = '';
    if (($pos = strrpos($straat, ',')) !== false) {
        $plaats = trim(substr($straat, $pos + 1));
        $straat = trim(substr($straat, 0, $pos));
    }

    // Buis/bundel-regels + samenvattingen
    $diam = fn(array $it) => ($it['diameter_buis'] === 'anders' && $it['diameter_anders'] !== '')
        ? $it['diameter_anders'] : $it['diameter_buis'];
    $itemRegels = [];
    $meters     = [];
    $lassen     = 0;
    $geleverd   = 0;
    foreach ($data['items'] as $item) {
        if ($item['meter'] > 0) $meters[] = rtrim(rtrim(number_format($item['meter'], 1, '.', ''), '0'), '.');
        if ($item['type'] === 'bundel') {
            $delen = [];
            foreach ($item['buizen'] as $b) {
                $delen[] = $b['aantal'] . 'x ' . $diam($b);
                if ($b['lasser'])   $lassen   = 1;
                if ($b['levering']) $geleverd = 1;
            }
            $itemRegels[] = 'Bundel: ' . implode(' + ', $delen);
        } else {
            $itemRegels[] = '1x ' . $diam($item) . ' ' . $item['kleur'];
            if ($item['lasser'])   $lassen   = 1;
            if ($item['levering']) $geleverd = 1;
        }
    }
    $meters = array_unique($meters);

    // Bekende gegevens -> vaste cellen op de voorkant (linkerdeel)
    $map = [
        'A4'  => 'Datum: '                 . ($data['datum_boring'] ?: ''),
        'A5'  => 'Projectnr: '             . $data['projectnummer'],
        'A7'  => 'SDR-type: '              . $data['sdr_type'],
        'A8'  => 'Opdrachtgever: '         . $data['opdrachtgever'],
        'A9'  => 'Te: '                    . $plaats,
        'A10' => 'Boring uitgezet: '       . $jn($data['boring_uitgezet']),
        'A11' => 'Uitvoerder: '            . $data['naam_uitvoerder'],
        'A12' => 'Tel. '                   . $data['tel_uitvoerder'],
        'A13' => 'Boorplan aanwezig: '     . $jn($data['boorplan_aanwezig']),
        'A14' => 'Voorman: '               . $data['naam_voorman'],
        'A15' => 'Tel. '                   . $data['tel_voorman'],
        'A16' => 'Toegewezen aan Lex: '    . $jn($data['toegewezen_lex']),
        'A17' => 'Plaats boring: '         . $plaats,
        'A20' => trim($straat . ' ' . $data['huisnummer']),
        'A21' => 'KLIC-melding: ' . $klicTekst,
        'A23' => 'In-/uittrede graven: '   . $jn($data['in_uittrede_graven']),
        'A24' => 'Lengte boring: ' . ($meters ? implode(' / ', $meters) : '............') . ' meter',
        'A28' => 'Lassen: '           . $jn($lassen),
        'A29' => 'Water in de buis: ' . $jn($data['water_in_buis']),
        'A30' => 'Geleverde buis: '   . $jn($geleverd),
        'A31' => 'Geleverde touw: '   . $jn($data['levering_touw']),
        'A34' => 'Bentonietafvoer: '  . $jn($data['bentonietafvoer']),
    ];

    // Buis/bundel-regels verdelen over de vrije regels onder "Aantal, type en diameter buis:"
    $slots = ['A27', 'A33', 'A35'];
    foreach ($slots as $i => $ref) {
        if (!isset($itemRegels[$i])) break;
        $map[$ref] = ($i === count($slots) - 1 && count($itemRegels) > count($slots))
            ? implode('; ', array_slice($itemRegels, $i))
            : $itemRegels[$i];
    }

    // Achterkant (LMRA-checklist): KLIC-nummer + datum uitgifte aanvullen indien bekend
    if ($data['klic_nummer'] !== '') {
        $map['A61'] = '☐  KLIC-melding aanwezig  (nr: ' . $data['klic_nummer']
                    . ($data['klic_datum'] ? ' - uitgifte: ' . $data['klic_datum'] : '') . ')';
    }
    // Achterkant: extra opmerking uit het intakeformulier vast alvast invullen
    if ($data['extra_opmerking'] !== '') {
        $map['A44'] = $data['extra_opmerking'];
    }

    $zip = new ZipArchive();
    if ($zip->open($path) !== true) return false;
    $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($xml === false) { $zip->close(); return false; }

    foreach ($map as $ref => $tekst) {
        $xml = xlsx_set_cell($xml, $ref, $tekst);
    }

    // Vaste paginascheiding: rij 1-37 = voorkant, rij 38-73 = achterkant
    if (strpos($xml, '<rowBreaks') === false) {
        $brk = '<rowBreaks count="1" manualBreakCount="1"><brk id="37" max="16383" man="1"/></rowBreaks>';
        if (strpos($xml, '</headerFooter>') !== false) {
            $xml = str_replace('</headerFooter>', '</headerFooter>' . $brk, $xml);
        } else {
            $xml = preg_replace('/(<pageSetup[^>]*\/>)/', '$1' . $brk, $xml, 1);
        }
    }

    $zip->addFromString('xl/worksheets/sheet1.xml', $xml);

    // 'boorprofiel'-grafiekblad verwijderen zodat het bestand (ook in mail-preview)
    // direct het ingevulde formulier toont
    $wb = $zip->getFromName('xl/workbook.xml');
    if ($wb !== false && preg_match('/<sheet name="boorprofiel"[^>]*r:id="(rId\d+)"[^>]*\/>/', $wb, $m)) {
        $rid = $m[1];
        $wb  = str_replace($m[0], '', $wb);
        $wb  = preg_replace('/activeTab="\d+"/', 'activeTab="0"', $wb);
        // Print_Area verwijst met localSheetId naar de sheetvolgorde; na het verwijderen
        // van het eerste tabblad (boorprofiel) schuift Blad1 van index 1 naar index 0.
        $wb  = preg_replace('/(_xlnm\.Print_Area" localSheetId=")\d+(")/', '${1}0${2}', $wb);
        $zip->addFromString('xl/workbook.xml', $wb);

        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($rels !== false && preg_match('/<Relationship Id="' . $rid . '"[^>]*Target="([^"]+)"[^>]*\/>/', $rels, $rm)) {
            $zip->addFromString('xl/_rels/workbook.xml.rels', str_replace($rm[0], '', $rels));
            $doel = 'xl/' . ltrim($rm[1], '/'); // bv. xl/chartsheets/sheet1.xml
            $ct = $zip->getFromName('[Content_Types].xml');
            if ($ct !== false) {
                $ct = preg_replace('/<Override PartName="\/' . preg_quote($doel, '/') . '"[^>]*\/>/', '', $ct);
                $zip->addFromString('[Content_Types].xml', $ct);
            }
            $zip->deleteName($doel);
            $zip->deleteName('xl/chartsheets/_rels/sheet1.xml.rels');
        }
    }

    return $zip->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post_val('straatnaam') === '')      $errors[] = 'Straatnaam + plaats is verplicht.';
    if (post_val('opdrachtgever') === '')   $errors[] = 'Opdrachtgever is verplicht.';
    if (post_val('naam_uitvoerder') === '') $errors[] = 'Naam uitvoerder is verplicht.';
    if (post_val('projectnummer') === '')   $errors[] = 'Projectnummer is verplicht.';
    if (($_POST['klic_melding'] ?? 'nee') === 'ja' && post_val('klic_nummer') === '')
        $errors[] = 'Vul het KLIC-nummer in bij aanwezige KLIC-melding.';

    // KLIC datum uitgifte mag niet meer dan 1 maand voor de datum boring liggen
    // (of 1 maand voor vandaag als er geen datum boring is)
    $klic_datum_val = post_val('klic_datum');
    if ($klic_datum_val !== '') {
        $ref_datum = post_val('datum_boring') !== '' ? post_val('datum_boring') : date('Y-m-d');
        $grens = date('Y-m-d', strtotime($ref_datum . ' -1 month'));
        if ($klic_datum_val < $grens) {
            $errors[] = 'De KLIC datum uitgifte mag niet meer dan 1 maand voor '
                      . (post_val('datum_boring') !== '' ? 'de datum van de boring' : 'vandaag')
                      . ' liggen (op zijn vroegst ' . $grens . ').';
        }
    }

    if (empty($errors)) {
        $data = [
            'datum_boring'       => $_POST['datum_boring'] ?? null,
            'straatnaam'         => post_val('straatnaam'),
            'huisnummer'         => post_val('huisnummer'),
            'opdrachtgever'      => post_val('opdrachtgever'),
            'naam_uitvoerder'    => post_val('naam_uitvoerder'),
            'tel_uitvoerder'     => post_val('tel_uitvoerder'),
            'projectnummer'      => post_val('projectnummer'),
            'boorplan_aanwezig'  => ($_POST['boorplan_aanwezig'] ?? 'nee') === 'ja' ? 1 : 0,
            'boorplan_pad'       => null,
            'boorplan_naam'      => null,
            'boring_uitgezet'    => ($_POST['boring_uitgezet'] ?? 'nee') === 'ja' ? 1 : 0,
            'naam_voorman'       => post_val('naam_voorman'),
            'tel_voorman'        => post_val('tel_voorman'),
            'sdr_type'           => $_POST['sdr_type'] ?? 'SDR11',
            'levering_touw'      => ($_POST['levering_touw']      ?? 'nee') === 'ja' ? 1 : 0,
            'water_in_buis'      => ($_POST['water_in_buis']      ?? 'nee') === 'ja' ? 1 : 0,
            'bentonietafvoer'    => ($_POST['bentonietafvoer']    ?? 'nee') === 'ja' ? 1 : 0,
            'in_uittrede_graven' => ($_POST['in_uittrede_graven'] ?? 'nee') === 'ja' ? 1 : 0,
            'klic_melding'       => $_POST['klic_melding'] ?? 'nee',
            'klic_nummer'        => post_val('klic_nummer'),
            'klic_datum'         => $_POST['klic_datum'] ?? null,
            'toegewezen_lex'     => ($_POST['toegewezen_lex']     ?? 'nee') === 'ja' ? 1 : 0,
            'extra_opmerking'    => post_val('extra_opmerking'),
            'items'              => [],
        ];

        // Boorplan bestand (upload) - wordt alleen als mailbijlage meegestuurd
        $boorplan_status = null;
        if ($data['boorplan_aanwezig']) {
            $fout = $_FILES['boorplan_bestand']['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($fout === UPLOAD_ERR_OK) {
                $data['boorplan_pad']  = $_FILES['boorplan_bestand']['tmp_name'];
                $data['boorplan_naam'] = basename($_FILES['boorplan_bestand']['name']);
                $boorplan_status = 'Boorplan "' . $data['boorplan_naam'] . '" ontvangen.';
            } elseif ($fout === UPLOAD_ERR_NO_FILE) {
                $boorplan_status = 'Let op: boorplan aanwezig aangevinkt, maar geen bestand gekozen.';
            } elseif (in_array($fout, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                $boorplan_status = 'Let op: het boorplan is te groot om te uploaden en is NIET meegestuurd.';
            } else {
                $boorplan_status = 'Let op: uploaden van het boorplan is mislukt (code ' . $fout . ') en is NIET meegestuurd.';
            }
        }

        if (!empty($_POST['items']) && is_array($_POST['items'])) {
            // Leest alle buisvelden (behalve meter) incl. leveringsketen-logica
            $lees_buis = function (array $item): array {
                $e = [
                    'kleur'           => $item['kleur'] ?? '',
                    'mantel_medium'   => $item['mantel_medium'] ?? '',
                    'levering'        => ($item['levering'] ?? 'nee') === 'ja' ? 1 : 0,
                    'rol_lengtes'     => $item['rol_lengtes'] ?? '',
                    'lasser'          => ($item['lasser']     ?? 'nee') === 'ja' ? 1 : 0,
                    'rail_eruit'      => ($item['rail_eruit'] ?? 'nee') === 'ja' ? 1 : 0,
                    'trekkop'         => ($item['trekkop']    ?? 'nee') === 'ja' ? 1 : 0,
                    'diameter_buis'   => $item['diameter_buis'] ?? '',
                    'diameter_anders' => trim($item['diameter_anders'] ?? ''),
                ];
                // Vervolgvelden alleen relevant bij levering = ja
                if (!$e['levering']) {
                    $e['rol_lengtes'] = '';
                    $e['lasser'] = $e['rail_eruit'] = $e['trekkop'] = 0;
                }
                if ($e['rol_lengtes'] !== 'op lengtes') {
                    $e['lasser'] = $e['rail_eruit'] = $e['trekkop'] = 0;
                }
                if (!$e['lasser'])     $e['rail_eruit'] = $e['trekkop'] = 0;
                if (!$e['rail_eruit']) $e['trekkop'] = 0;
                return $e;
            };

            foreach ($_POST['items'] as $item) {
                $type  = $item['type'] ?? 'buis';
                $entry = $lees_buis($item);
                $entry['type']  = $type;
                $entry['meter'] = (float)($item['meter'] ?? 0);
                if ($type === 'bundel') {
                    $entry['buizen'] = [];
                    foreach (($item['bundel_buizen'] ?? []) as $bb) {
                        if (!is_array($bb)) continue;
                        $buis = $lees_buis($bb);
                        $buis['aantal'] = max(1, (int)($bb['aantal'] ?? 1));
                        $entry['buizen'][] = $buis;
                    }
                }
                $data['items'][] = $entry;
            }
        }

        // TODO: bewaar $data in database of verwerk verder

        // Excel-bestand genereren
        $dir = __DIR__ . '/opdrachten';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $proj     = preg_replace('/[^A-Za-z0-9_-]/', '_', $data['projectnummer']);
        $fileName = 'opdracht_' . $proj . '_' . date('Ymd_His') . '.xlsx';
        $xlsxPad  = $dir . '/' . $fileName;
        if (make_xlsx($data, $xlsxPad)) {
            $xlsx_link = 'opdrachten/' . rawurlencode($fileName);
        }

        // Excel per mail versturen
        if (!empty($xlsx_link)) {
            $envPad = __DIR__ . '/.env';
            if (is_readable($envPad)) {
                require_once __DIR__ . '/mailer.php';
                $env = laad_env($envPad);
                $cfg = [
                    'smtp_host'      => $env['SMTP_HOST']           ?? 'smtp.gmail.com',
                    'smtp_port'      => $env['SMTP_PORT']           ?? 465,
                    'gebruiker'      => $env['SMTP_GEBRUIKER']      ?? '',
                    'app_wachtwoord' => $env['SMTP_APP_WACHTWOORD'] ?? '',
                    'afzender_naam'  => $env['SMTP_AFZENDER_NAAM']  ?? 'Boorformulier',
                ];
                $bijlagen = [['pad' => $xlsxPad, 'naam' => $fileName]];
                if (!empty($data['boorplan_pad'])) {
                    $bijlagen[] = ['pad' => $data['boorplan_pad'], 'naam' => $data['boorplan_naam']];
                }
                $tekst = "Er is een nieuwe boringsopdracht ingevoerd.\n\n"
                       . "Projectnummer: {$data['projectnummer']}\n"
                       . "Opdrachtgever: {$data['opdrachtgever']}\n"
                       . "Locatie: {$data['straatnaam']} {$data['huisnummer']}\n"
                       . "Datum boring: " . ($data['datum_boring'] ?: 'onbekend') . "\n\n"
                       . "Alle details staan in het bijgevoegde Excel-bestand."
                       . (!empty($data['boorplan_pad']) ? "\nHet boorplan is als bijlage toegevoegd." : '');
                $mail_status = verstuur_opdracht_mail(
                    $cfg,
                    'oskar.krabbe300607@gmail.com',
                    'Nieuwe boringsopdracht - ' . $data['projectnummer'] . ' (' . date('d-m-Y H:i:s') . ')',
                    $tekst,
                    $bijlagen
                );
            } else {
                $mail_status = ['ok' => false, 'fout' => '.env-bestand niet gevonden - mail niet verstuurd.'];
            }
        }

        $success = true;
    }
}

function old(string $key, string $default = ''): string {
    return htmlspecialchars($_POST[$key] ?? $default, ENT_QUOTES);
}
function radio_if(string $key, string $val, string $default = 'nee'): string {
    return (($_POST[$key] ?? $default) === $val) ? 'checked' : '';
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Boringsopdracht invoeren</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --accent:       #1D9E75;
      --accent-hover: #0F6E56;
      --accent-light: #E1F5EE;
      --text-primary:   #1a1a18;
      --text-secondary: #5f5e5a;
      --text-tertiary:  #9a9893;
      --bg-page:    #f4f3ef;
      --bg-card:    #ffffff;
      --bg-input:   #ffffff;
      --bg-muted:   #f8f7f4;
      --border:     rgba(0,0,0,0.12);
      --border-md:  rgba(0,0,0,0.20);
      --radius-sm:  6px;
      --radius-md:  8px;
      --radius-lg:  12px;
      --font: 'DM Sans', system-ui, sans-serif;
      --shadow-card: 0 1px 3px rgba(0,0,0,0.07), 0 1px 2px rgba(0,0,0,0.04);
    }

    body {
      font-family: var(--font);
      background: var(--bg-page);
      color: var(--text-primary);
      font-size: 15px; line-height: 1.6;
      min-height: 100vh;
      padding: 2rem 1rem 4rem;
    }

    .page-wrap { max-width: 780px; margin: 0 auto; }

    .page-header { display: flex; align-items: center; gap: 14px; margin-bottom: 2rem; }
    .logo-mark {
      width: 44px; height: 44px;
      border-radius: var(--radius-md);
      background: var(--accent); color: white;
      font-size: 15px; font-weight: 600;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .page-header h1 { font-size: 20px; font-weight: 600; line-height: 1.2; }
    .subtitle { font-size: 13px; color: var(--text-secondary); margin-top: 2px; }

    .alert {
      padding: 12px 16px; border-radius: var(--radius-md);
      margin-bottom: 1.25rem; font-size: 14px; line-height: 1.5;
    }
    .alert-success { background: var(--accent-light); color: var(--accent-hover); border-left: 3px solid var(--accent); }
    .alert-error   { background: #FCEBEB; color: #A32D2D; border-left: 3px solid #E24B4A; }
    .alert ul { margin: 6px 0 0 18px; }

    .card {
      background: var(--bg-card);
      border: 0.5px solid var(--border);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-card);
      margin-bottom: 1.25rem;
      overflow: hidden;
    }
    .card-header {
      padding: 10px 18px;
      background: var(--bg-muted);
      border-bottom: 0.5px solid var(--border);
      font-size: 12px; font-weight: 600;
      letter-spacing: 0.06em; text-transform: uppercase;
      color: var(--text-secondary);
    }
    .card-body { padding: 18px; display: flex; flex-direction: column; gap: 14px; }

    .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .field-row.three { grid-template-columns: 1fr 1fr 1fr; }
    .col-2 { grid-column: span 2; }

    .field { display: flex; flex-direction: column; gap: 5px; }
    label { font-size: 13px; font-weight: 500; color: var(--text-secondary); }
    .opt { font-weight: 400; color: var(--text-tertiary); font-size: 12px; }
    .req { color: #E24B4A; }

    input[type=text], input[type=date], input[type=tel], input[type=number], select, textarea {
      font-family: var(--font);
      font-size: 14px; color: var(--text-primary);
      background: var(--bg-input);
      border: 0.5px solid var(--border-md);
      border-radius: var(--radius-md);
      padding: 8px 11px; height: 38px; width: 100%;
      outline: none;
      transition: border-color 0.15s, box-shadow 0.15s;
      appearance: none; -webkit-appearance: none;
    }
    textarea { height: auto; min-height: 90px; resize: vertical; }
    select {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%235f5e5a' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 10px center;
      padding-right: 34px; cursor: pointer;
    }
    input:focus, select:focus, textarea:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(29,158,117,0.15);
    }
    input::placeholder, textarea::placeholder { color: var(--text-tertiary); }

    input[type=file] {
      font-family: var(--font); font-size: 13px; color: var(--text-secondary);
    }
    input[type=file]::file-selector-button {
      font-family: var(--font); font-size: 13px; font-weight: 500;
      background: var(--bg-muted); color: var(--text-primary);
      border: 0.5px solid var(--border-md); border-radius: var(--radius-sm);
      padding: 6px 12px; margin-right: 10px; cursor: pointer;
    }

    /* "Anders" vrij-invoerveld onder een dropdown */
    .anders-input {
      display: none;
      margin-top: 6px;
    }
    .anders-input.visible { display: block; }

    /* Radiobuttons */
    .radio-field { display: flex; flex-direction: column; gap: 6px; }
    .radio-label { font-size: 13px; font-weight: 500; color: var(--text-secondary); }
    .radio-group { display: flex; gap: 18px; flex-wrap: wrap; }
    .radio-opt {
      display: flex; align-items: center; gap: 7px;
      font-size: 14px; font-weight: 400; color: var(--text-primary);
      cursor: pointer; line-height: 1.4;
    }
    .radio-opt input[type=radio] {
      width: 16px; height: 16px; margin: 0;
      accent-color: var(--accent); cursor: pointer;
    }

    .cond { display: none; }
    .cond.visible { display: flex; }

    .btn-group {
      display: inline-flex;
      border: 0.5px solid var(--border-md); border-radius: var(--radius-md); overflow: hidden;
    }
    .seg-btn {
      padding: 7px 20px; border: none; background: var(--bg-card);
      font-family: var(--font); font-size: 14px; font-weight: 500;
      color: var(--text-secondary); cursor: pointer;
      transition: background 0.15s, color 0.15s;
      border-right: 0.5px solid var(--border);
    }
    .seg-btn:last-child { border-right: none; }
    .seg-btn.active { background: var(--accent); color: white; }
    .seg-btn:hover:not(.active) { background: var(--bg-muted); }

    #pipe-list { display: flex; flex-direction: column; gap: 12px; }
    .pipe-item {
      border: 0.5px solid var(--border-md); border-radius: var(--radius-md);
      padding: 14px; background: var(--bg-muted);
    }
    .pipe-item-top {
      display: flex; align-items: center;
      justify-content: space-between; margin-bottom: 12px;
    }
    .pipe-item-label {
      font-size: 13px; font-weight: 600; color: var(--text-secondary);
      text-transform: uppercase; letter-spacing: 0.04em;
    }
    .btn-remove {
      background: none; border: none; cursor: pointer;
      color: var(--text-tertiary); font-size: 20px; line-height: 1;
      padding: 2px 6px; border-radius: var(--radius-sm);
      transition: background 0.15s, color 0.15s;
    }
    .btn-remove:hover { background: #FCEBEB; color: #A32D2D; }

    .type-group { display: flex; gap: 8px; margin-bottom: 12px; }
    .type-btn {
      flex: 1; padding: 7px;
      border: 0.5px solid var(--border-md); border-radius: var(--radius-md);
      background: var(--bg-card); font-family: var(--font);
      font-size: 13px; font-weight: 500; cursor: pointer;
      transition: all 0.15s; color: var(--text-secondary);
    }
    .type-btn.active { background: var(--accent); color: white; border-color: var(--accent); }

    .sub-box {
      background: var(--bg-card); border: 0.5px solid var(--border);
      border-radius: var(--radius-md); padding: 12px;
      display: flex; flex-direction: column; gap: 10px;
    }
    .sub-divider { border: none; border-top: 0.5px solid var(--border); margin: 2px 0; }

    .add-row-btn {
      display: flex; align-items: center; gap: 8px;
      background: none; border: 1px dashed var(--border-md);
      border-radius: var(--radius-md); padding: 10px 14px;
      font-family: var(--font); font-size: 14px; font-weight: 500;
      color: var(--accent-hover); cursor: pointer; width: 100%;
      transition: background 0.15s, border-color 0.15s;
    }
    .add-row-btn:hover { background: var(--accent-light); border-color: var(--accent); }
    .plus-icon { font-size: 18px; line-height: 1; }

    .klic-extra { display: none; grid-template-columns: 1fr 1fr; gap: 14px; }
    .klic-extra.visible { display: grid; }

    .boorplan-extra { display: none; }
    .boorplan-extra.visible { display: flex; }

    .notice {
      font-size: 13px; color: var(--accent-hover);
      background: var(--accent-light);
      border-left: 3px solid var(--accent);
      border-radius: 0 var(--radius-md) var(--radius-md) 0;
      padding: 10px 14px; line-height: 1.5;
    }

    .form-actions { display: flex; justify-content: flex-end; padding: 4px 0 2rem; }
    .btn-primary {
      background: var(--accent); color: white; border: none;
      border-radius: var(--radius-md); padding: 11px 32px;
      font-family: var(--font); font-size: 15px; font-weight: 600;
      cursor: pointer; transition: background 0.15s, transform 0.1s;
    }
    .btn-primary:hover  { background: var(--accent-hover); }
    .btn-primary:active { transform: scale(0.98); }

    @media (max-width: 580px) {
      .field-row       { grid-template-columns: 1fr; }
      .field-row.three { grid-template-columns: 1fr; }
      .col-2           { grid-column: span 1; }
      .klic-extra      { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<div class="page-wrap">
  <header class="page-header">
    <div class="logo-mark">LK</div>
    <div>
      <h1>Nieuwe boringsopdracht</h1>
      <p class="subtitle">Lex Krabbe BV — Horizontaal gestuurd boren</p>
    </div>
  </header>

  <?php if ($success): ?>
    <div class="alert alert-success">
      <strong>Opdracht opgeslagen!</strong> De boringsopdracht is succesvol ingevoerd.
      <?php if (!empty($xlsx_link)): ?>
        <br><a href="<?= htmlspecialchars($xlsx_link) ?>" download style="color:var(--accent-hover);font-weight:600">
          Download Excel-bestand
        </a>
      <?php endif; ?>
      <?php if (isset($boorplan_status)): ?>
        <br><?= htmlspecialchars($boorplan_status) ?>
      <?php endif; ?>
      <?php if (isset($mail_status)): ?>
        <br><?= $mail_status['ok']
              ? 'Mail verstuurd met ' . (int)($mail_status['aantal_bijlagen'] ?? 1) . ' bijlage(n).'
              : 'Mail versturen MISLUKT: ' . htmlspecialchars($mail_status['fout']) ?>
      <?php else: ?>
        <br>Er is geen mail verstuurd.
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
      <strong>Controleer het formulier:</strong>
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="POST" action="" enctype="multipart/form-data" novalidate>

    <!-- ===== SECTIE 1: ALGEMEEN ===== -->
    <section class="card">
      <div class="card-header">Algemeen</div>
      <div class="card-body">

        <div class="field">
          <label for="datum_boring">Datum boring <span class="opt">(optioneel)</span></label>
          <input type="date" id="datum_boring" name="datum_boring"
                 value="<?= old('datum_boring') ?>" style="max-width:220px">
        </div>

        <div class="field-row three">
          <div class="field col-2">
            <label for="straatnaam">Straatnaam + plaats <span class="req">*</span></label>
            <input type="text" id="straatnaam" name="straatnaam"
                   placeholder="Dorpsstraat, Schiedam" value="<?= old('straatnaam') ?>" required>
          </div>
          <div class="field">
            <label for="huisnummer">Huisnummer <span class="opt">(optioneel)</span></label>
            <input type="text" id="huisnummer" name="huisnummer"
                   placeholder="bijv. 12a" value="<?= old('huisnummer') ?>">
          </div>
        </div>

      </div>
    </section>

    <!-- ===== SECTIE 2: OPDRACHTGEVER & CONTACTPERSONEN ===== -->
    <section class="card">
      <div class="card-header">Opdrachtgever &amp; contactpersonen</div>
      <div class="card-body">

        <div class="field-row">
          <div class="field">
            <label for="opdrachtgever">Opdrachtgever <span class="req">*</span></label>
            <input type="text" id="opdrachtgever" name="opdrachtgever"
                   placeholder="Naam opdrachtgever" value="<?= old('opdrachtgever') ?>" required>
          </div>
          <div class="field">
            <label for="projectnummer">Projectnummer <span class="req">*</span></label>
            <input type="text" id="projectnummer" name="projectnummer"
                   placeholder="bijv. 2025-042" value="<?= old('projectnummer') ?>" required>
          </div>
        </div>

        <div class="radio-field">
          <span class="radio-label">Boorplan aanwezig</span>
          <div class="radio-group">
            <label class="radio-opt">
              <input type="radio" name="boorplan_aanwezig" value="ja" id="boorplan-ja"
                     <?= radio_if('boorplan_aanwezig','ja') ?>> Ja
            </label>
            <label class="radio-opt">
              <input type="radio" name="boorplan_aanwezig" value="nee" id="boorplan-nee"
                     <?= radio_if('boorplan_aanwezig','nee') ?>> Nee
            </label>
          </div>
        </div>

        <div id="boorplan-extra" class="field boorplan-extra <?= (($_POST['boorplan_aanwezig'] ?? 'nee') === 'ja') ? 'visible' : '' ?>">
          <label for="boorplan_bestand">Boorplan toevoegen</label>
          <div style="display:flex; align-items:center; gap:8px;">
            <input type="file" id="boorplan_bestand" name="boorplan_bestand"
                   accept=".pdf,.jpg,.jpeg,.png,.dwg,.dxf" style="flex:1">
            <button type="button" id="boorplan-verwijder" class="btn-remove"
                    title="Verwijder gekozen bestand" style="display:none">&times;</button>
          </div>
          <span id="boorplan-bestandsnaam" class="opt" style="display:none"></span>
        </div>

        <div class="radio-field">
          <span class="radio-label">Wordt de boring uitgezet?</span>
          <div class="radio-group">
            <label class="radio-opt">
              <input type="radio" name="boring_uitgezet" value="ja" <?= radio_if('boring_uitgezet','ja') ?>> Ja
            </label>
            <label class="radio-opt">
              <input type="radio" name="boring_uitgezet" value="nee" <?= radio_if('boring_uitgezet','nee') ?>> Nee
            </label>
          </div>
        </div>

        <div class="field-row">
          <div class="field">
            <label for="naam_uitvoerder">Naam uitvoerder <span class="req">*</span></label>
            <input type="text" id="naam_uitvoerder" name="naam_uitvoerder"
                   placeholder="Volledige naam" value="<?= old('naam_uitvoerder') ?>" required>
          </div>
          <div class="field">
            <label for="tel_uitvoerder">Telefoonnummer uitvoerder</label>
            <input type="tel" id="tel_uitvoerder" name="tel_uitvoerder"
                   placeholder="+31 6 00 000 000" value="<?= old('tel_uitvoerder') ?>">
          </div>
        </div>

        <div class="field-row">
          <div class="field">
            <label for="naam_voorman">Naam voorman</label>
            <input type="text" id="naam_voorman" name="naam_voorman"
                   placeholder="Volledige naam" value="<?= old('naam_voorman') ?>">
          </div>
          <div class="field">
            <label for="tel_voorman">Telefoonnummer voorman</label>
            <input type="tel" id="tel_voorman" name="tel_voorman"
                   placeholder="+31 6 00 000 000" value="<?= old('tel_voorman') ?>">
          </div>
        </div>

      </div>
    </section>

    <!-- ===== SECTIE 3: MATERIAAL (HERHAALBAAR) ===== -->
    <section class="card">
      <div class="card-header">Materiaal &amp; levering</div>
      <div class="card-body">

        <div class="field">
          <label>SDR-type</label>
          <div class="btn-group" id="sdr-group" role="group" aria-label="SDR-type">
            <button type="button" class="seg-btn active" onclick="setSDR('SDR11',this)">SDR 11</button>
            <button type="button" class="seg-btn" onclick="setSDR('SDR17',this)">SDR 17</button>
          </div>
          <input type="hidden" name="sdr_type" id="sdr_type_val" value="<?= old('sdr_type','SDR11') ?>">
        </div>

        <div id="pipe-list"></div>

        <button type="button" class="add-row-btn" onclick="addPipe()">
          <span class="plus-icon">+</span> Buis/bundel toevoegen
        </button>

      </div>
    </section>

    <!-- ===== SECTIE 4: WERKZAAMHEDEN ===== -->
    <section class="card">
      <div class="card-header">Werkzaamheden</div>
      <div class="card-body">
        <?php
        $werkzaamheden = [
            'levering_touw'      => 'Levering touw',
            'water_in_buis'      => 'Water in buis',
            'bentonietafvoer'    => 'Bentonietafvoer',
            'in_uittrede_graven' => 'In- en uittrede graven',
        ];
        foreach ($werkzaamheden as $name => $label): ?>
        <div class="radio-field">
          <span class="radio-label"><?= $label ?></span>
          <div class="radio-group">
            <label class="radio-opt">
              <input type="radio" name="<?= $name ?>" value="ja" <?= radio_if($name,'ja') ?>> Ja
            </label>
            <label class="radio-opt">
              <input type="radio" name="<?= $name ?>" value="nee" <?= radio_if($name,'nee') ?>> Nee
            </label>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- ===== SECTIE 5: KLIC & TOEWIJZING ===== -->
    <section class="card">
      <div class="card-header">KLIC &amp; toewijzing</div>
      <div class="card-body">

        <div class="notice">
          Wij zullen zelf een KLIC APP melding maken wanneer deze er niet aanwezig is.
          Bij een ongeldige KLIC zullen wij zelf een melding maken en de kosten hiervoor in rekening brengen.
        </div>

        <div class="radio-field">
          <span class="radio-label">KLIC APP Melding aanwezig</span>
          <div class="radio-group" id="klic-group">
            <label class="radio-opt">
              <input type="radio" name="klic_melding" value="ja" <?= radio_if('klic_melding','ja') ?>> Ja
            </label>
            <label class="radio-opt">
              <input type="radio" name="klic_melding" value="nee" <?= radio_if('klic_melding','nee') ?>> Nee
            </label>
            <label class="radio-opt">
              <input type="radio" name="klic_melding" value="volgt_nog" <?= radio_if('klic_melding','volgt_nog') ?>> Volgt nog
            </label>
          </div>
        </div>

        <div id="klic-extra" class="klic-extra <?= (($_POST['klic_melding'] ?? 'nee') === 'ja') ? 'visible' : '' ?>">
          <div class="field">
            <label for="klic_nummer">KLIC-nummer</label>
            <input type="text" id="klic_nummer" name="klic_nummer"
                   placeholder="bijv. 25G123456" value="<?= old('klic_nummer') ?>">
          </div>
          <div class="field">
            <label for="klic_datum">Datum uitgifte</label>
            <input type="date" id="klic_datum" name="klic_datum" value="<?= old('klic_datum') ?>">
          </div>
        </div>

        <div class="radio-field">
          <span class="radio-label">Toegewezen aan Lex</span>
          <div class="radio-group">
            <label class="radio-opt">
              <input type="radio" name="toegewezen_lex" value="ja" <?= radio_if('toegewezen_lex','ja') ?>> Ja
            </label>
            <label class="radio-opt">
              <input type="radio" name="toegewezen_lex" value="nee" <?= radio_if('toegewezen_lex','nee') ?>> Nee
            </label>
          </div>
        </div>

      </div>
    </section>

    <!-- ===== SECTIE 6: EXTRA OPMERKING ===== -->
    <section class="card">
      <div class="card-header">Extra opmerking</div>
      <div class="card-body">
        <div class="field">
          <label for="extra_opmerking">Opmerking <span class="opt">(optioneel)</span></label>
          <textarea id="extra_opmerking" name="extra_opmerking"
                    placeholder="Eventuele extra opmerkingen of bijzonderheden"><?= old('extra_opmerking') ?></textarea>
        </div>
      </div>
    </section>

    <div class="form-actions">
      <button type="submit" class="btn-primary">Opdracht opslaan</button>
    </div>

  </form>
</div>

<script>
(function () {
  'use strict';

  const BUIS_DIAMETERS = ['40mm','50mm','63mm','75mm','110mm','125mm','160mm','200mm','250mm','310mm','anders'];
  const KLEUREN        = ['zwart-blauwe streep','zwart-oranje streep','zwart-rode streep','zwart-gele streep','helemaal blauw','helemaal oranje','helemaal zwart','helemaal rood'];

  let itemTeller = 0;

  // ---- Helpers ----
  function el(tag, props) {
    const e = document.createElement(tag);
    if (props) {
      Object.entries(props).forEach(([k, v]) => {
        if (k === 'style' && typeof v === 'object') Object.assign(e.style, v);
        else e[k] = v;
      });
    }
    return e;
  }

  function maakSelectEl(name, opties) {
    const sel = el('select', { name });
    opties.forEach(opt => sel.appendChild(el('option', { value: opt, textContent: opt })));
    return sel;
  }

  function maakField(labelTekst, inhoud) {
    const div = el('div', { className: 'field' });
    div.appendChild(el('label', { textContent: labelTekst }));
    div.appendChild(inhoud);
    return div;
  }

  function maakRow(kinderen) {
    const row = el('div', { className: 'field-row' });
    kinderen.forEach(k => row.appendChild(k));
    return row;
  }

  // Radiogroep: geeft { wrap, radios } terug, onChange krijgt de gekozen waarde
  function maakRadioGroup(name, labelTekst, opties, defaultVal, onChange) {
    const wrap  = el('div', { className: 'radio-field' });
    wrap.appendChild(el('span', { className: 'radio-label', textContent: labelTekst }));
    const group = el('div', { className: 'radio-group' });
    const radios = [];
    opties.forEach(opt => {
      const lbl   = el('label', { className: 'radio-opt' });
      const radio = el('input', { type: 'radio', name, value: opt });
      if (opt === defaultVal) radio.checked = true;
      if (onChange) radio.addEventListener('change', () => onChange(opt));
      lbl.appendChild(radio);
      lbl.appendChild(document.createTextNode(' ' + opt.charAt(0).toUpperCase() + opt.slice(1)));
      group.appendChild(lbl);
      radios.push(radio);
    });
    wrap.appendChild(group);
    return { wrap, radios };
  }

  // ---- Diameter veld met "anders" vrij-invoer (buis) ----
  function maakDiameterVeld(prefix) {
    const wrap = el('div', { className: 'field' });
    wrap.appendChild(el('label', { textContent: 'Diameter' }));

    const sel = maakSelectEl(`${prefix}[diameter_buis]`, BUIS_DIAMETERS);
    const andersInput = el('input', {
      type: 'text',
      name: `${prefix}[diameter_anders]`,
      placeholder: 'Voer diameter in (bijv. 400mm)',
      className: 'anders-input',
    });
    sel.addEventListener('change', () => {
      andersInput.classList.toggle('visible', sel.value === 'anders');
    });

    wrap.appendChild(sel);
    wrap.appendChild(andersInput);
    return wrap;
  }

  // ---- Leveringsketen: levering -> rol/lengtes -> lasser -> rail eruit -> trekkop ----
  function maakLeveringsKeten(prefix) {
    const cont = el('div', { style: { display: 'flex', flexDirection: 'column', gap: '10px' } });

    function condToon(veld, zichtbaar) { veld.classList.toggle('visible', zichtbaar); }

    const trekkop = maakRadioGroup(`${prefix}[trekkop]`, 'Trekkop meelassen', ['ja', 'nee'], 'nee', null);
    trekkop.wrap.classList.add('cond');

    const railEruit = maakRadioGroup(`${prefix}[rail_eruit]`, 'Moet de rail eruit', ['ja', 'nee'], 'nee', val => {
      condToon(trekkop.wrap, val === 'ja');
    });
    railEruit.wrap.classList.add('cond');

    const lasser = maakRadioGroup(`${prefix}[lasser]`, 'Lasser', ['ja', 'nee'], 'nee', val => {
      condToon(railEruit.wrap, val === 'ja');
      if (val !== 'ja') condToon(trekkop.wrap, false);
    });
    lasser.wrap.classList.add('cond');

    const rolLengtes = maakRadioGroup(`${prefix}[rol_lengtes]`, 'Op rol of op lengtes', ['op rol', 'op lengtes'], 'op rol', val => {
      const lengtes = val === 'op lengtes';
      condToon(lasser.wrap, lengtes);
      if (!lengtes) { condToon(railEruit.wrap, false); condToon(trekkop.wrap, false); }
    });
    rolLengtes.wrap.classList.add('cond');

    const levering = maakRadioGroup(`${prefix}[levering]`, 'Levering buis door Lex Krabbe BV', ['ja', 'nee'], 'nee', val => {
      const ja = val === 'ja';
      condToon(rolLengtes.wrap, ja);
      if (!ja) {
        condToon(lasser.wrap, false);
        condToon(railEruit.wrap, false);
        condToon(trekkop.wrap, false);
      } else if (rolLengtes.radios.find(r => r.checked)?.value === 'op lengtes') {
        condToon(lasser.wrap, true);
      }
    });

    [levering, rolLengtes, lasser, railEruit, trekkop].forEach(g => cont.appendChild(g.wrap));
    return cont;
  }

  // ---- Alle buisvelden (behalve aantal meter) ----
  function maakBuisVelden(prefix) {
    const cont = el('div', { style: { display: 'flex', flexDirection: 'column', gap: '10px' } });
    cont.appendChild(maakDiameterVeld(prefix));
    cont.appendChild(maakField('Kleur', maakSelectEl(`${prefix}[kleur]`, KLEUREN)));
    cont.appendChild(maakRadioGroup(
      `${prefix}[mantel_medium]`, 'Mantelbuis of mediumvoerend',
      ['mantelbuis', 'mediumvoerend'], 'mantelbuis', null
    ).wrap);
    cont.appendChild(el('hr', { className: 'sub-divider' }));
    cont.appendChild(maakLeveringsKeten(prefix));
    return cont;
  }

  // ---- Bundel: meerdere buizen, elk met alle buisvelden behalve aantal meter ----
  function maakBundelVeld(prefix) {
    const wrap = el('div', { className: 'field' });
    wrap.appendChild(el('label', { textContent: 'Buizen in bundel' }));

    const lijst = el('div', { style: { display: 'flex', flexDirection: 'column', gap: '10px' } });
    let buisTeller = 0;

    function addBundelBuis() {
      buisTeller++;
      const bid     = buisTeller;
      const bPrefix = `${prefix}[bundel_buizen][${bid}]`;

      const sub = el('div', { className: 'sub-box' });

      const top = el('div', { className: 'pipe-item-top', style: { marginBottom: '0' } });
      top.appendChild(el('span', { className: 'pipe-item-label', textContent: 'Buis' }));
      const verwijder = el('button', { type: 'button', className: 'btn-remove', textContent: '×' });
      verwijder.setAttribute('aria-label', 'Verwijder buis uit bundel');
      verwijder.onclick = () => sub.remove();
      top.appendChild(verwijder);
      sub.appendChild(top);

      const aantal = el('input', { type: 'number', name: `${bPrefix}[aantal]`, min: '1', step: '1', value: '1' });
      aantal.style.maxWidth = '120px';
      sub.appendChild(maakField('Aantal', aantal));

      sub.appendChild(maakBuisVelden(bPrefix));
      lijst.appendChild(sub);
    }

    const addBtn = el('button', { type: 'button', className: 'add-row-btn', textContent: '+ Buis toevoegen aan bundel' });
    addBtn.style.marginTop = '8px';
    addBtn.onclick = addBundelBuis;

    addBundelBuis(); // start met 1 buis

    wrap.appendChild(lijst);
    wrap.appendChild(addBtn);
    return wrap;
  }

  // ---- Buis/bundel item toevoegen ----
  window.addPipe = function () {
    itemTeller++;
    const id     = itemTeller;
    const prefix = `items[${id}]`;

    const wrapper = el('div', { className: 'pipe-item', id: 'pipe-' + id });

    // Header
    const top     = el('div', { className: 'pipe-item-top' });
    const itemLbl = el('span', { className: 'pipe-item-label', textContent: `Buis/bundel #${id}` });
    const delBtn  = el('button', { type: 'button', className: 'btn-remove', textContent: '×' });
    delBtn.setAttribute('aria-label', 'Verwijder item');
    delBtn.onclick = () => wrapper.remove();
    top.appendChild(itemLbl); top.appendChild(delBtn);

    // Type toggle (solo/bundel) - wisselt tussen solo-velden en bundel-buizen
    const typeInput = el('input', { type: 'hidden', name: `${prefix}[type]`, value: 'buis' });
    const typeGroup = el('div', { className: 'type-group' });

    const soloVelden = maakBuisVelden(prefix);
    const bundelVeld = maakBundelVeld(prefix);
    bundelVeld.style.display = 'none';

    const TYPE_LABELS = { buis: 'Solo', bundel: 'Bundel' };
    ['buis', 'bundel'].forEach(type => {
      const btn = el('button', {
        type: 'button',
        className: 'type-btn' + (type === 'buis' ? ' active' : ''),
        textContent: TYPE_LABELS[type],
      });
      btn.onclick = () => {
        typeGroup.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        typeInput.value = type;
        soloVelden.style.display = type === 'buis'   ? '' : 'none';
        bundelVeld.style.display = type === 'bundel' ? '' : 'none';
      };
      typeGroup.appendChild(btn);
    });

    // ---- Velden ----
    const box = el('div', { className: 'sub-box' });

    const meterInput = el('input', {
      type: 'number', name: `${prefix}[meter]`,
      placeholder: 'bijv. 150', min: '0', step: '0.1',
    });
    meterInput.style.maxWidth = '220px';

    box.appendChild(maakField('Aantal meter', meterInput));
    box.appendChild(soloVelden);
    box.appendChild(bundelVeld);

    // Samenstellen
    wrapper.appendChild(top);
    wrapper.appendChild(typeGroup);
    wrapper.appendChild(typeInput);
    wrapper.appendChild(box);

    document.getElementById('pipe-list').appendChild(wrapper);
  };

  // ---- SDR toggle ----
  window.setSDR = function (val, btn) {
    document.querySelectorAll('#sdr-group .seg-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('sdr_type_val').value = val;
  };

  // ---- Boorplan radio -> bestand veld ----
  const boorplanExtra = document.getElementById('boorplan-extra');
  document.querySelectorAll('input[name="boorplan_aanwezig"]').forEach(r => {
    r.addEventListener('change', () => {
      boorplanExtra.classList.toggle('visible', r.value === 'ja' && r.checked);
    });
  });

  // ---- Boorplan bestand: naam tonen + kunnen aanpassen/verwijderen ----
  const boorplanInput   = document.getElementById('boorplan_bestand');
  const boorplanVerwBtn = document.getElementById('boorplan-verwijder');
  const boorplanNaam    = document.getElementById('boorplan-bestandsnaam');
  boorplanInput.addEventListener('change', () => {
    if (boorplanInput.files.length) {
      boorplanNaam.textContent = boorplanInput.files[0].name;
      boorplanNaam.style.display = '';
      boorplanVerwBtn.style.display = '';
    } else {
      boorplanNaam.style.display = 'none';
      boorplanVerwBtn.style.display = 'none';
    }
  });
  boorplanVerwBtn.addEventListener('click', () => {
    boorplanInput.value = '';
    boorplanNaam.style.display = 'none';
    boorplanVerwBtn.style.display = 'none';
  });

  // ---- KLIC radio -> extra velden ----
  const klicExtra = document.getElementById('klic-extra');
  document.querySelectorAll('input[name="klic_melding"]').forEach(r => {
    r.addEventListener('change', () => {
      klicExtra.classList.toggle('visible', r.value === 'ja' && r.checked);
    });
  });

  // ---- KLIC datum uitgifte: max 1 maand voor datum boring (of vandaag) ----
  const datumBoring = document.getElementById('datum_boring');
  const klicDatum   = document.getElementById('klic_datum');
  function updateKlicMin() {
    const ref = datumBoring.value ? new Date(datumBoring.value) : new Date();
    ref.setMonth(ref.getMonth() - 1);
    klicDatum.min = ref.toISOString().slice(0, 10);
    if (klicDatum.value && klicDatum.value < klicDatum.min) {
      klicDatum.setCustomValidity('Datum uitgifte mag niet meer dan 1 maand voor de datum van de boring liggen.');
    } else {
      klicDatum.setCustomValidity('');
    }
  }
  datumBoring.addEventListener('change', updateKlicMin);
  klicDatum.addEventListener('change', updateKlicMin);
  updateKlicMin();

  // Start met een item
  addPipe();
})();
</script>

</body>
</html>
