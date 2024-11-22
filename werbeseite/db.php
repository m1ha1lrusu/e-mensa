<?php

$host = "localhost";
$username = "root";
$password = "user";
$database = "emensawerbeseite";

$link = mysqli_connect(
    "localhost",
    "root",
    "user",
    "emensawerbeseite"
);

if (!$link) {
    die("Verbindung fehlgeschlagen: " . mysqli_connect_error());
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

