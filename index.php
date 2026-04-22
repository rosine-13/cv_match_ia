<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CVMatch IA - Accueil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .hero { background: #0d6efd; color: white; padding: 60px 0; text-align: center; }
    </style>
</head>
<body>
    <div class="hero">
        <h1>Bienvenue sur CVMatch IA</h1>
        <p>Recrutement intelligent par Intelligence Artificielle</p>
    </div>
    <div class="container mt-5">
        <div class="row justify-content-center text-center">
            <div class="col-md-4">
                <div class="card p-4 shadow">
                    <h3>Candidat</h3>
                    <p>Déposez votre CV et suivez vos candidatures.</p>
                    <a href="candidate/register.php" class="btn btn-primary">S'inscrire</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4 shadow border-success">
                    <h3>Recruteur</h3>
                    <p>Analysez les profils avec l'IA.</p>
                    <a href="recruiter/login.php" class="btn btn-success">Espace Recruteur</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>