<?php
// formulier.php - Boringsopdracht invoerformulier (alles in één bestand)
$success = false;
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['opdrachtgever']))   $errors[] = 'Selecteer een opdrachtgever.';
    if (empty($_POST['naam_uitvoerder'])) $errors[] = 'Naam uitvoerder is verplicht.';
    if (empty($_POST['projectnummer']))   $errors[] = 'Projectnummer is verplicht.';
    if (!empty($_POST['klic_melding']) && empty($_POST['klic_nummer']))
        $errors[] = 'Vul het KLIC-nummer in bij aanwezige KLIC-melding.';

    if (empty($errors)) {
        $data = [
            'datum_boring'         => $_POST['datum_boring']          ?? null,
            'straatnaam'           => trim($_POST['straatnaam']        ?? ''),
            'huisnummer'           => trim($_POST['huisnummer']        ?? ''),
            'opdrachtgever'        => $_POST['opdrachtgever'],
            'opdrachtgever_anders' => trim($_POST['opdrachtgever_anders'] ?? ''),
            'naam_uitvoerder'      => trim($_POST['naam_uitvoerder']),
            'tel_uitvoerder'       => trim($_POST['tel_uitvoerder']    ?? ''),
            'projectnummer'        => trim($_POST['projectnummer']     ?? ''),
            'boorplan_aanwezig'    => isset($_POST['boorplan_aanwezig'])  ? 1 : 0,
            'naam_voorman'         => trim($_POST['naam_voorman']      ?? ''),
            'tel_voorman'          => trim($_POST['tel_voorman']       ?? ''),
            'sdr_type'             => $_POST['sdr_type']               ?? 'SDR11',
            'levering_touw'        => isset($_POST['levering_touw'])    ? 1 : 0,
            'water_in_buis'        => isset($_POST['water_in_buis'])    ? 1 : 0,
            'bentonietafvoer'      => isset($_POST['bentonietafvoer'])  ? 1 : 0,
            'in_uittrede_graven'   => isset($_POST['in_uittrede_graven']) ? 1 : 0,
            'klic_melding'         => isset($_POST['klic_melding'])     ? 1 : 0,
            'klic_nummer'          => trim($_POST['klic_nummer']       ?? ''),
            'klic_datum'           => $_POST['klic_datum']              ?? null,
            'toegewezen_lex'       => isset($_POST['toegewezen_lex'])   ? 1 : 0,
            'items'                => [],
        ];

        if (!empty($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                $type  = $item['type'] ?? 'buis';
                $entry = ['type' => $type, 'levering_lex' => isset($item['levering_lex']) ? 1 : 0];
                if ($type === 'buis') {
                    $entry['meter']           = (float)($item['meter'] ?? 0);
                    $entry['diameter_buis']   = $item['diameter_buis']  ?? '';
                    $entry['diameter_anders'] = trim($item['diameter_anders'] ?? '');
                    $entry['kleur']           = $item['kleur'] ?? '';
                } else {
                    $entry['diameter_bundel'] = $item['diameter_bundel'] ?? '';
                }
                $data['items'][] = $entry;
            }
        }

        // TODO: bewaar $data in database of verwerk verder
        $success = true;
    }
}

