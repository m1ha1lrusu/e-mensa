<?php

function getGerichte($link) {
    $sql = "SELECT name, preisintern, preisextern FROM emensawerbeseite.gericht ORDER BY name ASC LIMIT 5";

    // Abfrage ausführen
    $result = mysqli_query($link, $sql);

    if (!$result) {
        die("Fehler bei der Abfrage: " . mysqli_error($link));
    }

    // Ergebnisse in ein Array speichern
    $gerichte = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $gerichte[] = $row;
    }

    mysqli_free_result($result); // Ressourcen freigeben
    return $gerichte;
}

function getGerichteMitAllergenen($link): array
{
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

    return $_SESSION['random_gerichte'];
}


function getGerichtByName($link, $name)
{
    $stmt = mysqli_prepare($link, "SELECT * FROM emensawerbeseite.gericht WHERE name = ?");
    mysqli_stmt_bind_param($stmt, "s", $name);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $gericht = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);
    return $gericht;
}

function speichereNewsletterAnmeldung($link, $name, $email): void
{
    $stmt = $link->prepare("INSERT INTO emensawerbeseite.newsletter_anmeldungen (name, email, datum) VALUES (?, ?, ?)");
    if (!$stmt) {
        die("Fehler beim Vorbereiten des Statements: " . $link->error);
    }

    $datum = date('Y-m-d H:i:s');
    $stmt->bind_param("sss", $name, $email, $datum);

    if (!$stmt->execute()) {
        die("Fehler beim Ausführen des Statements: " . $stmt->error);
    }

    $stmt->close();
}


