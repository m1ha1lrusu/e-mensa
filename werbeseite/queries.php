<?php
require 'db.php';

function executeQuery($link, $query, $params = [])
{
    $stmt = mysqli_prepare($link, $query);
    if (!$stmt) {
        return false;
    }

    if (!empty($params)) {
        $types = array_shift($params); // Erster Parameter ist der Typ-String
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    if (!mysqli_stmt_execute($stmt)) {
        return false;
    }

    $result = mysqli_stmt_get_result($stmt);
    if ($result === false) {
        return false; // Kein Ergebnis, z. B. bei INSERT
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }

    return $data; // Array mit den Ergebnissen
}

function checkUserIP($link, $ip, $date): bool
{
    $sql = "SELECT COUNT(*) AS count FROM emensawerbeseite.besucher WHERE ip_address = ? AND visit_date = ?";
    $result = executeQuery($link, $sql, ['ss', $ip, $date]);

    // Wenn kein Ergebnis zurückkommt, Standardwert 0
    return isset($result[0]['count']) && $result[0]['count'] > 0;
}


// Fügt die IP-Adresse für das heutige Datum hinzu
function insertUserIP($link, $ip, $date): bool
{
    $sql = "INSERT INTO emensawerbeseite.besucher (ip_address, visit_date) VALUES (?, ?)";
    return executeQuery($link, $sql, ['ss', $ip, $date]);
}

// Holt die Anzahl der Besucher
function getVisitCount($link): int
{
    $sql = "SELECT COUNT(*) AS count FROM emensawerbeseite.besucher";
    $result = executeQuery($link, $sql);
    return $result[0]['count'];
}

// Holt die Anzahl der Newsletter-Anmeldungen
function getNewsletterCount($link): int
{
    $sql = "SELECT COUNT(*) AS count FROM emensawerbeseite.newsletter_anmeldungen";
    $result = executeQuery($link, $sql);
    return $result[0]['count'];
}

// Fügt eine Newsletter-Anmeldung hinzu
function insertNewsletterSignup($link, $name, $email, $datum): bool
{
    $sql = "INSERT INTO emensawerbeseite.newsletter_anmeldungen (name, email, datum) VALUES (?, ?, ?)";
    return executeQuery($link, $sql, ['sss', $name, $email, $datum]);
}

function getGerichte($link, $sort): array
{
    // Quelle: https://www.php-kurs.com/session-anwenden.htm
    if (!isset($_SESSION['random_gerichte'])) {
        $sql = "
            SELECT
                g.name, 
                g.preisintern, 
                g.preisextern, 
                GROUP_CONCAT(DISTINCT a.code ORDER BY a.code ASC SEPARATOR ', ') AS allergene
            FROM emensawerbeseite.gericht g
            LEFT JOIN emensawerbeseite.gericht_hat_allergen gha ON g.id = gha.gericht_id
            LEFT JOIN emensawerbeseite.allergen a ON gha.code = a.code
            GROUP BY g.id
            ORDER BY RAND()
            LIMIT 5;
        ";

        $result = mysqli_query($link, $sql);
        $_SESSION['random_gerichte'] = mysqli_fetch_all($result, MYSQLI_ASSOC); // Speichern in der Session
    }

    // Sortierung anwenden, wenn Sortierkriterien vorliegen
    if (isset($_GET['sort'])) {
        $orderBy = 'name';
        $orderDir = 'ASC';

        switch ($sort) {
            case 'name_desc':
                $orderBy = 'name';
                $orderDir = 'DESC';
                break;
            case 'preisintern_asc':
                $orderBy = 'preisintern';
                $orderDir = 'ASC';
                break;
            case 'preisintern_desc':
                $orderBy = 'preisintern';
                $orderDir = 'DESC';
                break;
        }

        // Session-Daten mit Sortierung aktualisieren
        usort($_SESSION['random_gerichte'], function ($a, $b) use ($orderBy, $orderDir) {
            if ($orderDir === 'ASC') {
                return $a[$orderBy] <=> $b[$orderBy];
            } else {
                return $b[$orderBy] <=> $a[$orderBy];
            }
        });
    }

    return $_SESSION['random_gerichte'];
}
