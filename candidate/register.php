<?php 
require_once '../config.php'; // On remonte d'un dossier pour trouver config.php

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Récupération et nettoyage des données
    $username  = clean_input($_POST['username']);
    $email     = clean_input($_POST['email']);
    $full_name = clean_input($_POST['full_name']);
    $password  = $_POST['password'];
    $city      = clean_input($_POST['city']);

    // 2. Vérifications de base
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format d'email invalide.";
    } else {
        try {
            // 3. Vérifier si l'email ou le pseudo existe déjà
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $check->execute([$email, $username]);
            
            if ($check->fetch()) {
                $error = "Ce pseudo ou cet email est déjà utilisé.";
            } else {
                // 4. DEBUT DE LA TRANSACTION (On remplit deux tables)
                $pdo->beginTransaction();

                // Insertion dans 'users'
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $ins_user = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'candidate')");
                $ins_user->execute([$username, $email, $hashed_password]);
                
                $user_id = $pdo->lastInsertId(); // On récupère l'ID qui vient d'être créé

                // Insertion dans 'candidate_profiles'
                $ins_profile = $pdo->prepare("INSERT INTO candidate_profiles (user_id, full_name, city) VALUES (?, ?, ?)");
                $ins_profile->execute([$user_id, $full_name, $city]);

                $pdo->commit(); // On valide les deux insertions
                $success = "Inscription réussie ! <a href='login.php'>Connectez-vous ici</a>";
            }
        } catch (Exception $e) {
            $pdo->rollBack(); // En cas d'erreur, on annule tout pour ne pas avoir de données orphelines
            $error = "Erreur lors de l'inscription : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription Candidat - CVMatch IA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4>Créer un compte Candidat</h4>
                </div>
                <div class="card-body">
                    <?php if($error): ?> <div class="alert alert-danger"><?= $error ?></div> <?php endif; ?>
                    <?php if($success): ?> <div class="alert alert-success"><?= $success ?></div> <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label>Nom d'utilisateur (Pseudo)</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Nom Complet</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Ville</label>
                            <input type="text" name="city" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Mot de passe</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">S'inscrire</button>
                    </form>
                    <p class="text-center mt-3">Déjà un compte ? <a href="login.php">Se connecter</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>