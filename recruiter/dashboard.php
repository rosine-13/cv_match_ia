<?php
require_once '../config.php';

// 1. Sécurité : Vérification du rôle recruteur
if (!is_logged_in() || !is_recruiter()) {
    header("Location: login.php");
    exit;
}

// 2. Récupération de tous les CV déposés
// On fait une JOINTURE (JOIN) pour avoir le nom du candidat qui vient d'une autre table
$stmt = $pdo->query("
    SELECT cvs.id, cvs.cv_file_path, cvs.upload_date, cvs.status, cp.full_name, cp.city 
    FROM cvs 
    LEFT JOIN candidate_profiles cp ON cvs.candidate_id = cp.user_id
    ORDER BY cvs.upload_date DESC
");
$all_cvs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Recruteur - Liste des CV</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-success mb-4">
    <div class="container">
        <span class="navbar-brand">CVMatch IA | Administration RH</span>
        <a href="../logout.php" class="btn btn-outline-light btn-sm">Déconnexion</a>
    </div>
</nav>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h3 class="mb-4">Gestion des Candidatures</h3>
            
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form action="search_ia.php" method="GET" class="d-flex gap-2">
                        <input type="text" name="query" class="form-control" placeholder="Ex: Développeur PHP à Abidjan...">
                        <button type="submit" class="btn btn-primary">Recherche IA</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Candidat</th>
                                <th>Ville</th>
                                <th>Date d'envoi</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($all_cvs)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">Aucun CV n'a été déposé pour le moment.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($all_cvs as $cv): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($cv['full_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($cv['city']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($cv['upload_date'])) ?></td>
                                    <td>
                                        <span class="badge rounded-pill <?= $cv['status'] == 'Analysé' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                            <?= htmlspecialchars($cv['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="../<?= htmlspecialchars($cv['cv_file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">Voir le CV</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>