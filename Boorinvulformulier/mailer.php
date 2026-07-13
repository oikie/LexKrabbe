<?php
// mailer.php - versturen van mail met bijlage via Gmail SMTP (SSL, poort 465)
// Pure PHP, geen externe libraries nodig.

/**
 * Leest een SMTP-antwoord (kan meerdere regels zijn) en controleert de statuscode.
 */
function smtp_lees($fp, string $verwacht): string {
    $antwoord = '';
    while (($regel = fgets($fp, 515)) !== false) {
        $antwoord .= $regel;
        // laatste regel van een antwoord heeft een spatie na de code: "250 ..."
        if (strlen($regel) < 4 || $regel[3] !== '-') break;
    }
    if (strpos($antwoord, $verwacht) !== 0) {
        throw new RuntimeException("SMTP-fout (verwacht $verwacht): " . trim($antwoord));
    }
    return $antwoord;
}

function smtp_cmd($fp, string $cmd, string $verwacht): string {
    fwrite($fp, $cmd . "\r\n");
    return smtp_lees($fp, $verwacht);
}

/**
 * Verstuurt een mail met een of meer bijlagen.
 * $cfg komt uit mail_config.php: smtp_host, smtp_port, gebruiker, app_wachtwoord, afzender_naam
 * $bijlagen: pad-string (1 bijlage) of lijst van ['pad' => ..., 'naam' => ...]
 * Retourneert ['ok' => bool, 'fout' => string]
 */
function verstuur_opdracht_mail(array $cfg, string $naar, string $onderwerp, string $tekst, $bijlagen): array {
    try {
        $host = $cfg['smtp_host']     ?? 'smtp.gmail.com';
        $port = (int)($cfg['smtp_port'] ?? 465);
        $user = $cfg['gebruiker']      ?? '';
        $pass = str_replace(' ', '', $cfg['app_wachtwoord'] ?? ''); // spaties uit app-wachtwoord halen
        $van  = $cfg['afzender_naam']  ?? 'Boorformulier';

        if ($user === '' || $pass === '') {
            return ['ok' => false, 'fout' => 'Gebruiker of app-wachtwoord ontbreekt in mail_config.php'];
        }

        // Normaliseer naar lijst van ['pad' => ..., 'naam' => ...]
        if (is_string($bijlagen)) $bijlagen = [['pad' => $bijlagen, 'naam' => basename($bijlagen)]];
        foreach ($bijlagen as $b) {
            if (!is_readable($b['pad'])) {
                return ['ok' => false, 'fout' => 'Bijlage niet gevonden: ' . ($b['naam'] ?? basename($b['pad']))];
            }
        }

        $fp = stream_socket_client(
            "ssl://$host:$port", $errno, $errstr, 20,
            STREAM_CLIENT_CONNECT,
            stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]])
        );
        if (!$fp) return ['ok' => false, 'fout' => "Verbinding mislukt: $errstr ($errno)"];
        stream_set_timeout($fp, 20);

        smtp_lees($fp, '220');
        smtp_cmd($fp, 'EHLO localhost', '250');
        smtp_cmd($fp, 'AUTH LOGIN', '334');
        smtp_cmd($fp, base64_encode($user), '334');
        smtp_cmd($fp, base64_encode($pass), '235');
        smtp_cmd($fp, "MAIL FROM:<$user>", '250');
        smtp_cmd($fp, "RCPT TO:<$naar>", '250');
        smtp_cmd($fp, 'DATA', '354');

        // ---- MIME-bericht met bijlagen ----
        $grens = 'grens_' . bin2hex(random_bytes(12));

        $bericht  = "From: =?UTF-8?B?" . base64_encode($van) . "?= <$user>\r\n";
        $bericht .= "To: <$naar>\r\n";
        $bericht .= "Subject: =?UTF-8?B?" . base64_encode($onderwerp) . "?=\r\n";
        $bericht .= "MIME-Version: 1.0\r\n";
        $bericht .= "Content-Type: multipart/mixed; boundary=\"$grens\"\r\n";
        $bericht .= "\r\n";
        $bericht .= "--$grens\r\n";
        $bericht .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $bericht .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $bericht .= $tekst . "\r\n";

        foreach ($bijlagen as $b) {
            $naam = $b['naam'] ?? basename($b['pad']);
            $mime = function_exists('mime_content_type')
                ? (mime_content_type($b['pad']) ?: 'application/octet-stream')
                : 'application/octet-stream';
            $b64  = chunk_split(base64_encode(file_get_contents($b['pad'])));

            $bericht .= "--$grens\r\n";
            $bericht .= "Content-Type: $mime; name=\"$naam\"\r\n";
            $bericht .= "Content-Transfer-Encoding: base64\r\n";
            $bericht .= "Content-Disposition: attachment; filename=\"$naam\"\r\n\r\n";
            $bericht .= $b64 . "\r\n";
        }
        $bericht .= "--$grens--\r\n";

        // Regels die met "." beginnen escapen (SMTP dot-stuffing)
        $bericht = preg_replace('/^\./m', '..', $bericht);

        fwrite($fp, $bericht . "\r\n.\r\n");
        smtp_lees($fp, '250');
        smtp_cmd($fp, 'QUIT', '221');
        fclose($fp);

        return ['ok' => true, 'fout' => '', 'aantal_bijlagen' => count($bijlagen)];
    } catch (Throwable $e) {
        return ['ok' => false, 'fout' => $e->getMessage()];
    }
}
