<?php
header('Content-Type: text/html; charset=utf-8');
if (isset($_POST['message'])) {
    $msg = escapeshellarg($_POST['message']);
    // On remonte d'un dossier pour trouver le script python s'il est à la racine
    $command = "python " . realpath("../chat_agent.py") . " " . $msg . " 2>&1";
    $output = shell_exec($command);
    echo $output;
}
?>