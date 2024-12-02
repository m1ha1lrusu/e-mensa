<?php
/**
 * Praktikum DBWT. Autoren:
 * Mihail, Rusu, 3669639
 * Soufiane, Zouak, 3638505
 */

// Fehlerberichterstattung aktivieren
ini_set('display_errors', 1); // Fehler im Browser anzeigen
//ini_set('display_startup_errors', 1); // Startfehler anzeigen
//error_reporting(E_ALL); // Alle Fehler und Warnungen anzeigen

session_start();

require 'db.php';
require 'queries.php';
require 'gerichte.php';

global $link, $gerichteMarquee;

global $gerichte, $visitCount, $newsletterCount, $mealCount, $message;

$currentDate = date('Y-m-d');
$userIP = $_SERVER['REMOTE_ADDR'];

$currentDate = date('Y-m-d');
$userIP = $_SERVER['REMOTE_ADDR'];

// 1. IP-Adresse prüfen und einfügen
if (checkUserIP($link, $userIP, $currentDate) === 0) {
    insertUserIP($link, $userIP, $currentDate);
}

// 2. Besucher- und Newsletter-Zahlen abrufen
$visitCount = getVisitCount($link);
$newsletterCount = getNewsletterCount($link);

/* Sortierung */
$sort = $_GET['sort'] ?? 'name_asc';

// 3. Gerichte laden und sortieren
$gerichte = getGerichte($link, $sort);
$mealCount = count($gerichte);

// 4. Newsletter-Verarbeitung
$message = ""; // Variable für Fehler- oder Erfolgsmeldungen

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $consent = isset($_POST['datenschutz']);

    $errors = [];
    if (empty($name)) $errors[] = 'Der Name darf nicht leer sein.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Die E-Mail-Adresse ist nicht korrekt.';
    if (!$consent) $errors[] = 'Sie müssen den Datenschutzbestimmungen zustimmen.';

    // E-Mail prüfen, falls keine Fehler
    if (empty($errors)) {
        // Prüfen, ob die E-Mail bereits existiert
        $result = executeQuery(
            $link,
            "SELECT COUNT(*) AS count FROM emensawerbeseite.newsletter_anmeldungen WHERE email = ?",
            ['s', $email]
        );

        $emailExists = $result[0]['count'] ?? 0;

        if ($emailExists > 0) {
            $errors[] = 'Diese E-Mail-Adresse ist bereits registriert.';
        }
    }

    // Nur wenn keine Fehler vorliegen, versuchen wir die Anmeldung zu speichern
    if (empty($errors)) {
        $datum = date('Y-m-d H:i:s');
        $insertSuccess = executeQuery(
            $link,
            "INSERT INTO emensawerbeseite.newsletter_anmeldungen (name, email, datum) VALUES (?, ?, ?)",
            ['sss', $name, $email, $datum]
        );

        if ($insertSuccess !== false) {
            $message = "<div class='success'>Danke für Ihre Anmeldung!</div>";
        } else {
            $message = "<div class='error'>Fehler beim Speichern der Anmeldung.</div>";
        }
    } else {
        // Fehler ausgeben
        $message = "<div class='error'><ul><li>" . implode('</li><li>', $errors) . "</li></ul></div>";
    }
}

// Session-Reset für neue Gerichte
if (isset($_GET['reset'])) {
    unset($_SESSION['random_gerichte']);
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit();
}
?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Mensa</title>

    <link rel="stylesheet" href="/werbeseite/css/styles.css">
    <link rel="stylesheet" href="/werbeseite/css/colors.css">
    <link rel="stylesheet" href="/werbeseite/css/header.css">
    <link rel="stylesheet" href="/werbeseite/css/marquee.css">
    <link rel="stylesheet" href="/werbeseite/css/table.css">
    <link rel="stylesheet" href="/werbeseite/css/stats.css">
    <link rel="stylesheet" href="/werbeseite/css/newsletter.css">
    <link rel="stylesheet" href="/werbeseite/css/footer.css">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inconsolata:wght@200..900&family=Karla:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">
</head>

<body>
<nav class="navbar">
    <div class="navbar-container">
        <!-- Logo -->
        <div class="navbar-logo">
            <a href="index.php">
                <img src="/werbeseite/img/logo-e-mensa.jpeg" alt="E-Mensa-Logo" class="logo">
            </a>
        </div>

        <!-- Navigation Links -->
        <ul class="navbar-links">
            <li><a href="#speisen">Speisen</a></li>
            <li><a href="#stats">Statistik</a></li>
        </ul>

        <!-- Kontakt Button -->
        <div class="navbar-contact">
            <a href="#newsletter" class="btn">Kontakt | Newsletter</a>
        </div>
    </div>
</nav>

<header class="hero">
    <img src="/werbeseite/img/koch.gif" alt="Animation" class="gif">
    <h1>Tagesmenü</h1>
    <h3>Was Sie heute erwartet</h3>
</header>

