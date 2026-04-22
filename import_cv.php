<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "cvmatch_ia_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connexion échouée: " . $conn->connect_error);
}

// 1. Dossier où tu as mis tes 53 CV
$dir = "uploads/";
$files = scandir($dir);
$importCount = 0;

foreach ($files as $file) {
    if ($file !== '.' && $file !== '..' && is_file($dir . $file)) {
        
        // --- ÉTAPE CRUCIALE : VÉRIFICATION DES COLONNES ---
        // On vérifie si la colonne s'appelle 'file_path' ou 'filename'
        // J'utilise 'file_path' ici, change-le si ta colonne a un autre nom dans phpMyAdmin
        $colonne_fichier = "file_path"; 
        
        // On vérifie si le fichier est déjà dans la table cvs
        $check = $conn->query("SELECT id FROM cvs WHERE $colonne_fichier = '$file'");
        
        if ($check && $check->num_rows == 0) {
            // On insère le fichier. 
            // Note : Si ta table demande un 'user_id', j'en mets un par défaut (1)
            $sql = "INSERT INTO cvs ($colonne_fichier, user_id) VALUES ('$file', 1)";
            
            if ($conn->query($sql)) {
                $importCount++;
            }
        }
    }
}

echo "<h3>Importation dans la table 'cvs' réussie !</h3>";
echo "Nouveaux fichiers ajoutés : " . $importCount;
echo "<br><a href='search_ia.php'>Voir le résultat</a>";

$conn->close();
?>