<?php
/**
 * Praktikum DBWT. Autoren:
 * Mihail, Rusu, 3669639
 * Soufiane, Zouak, 3638505
 */

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

session_start();

require 'db.php';
require 'queries.php';
require 'gerichte.php';

global $link, $gerichteMarquee;

global $gerichte;
global $visitCount;
global $newsletterCount;
global $mealCount;

$gerichte = getGerichteMitAllergenen($link);

$tagesmenue = [];
foreach ($tagesmenue as $gericht) {
    echo htmlspecialchars($gericht['image']) . '<br>';
}

// Verarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $consent = isset($_POST['datenschutz']);

    $errors = [];

    // Validierung
    if (empty($name)) {
        $errors[] = 'Der Name darf nicht leer sein.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Die E-Mail-Adresse ist nicht korrekt formatiert.';
    }
    if (preg_match('/@wegwerfmail\.(de|com)$/i', $email) || preg_match('/@trashmail\./i', $email)) {
        $errors[] = 'Wegwerf-E-Mail-Adressen sind nicht erlaubt.';
    }
    if (!$consent) {
        $errors[] = 'Der Datenschutzbestimmung muss zugestimmt werden.';
    }

    // IP-Zählung
    $ipFile = 'ip_log.txt';
    $userIP = $_SERVER['REMOTE_ADDR'];
    $currentDate = date('Y-m-d');
    $fileContents = file($ipFile, FILE_IGNORE_NEW_LINES);
    $ipFound = false;

    foreach ($fileContents as $entry) {
        list($ip, $date) = explode('|', $entry);
        if ($ip === $userIP && $date === $currentDate) {
            $ipFound = true;
            break;
        }
    }

    if (!$ipFound) {
        // IP und Datum speichern
        $newEntry = $userIP . '|' . $currentDate . PHP_EOL;

        $fileHandle = fopen($ipFile, 'a');
        if ($fileHandle) {
            fwrite($fileHandle, $newEntry); // Daten anhängen
        }
        fclose($fileHandle);
    }
}

if (empty($errors)) {
    $dataFile = 'newsletter_anmeldedaten.txt';
    $entry = "Name: $name, E-Mail: $email, Datum: " . date('Y-m-d H:i:s') . PHP_EOL;

    $fileHandle = fopen($dataFile, 'a');
    if ($fileHandle) {
        fwrite($fileHandle, $entry);
        fclose($fileHandle);
    }
} else {
    echo "<p>Fehler:</p><ul><li>" . implode('</li><li>', $errors) . "</li></ul>";
}

// Statistiken berechnen
$visitCount = count(file('ip_log.txt', FILE_IGNORE_NEW_LINES));
$newsletterCount = count(file('newsletter_anmeldedaten.txt', FILE_IGNORE_NEW_LINES));
$mealCount = count($gerichte);

/* Sortierung */
$sort = $_GET['sort'] ?? 'name_asc';

usort($gerichte, function ($a, $b) use ($sort) {
    switch ($sort) {
        case 'name_asc':
            return strcmp($a['name'], $b['name']);
        case 'name_desc':
            return strcmp($b['name'], $a['name']);
        case 'preisintern_asc':
            return $a['preisintern'] <=> $b['preisintern'];
        case 'preisintern_desc':
            return $b['preisintern'] <=> $a['preisintern'];
        default:
            return strcmp($a['name'], $b['name']); // Standard: Name aufsteigend
    }
});

if (isset($_GET['reset'])) {
    unset($_SESSION['random_gerichte']); // Lösche die gespeicherten Gerichte
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?')); // Seite ohne GET-Parameter neu laden
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
                    <p>Preis intern: <?= htmlspecialchars($gericht['preisintern']) ?> €</p>
                    <p>Preis extern: <?= htmlspecialchars($gericht['preisextern']) ?> €</p>
                </div>
            <?php endforeach; ?>

            <!-- Duplizieren für Endlosschleife -->
            <?php foreach ($gerichteMarquee as $gericht): ?>
                <div class="marquee-item">
                    <img src="<?= htmlspecialchars(strtolower($gericht['image'])) ?>" alt="<?= htmlspecialchars($gericht['name']) ?>" class="menu-image">
                    <h4><?= htmlspecialchars($gericht['name']) ?></h4>
                    <p>Preis intern: <?= htmlspecialchars($gericht['preisintern']) ?> €</p>
                    <p>Preis extern: <?= htmlspecialchars($gericht['preisextern']) ?> €</p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tabelle mit allen Gerichten -->
    <div class="table-container" id="speisen">

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
            <p>
                <label for="name"></label>
                <input id="name" name="name" type="text" placeholder="Vorname" required>
            </p>
            <p>
                <label for="email"></label>
                <input id="email" name="email" type="email" placeholder="E-Mail" required>
            </p>
            <p>
                <label for="sprache"></label>
                <input list="sprachen" id="sprache" name="sprache" placeholder="Sprache auswählen" required>
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