function old(string $key, string $default = ''): string {
    return htmlspecialchars($_POST[$key] ?? $default, ENT_QUOTES);
}
function checked_if(string $key): string {
    return isset($_POST[$key]) ? 'checked' : '';
}
function selected_if(string $key, string $val): string {
    return (($_POST[$key] ?? '') === $val) ? 'selected' : '';
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

    input[type=text], input[type=date], input[type=tel], input[type=number], select {
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
    select {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%235f5e5a' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: right 10px center;
      padding-right: 34px; cursor: pointer;
    }
    input:focus, select:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(29,158,117,0.15);
    }
    input::placeholder { color: var(--text-tertiary); }

    /* "Anders" vrij-invoerveld onder een dropdown */
    .anders-input {
      display: none;
      margin-top: 6px;
    }
    .anders-input.visible { display: block; }

    .toggle-wrap { display: flex; align-items: center; gap: 10px; }
    .toggle-text { font-size: 14px; color: var(--text-primary); line-height: 1.4; }
    .toggle { position: relative; width: 42px; height: 24px; flex-shrink: 0; display: inline-block; }
    .toggle input { opacity: 0; width: 0; height: 0; position: absolute; }
    .slider {
      position: absolute; inset: 0;
      background: var(--border-md); border-radius: 999px;
      cursor: pointer; transition: background 0.2s;
    }
    .slider::before {
      content: ''; position: absolute;
      width: 18px; height: 18px; left: 3px; top: 3px;
      background: white; border-radius: 50%;
      transition: transform 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    .toggle input:checked + .slider { background: var(--accent); }
    .toggle input:checked + .slider::before { transform: translateX(18px); }

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

  <form method="POST" action="formulier.php" novalidate>

    <!-- ===== SECTIE 1: ALGEMEEN (alles optioneel) ===== -->
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
            <label for="straatnaam">Straatnaam + plaats <span class="opt">(optioneel)</span></label>
            <input type="text" id="straatnaam" name="straatnaam"
                   placeholder="Dorpsstraat, Schiedam" value="<?= old('straatnaam') ?>">
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
            <select id="opdrachtgever" name="opdrachtgever" required
                    onchange="toggleAnders(this,'opdrachtgever-anders')">
              <option value="">— Selecteer —</option>
              <?php foreach (['Hanab','Hak','Siers','Vitens','Anders'] as $og): ?>
                <option value="<?= $og ?>" <?= selected_if('opdrachtgever', $og) ?>><?= $og ?></option>
              <?php endforeach; ?>
            </select>
            <input type="text" id="opdrachtgever-anders" name="opdrachtgever_anders"
                   placeholder="Naam opdrachtgever"
                   value="<?= old('opdrachtgever_anders') ?>"
                   class="anders-input <?= (old('opdrachtgever') === 'Anders') ? 'visible' : '' ?>">
          </div>
          <div class="field">
            <label for="projectnummer">Projectnummer <span class="req">*</span></label>
            <input type="text" id="projectnummer" name="projectnummer"
                   placeholder="bijv. 2025-042" value="<?= old('projectnummer') ?>" required>
          </div>
        </div>

        <div class="field-row">
          <div class="field">
            <label>Boorplan aanwezig</label>
            <div class="toggle-wrap" style="height:38px">
              <label class="toggle">
                <input type="checkbox" name="boorplan_aanwezig" value="1" <?= checked_if('boorplan_aanwezig') ?>>
                <span class="slider"></span>
              </label>
            </div>
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
        $toggles = [
            'levering_touw'      => 'Levering touw',
            'water_in_buis'      => 'Water in buis',
            'bentonietafvoer'    => 'Bentonietafvoer',
            'in_uittrede_graven' => 'In- en uittrede graven',
        ];
        foreach ($toggles as $name => $label): ?>
        <div class="toggle-wrap">
          <label class="toggle">
            <input type="checkbox" name="<?= $name ?>" value="1" <?= checked_if($name) ?>>
            <span class="slider"></span>
          </label>
          <span class="toggle-text"><?= $label ?></span>
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

        <div class="toggle-wrap">
          <label class="toggle">
            <input type="checkbox" name="klic_melding" value="1" id="klic-toggle"
                   <?= checked_if('klic_melding') ?>>
            <span class="slider"></span>
          </label>
          <span class="toggle-text">KLIC APP Melding aanwezig</span>
        </div>

        <div id="klic-extra" class="klic-extra <?= isset($_POST['klic_melding']) ? 'visible' : '' ?>">
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

        <div class="toggle-wrap">
          <label class="toggle">
            <input type="checkbox" name="toegewezen_lex" value="1" <?= checked_if('toegewezen_lex') ?>>
            <span class="slider"></span>
          </label>
          <span class="toggle-text">Toegewezen aan Lex</span>
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
  const BUIS_KLEUREN   = ['zwart','zwart-blauw','zwart-rood'];
  const BUNDEL_OPTIES  = ['2x 63mm','2x 110mm','3x 110mm','4x 110mm','6x 110mm','3x 160mm'];

  let itemTeller = 0;

  // ---- Generieke "anders" toggle (ook voor opdrachtgever) ----
  window.toggleAnders = function (selectEl, targetId) {
    const target = document.getElementById(targetId);
    if (!target) return;
    if (selectEl.value === 'Anders' || selectEl.value === 'anders') {
      target.classList.add('visible');
    } else {
      target.classList.remove('visible');
    }
  };

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

  function maakToggle(name, labelTekst) {
    const wrap   = el('div', { className: 'toggle-wrap' });
    const lbl    = el('label', { className: 'toggle' });
    const cb     = el('input', { type: 'checkbox', name, value: '1' });
    const slider = el('span', { className: 'slider' });
    const tekst  = el('span', { className: 'toggle-text', textContent: labelTekst });
    lbl.appendChild(cb); lbl.appendChild(slider);
    wrap.appendChild(lbl); wrap.appendChild(tekst);
    return { wrap, cb };
  }

  function maakSelectEl(name, opties) {
    const sel = el('select', { name });
    opties.forEach(opt => {
      const o = el('option', { value: opt, textContent: opt });
      sel.appendChild(o);
    });
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

  // ---- Diameter veld met "anders" vrij-invoer ----
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

  // ---- Levering Lex toggle (gedeeld per item, gesynchroniseerd) ----
  function maakLeveringLexToggle(prefix) {
    const { wrap, cb } = maakToggle(`${prefix}[levering_lex]`, 'Levering buis door Lex Krabbe BV');
    return { wrap, cb };
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

    // Type toggle
    const typeInput = el('input', { type: 'hidden', name: `${prefix}[type]`, value: 'buis' });
    const typeGroup = el('div', { className: 'type-group' });

    ['buis', 'bundel'].forEach(type => {
      const btn = el('button', {
        type: 'button',
        className: 'type-btn' + (type === 'buis' ? ' active' : ''),
        textContent: type.charAt(0).toUpperCase() + type.slice(1),
      });
      btn.onclick = () => {
        typeGroup.querySelectorAll('.type-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        typeInput.value = type;
        buisBox.style.display   = type === 'buis'   ? '' : 'none';
        bundelBox.style.display = type === 'bundel' ? '' : 'none';
      };
      typeGroup.appendChild(btn);
    });

    // ---- Buis sub-box ----
    const buisBox = el('div', { className: 'sub-box' });

    const meterInput = el('input', {
      type: 'number', name: `${prefix}[meter]`,
      placeholder: 'bijv. 150', min: '0', step: '0.1',
    });

    buisBox.appendChild(maakRow([maakField('Aantal meter', meterInput), maakDiameterVeld(prefix)]));
    buisBox.appendChild(maakField('Kleur', maakSelectEl(`${prefix}[kleur]`, BUIS_KLEUREN)));

    // Levering Lex in buis-box
    buisBox.appendChild(el('hr', { className: 'sub-divider' }));
    const lexBuis = maakLeveringLexToggle(prefix);
    buisBox.appendChild(lexBuis.wrap);

    // ---- Bundel sub-box ----
    const bundelBox = el('div', { className: 'sub-box', style: { display: 'none' } });
    bundelBox.appendChild(maakField('Diameter configuratie', maakSelectEl(`${prefix}[diameter_bundel]`, BUNDEL_OPTIES)));

    // Levering Lex ook in bundel-box (zelfde name, gesynchroniseerd)
    bundelBox.appendChild(el('hr', { className: 'sub-divider' }));
    const lexBundel = maakLeveringLexToggle(prefix);
    bundelBox.appendChild(lexBundel.wrap);

    // Sync: beide checkboxes wijzen naar dezelfde waarde
    [lexBuis.cb, lexBundel.cb].forEach((cb, i, arr) => {
      cb.addEventListener('change', () => { arr[1 - i].checked = cb.checked; });
    });

    // Samenstellen
    wrapper.appendChild(top);
    wrapper.appendChild(typeGroup);
    wrapper.appendChild(typeInput);
    wrapper.appendChild(buisBox);
    wrapper.appendChild(bundelBox);

    document.getElementById('pipe-list').appendChild(wrapper);
  };

  // ---- SDR toggle ----
  window.setSDR = function (val, btn) {
    document.querySelectorAll('#sdr-group .seg-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('sdr_type_val').value = val;
  };

  // ---- KLIC toggle ----
  const klicToggle = document.getElementById('klic-toggle');
  const klicExtra  = document.getElementById('klic-extra');
  if (klicToggle && klicExtra) {
    klicToggle.addEventListener('change', () => {
      klicExtra.classList.toggle('visible', klicToggle.checked);
    });
  }

  // Start met één item
  addPipe();
})();
</script>

</body>
</html>