<main>
    <div class="marquee-container marquee-borders">
        <div class="marquee-content">
            <!-- Gerichte dynamisch -->
            <?php foreach ($gerichteMarquee as $gericht): ?>
                <div class="marquee-item">
                    <img src="<?= htmlspecialchars(strtolower($gericht['image'])) ?>" alt="<?= htmlspecialchars($gericht['name']) ?>" class="menu-image">
                    <h4><?= htmlspecialchars($gericht['name']) ?></h4>
                    <p style="font-size: 30px">
                        <?= htmlspecialchars($gericht['preisintern']) ?> €
                        <span style="font-size: 18px">/intern</span>
                    </p>
                    <p style="font-size: 30px">
                        <?= htmlspecialchars($gericht['preisextern']) ?> €
                        <span style="font-size: 18px">/extern</span>
                    </p>
                </div>
            <?php endforeach; ?>

            <!-- Duplizieren für Endlosschleife -->
            <?php foreach ($gerichteMarquee as $gericht): ?>
                <div class="marquee-item">
                    <img src="<?= htmlspecialchars(strtolower($gericht['image'])) ?>" alt="<?= htmlspecialchars($gericht['name']) ?>" class="menu-image">
                    <h4><?= htmlspecialchars($gericht['name']) ?></h4>
                    <p style="font-size: 30px">
                        <?= htmlspecialchars($gericht['preisintern']) ?> €
                        <span style="font-size: 18px">/intern</span>
                    </p>
                    <p style="font-size: 30px">
                        <?= htmlspecialchars($gericht['preisextern']) ?> €
                        <span style="font-size: 18px">/extern</span>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tabelle mit allen Gerichten -->
    <div class="table-container" id="speisen">
        <h3>Datenbank</h3>
        <div class="sorting-container">
            <div class="sorting-options">
                <form method="GET" action="">
                    <label for="sort-dropdown">Sortieren nach:</label>
                    <select id="sort-dropdown" name="sort" onchange="this.form.submit()">
                        <option value="name_asc" <?= isset($_GET['sort']) && $_GET['sort'] == 'name_asc' ? 'selected' : '' ?>>
                            Name (aufsteigend)
                        </option>
                        <option value="name_desc" <?= isset($_GET['sort']) && $_GET['sort'] == 'name_desc' ? 'selected' : '' ?>>
                            Name (absteigend)
                        </option>
                        <option value="preisintern_asc" <?= isset($_GET['sort']) && $_GET['sort'] == 'preisintern_asc' ? 'selected' : '' ?>>
                            Preis intern (aufsteigend)
                        </option>
                        <option value="preisintern_desc" <?= isset($_GET['sort']) && $_GET['sort'] == 'preisintern_desc' ? 'selected' : '' ?>>
                            Preis intern (absteigend)
                        </option>
                    </select>
                </form>
            </div>

            <div class="reset-button">
                <a href="?reset=1" class="btn">Neue Gerichte generieren</a>
            </div>

        </div>

        <table class="menu-table">
            <thead>
            <tr>
                <th>Gericht</th>
                <th>Allergene</th>
                <th>Preis intern</th>
                <th>Preis extern</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($gerichte as $gericht): ?>
                <tr>
                    <td><?= htmlspecialchars($gericht['name']) ?></td>
                    <td><?= htmlspecialchars($gericht['allergene']) ?: '-' ?></td>
                    <td><?= number_format($gericht['preisintern'], 2, ',') ?> €</td>
                    <td><?= number_format($gericht['preisextern'], 2, ',') ?> €</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Statistik -->
    <section id="stats" class="stats">
        <div class="stat">
            <h4 class="item"><?php echo $visitCount; ?></h4>
            <p>Besuche</p>
        </div>
        <div class="stat">
            <h4 class="item"><?php echo $newsletterCount; ?></h4>
            <p>Anmeldungen</p>
        </div>
        <div class="stat">
            <h4 class="item"><?php echo $mealCount; ?></h4>
            <p>Speisen</p>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="newsletter" id="newsletter">
        <h2>Abonnieren Sie unseren Newsletter</h2>
        <form method="post">
            <p class="input-group">
                <input class="input" id="name" name="name" type="text" required>
                <label class="label" for="name">Vorname</label>
            </p>
            <p class="input-group">
                <input class="input" id="email" name="email" type="email" required>
                <label class="label" for="email">E-Mail</label>
            </p>
            <p class="input-group">
                <input class="input" list="sprachen" id="sprache" name="sprache" required>
                <label class="label" for="sprache">Sprache</label>
                <datalist id="sprachen">
                    <option value="Deutsch">Deutsch</option>
                    <option value="Englisch">Englisch</option>
                </datalist>
            </p>
            <br>
            <p>
                <input type="checkbox" id="datenschutz" name="datenschutz" required>
                <label for="datenschutz">Den Datenschutzbestimmungen stimme ich zu</label>
            </p>

            <button type="submit" class="newsletter-btn">Zum Newsletter anmelden</button>
            <?= $message ?? '' ?>
        </form>
    </section>
</main>

<!-- Footer -->
<footer class="footer">
    <div class="footer-left">
        <p>© 2024 E-Mensa</p>
    </div>
    <div class="footer-center">
        <p class="footer-authors">
            <span>Mihail Rusu & Soufiane Zouak</span>
        </p>
    </div>
    <div class="footer-right">
        <ul class="footer-links">
            <a href="#">Datenschutz</a>
            <a href="#">AGB</a>
        </ul>
    </div>
</footer>

</body>
</html>
