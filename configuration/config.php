<?php
// config.php

// Paramèt koneksyon baz done
define('DB_HOST', 'localhost');
define('DB_NAME', 'anacaona');
define('DB_USER', 'root');
define('DB_PASS', '');

// URL baz aplikasyon an (pou redireksyon)
define('BASE_URL', 'http://localhost/ANACAONA/');

// Sesyon demare si li poko demare
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fonksyon debaz (si ou vle ajoute isit la)
function isLoggedIn() {
    return isset(_SESSION['user']);
}

function redirect($url) {
    header("Location: $url");
    exit;
}
