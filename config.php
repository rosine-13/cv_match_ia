<?php
// config.php

// 1. Démarrage de la session (obligatoire pour gérer les connexions)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Paramètres de connexion à la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cvmatch_ia_db'); // Ton nouveau nom de BDD

try {
    // Connexion via PDO avec support de l'UTF-8 pour les accents
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    
    // Activation des erreurs pour nous aider à débugger pendant le développement
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Mode de récupération par défaut : Tableaux associatifs
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si la connexion échoue, on arrête tout et on affiche l'erreur
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// --- FONCTIONS DE SÉCURITÉ ET UTILITAIRES ---

/**
 * Nettoie les données saisies par l'utilisateur (évite les failles XSS)
 */
function clean_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Vérifie si l'utilisateur est connecté au site
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si la personne connectée est un CANDIDAT
 */
function is_candidate() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'candidate';
}

/**
 * Vérifie si la personne connectée est un RECRUTEUR
 */
function is_recruiter() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'recruiter';
}

/**
 * Valide le fichier uploadé (Format et Taille)
 */
function validate_uploaded_file($file) {
    $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
    $file_info = pathinfo($file['name']);
    $file_extension = strtolower($file_info['extension']);
    
    // Vérification de l'extension
    if (!in_array($file_extension, $allowed_extensions)) {
        return [
            'success' => false, 
            'error' => "Format non supporté. Veuillez utiliser un PDF, JPG ou PNG."
        ];
    }
    
    // Vérification de la taille (Max 5 Mo ici)
    if ($file['size'] > 5000000) {
        return [
            'success' => false, 
            'error' => "Le fichier est trop lourd. La limite est de 5 Mo."
        ];
    }
    
    return ['success' => true];
}
?>