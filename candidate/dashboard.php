<?php
require_once '../config.php';

// Sécurité : on vérifie que c'est bien un candidat
if (!is_logged_in() || !is_candidate()) {
    header("Location: login.php");
    exit;
}

// Récupérer les CV envoyés par ce candidat spécifique
$stmt = $pdo->prepare("SELECT cv_file_path, status, upload_date FROM cvs WHERE candidate_id = ? ORDER BY upload_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$my_cvs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Espace Candidat - CVMatch IA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">CVMatch IA</a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link btn btn-danger btn-sm text-white" href="../logout.php">Déconnexion</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <img src="https://ui-avatars.com/api/?name=<?= $_SESSION['username'] ?>&background=random" class="rounded-circle mb-3" alt="Avatar">
                    <h4><?= htmlspecialchars($_SESSION['username']) ?></h4>
                    <p class="text-muted small">Candidat</p>
                    <a href="upload_cv.php" class="btn btn-primary w-100">Déposer un nouveau CV</a>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <h3 class="mb-4">Mes candidatures</h3>
            
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (empty($my_cvs)): ?>
                        <p class="text-center text-muted">Vous n'avez pas encore envoyé de CV.</p>
                    <?php else: ?>
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Fichier</th>
                                    <th>Date d'envoi</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($my_cvs as $cv): ?>
                                <tr>
                                    <td>
                                        <a href="../<?= $cv['cv_file_path'] ?>" target="_blank" class="text-decoration-none">
                                            📄 <?= basename($cv['cv_file_path']) ?>
                                        </a>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($cv['upload_date'])) ?></td>
                                    <td>
                                        <?php if ($cv['status'] == 'En attente'): ?>
                                            <span class="badge bg-warning text-dark">En attente d'analyse</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Analysé par l'IA</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>