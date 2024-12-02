<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wunschgericht</title>
</head>
<body>
    <h1>Wunschgericht eintragen</h1>
    <form action="wunschgericht.php" method="POST">
        <label for="gericht-name">Name des Gerichts:</label>
        <input type="text" id="gericht-name" name="gericht_name" required><br>

        <label for="gericht-beschreibung">Beschreibung:</label>
        <textarea id="gericht-beschreibung" name="gericht_beschreibung" required></textarea><br>

        <label for="ersteller-name">Ihr Name:</label>
        <input type="text" id="ersteller-name" name="ersteller_name"><br>

        <label for="ersteller-email">Ihre E-Mail:</label>
        <input type="email" id="ersteller-email" name="ersteller_email" required><br>

        <button type="submit">Wunsch abschicken</button>
    </form>
</body>
</html>
