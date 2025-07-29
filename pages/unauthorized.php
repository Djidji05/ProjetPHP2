<?php
// unauthorized.php
http_response_code(403); // Kòd HTTP 403: Aksè entèdi
echo "<h1>403 - Accès non autorisé</h1>";
echo "<p>Vous n'avez pas la permission d'accéder à cette page.</p>";
?>
